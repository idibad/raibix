<?php

namespace App\Services;

use App\Models\FeeChallan;
use App\Models\FeeStructure;
use App\Models\FeeTransaction;
use App\Models\Student;
use App\Models\AdmissionApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FeeService
{
    /**
     * Generate Admission Fee Challan for a newly approved admission application.
     */
    public function generateAdmissionChallan(AdmissionApplication $application): FeeChallan
    {
        $structure = FeeStructure::where('class_id', $application->class_id)->first();

        $admissionFee = $structure ? $structure->admission_fee : 15000.00;
        $registrationFee = $structure ? $structure->registration_fee : 3000.00;
        $securityFee = $structure ? $structure->security_fee : 5000.00;
        $monthlyTuition = $structure ? $structure->monthly_tuition : 8000.00;

        $total = $admissionFee + $registrationFee + $securityFee + $monthlyTuition;

        // Student records require a user, even while admission is pending.
        $studentEmail = 'admission.' . $application->id . '@student.test';
        $studentUser = User::firstOrCreate(
            ['email' => $studentEmail],
            [
                'name' => $application->student_name,
                'user_type' => 'student',
                'status' => 'inactive',
                'password' => Hash::make(Str::random(32)),
            ]
        );

        $dummyStudent = Student::firstOrCreate(
            ['admission_number' => 'ADM-TEMP-' . $application->id],
            [
                'user_id' => $studentUser->id,
                'status' => 'fee_pending',
                'current_class_id' => $application->class_id,
            ]
        );

        if (!$dummyStudent->user_id) {
            $dummyStudent->update(['user_id' => $studentUser->id]);
        }

        // Check if an admission challan already exists for this dummy student to prevent UNIQUE constraint violation on challan_number
        $existingChallan = FeeChallan::where('student_id', $dummyStudent->id)
            ->where('fee_type', 'admission')
            ->first();
        if ($existingChallan) {
            return $existingChallan;
        }

        return FeeChallan::create([
            'challan_number' => 'CH-ADM-' . date('Ym') . '-' . str_pad($application->id, 4, '0', STR_PAD_LEFT),
            'student_id' => $dummyStudent->id,
            'fee_type' => 'admission',
            'month_year' => date('Y-m'),
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'admission_fee' => $admissionFee,
            'registration_fee' => $registrationFee,
            'security_fee' => $securityFee,
            'tuition_fee' => $monthlyTuition,
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);
    }

    /**
     * Generate Monthly Fee Challans for all active enrolled students (Cron Job logic).
     */
    public function generateMonthlyChallans(string $monthYear = null): int
    {
        $monthYear = $monthYear ?? date('Y-m');
        $students = Student::where('status', 'active')->with(['currentClass', 'parent.students'])->get();
        $generatedCount = 0;

        foreach ($students as $student) {
            // Check if challan already generated for this month
            if (FeeChallan::where('student_id', $student->id)->where('month_year', $monthYear)->where('fee_type', 'monthly')->exists()) {
                continue;
            }

            $structure = FeeStructure::where('class_id', $student->current_class_id)->first();
            $tuitionFee = $structure ? $structure->monthly_tuition : 8000.00;
            $labFee = ($student->currentClass && in_array($student->currentClass->level, ['matric', 'college'])) ? ($structure->lab_charges ?? 1500.00) : 0;
            
            // Sibling discount calculation (10% discount if parent has more than 1 active child)
            $discount = 0;
            if ($student->parent && $student->parent->students()->where('status', 'active')->count() > 1) {
                $discount = round($tuitionFee * 0.10, 2);
            }

            // Check overdue fine from previous unpaid challans
            $overdueChallans = FeeChallan::where('student_id', $student->id)->whereIn('status', ['unpaid', 'overdue'])->get();
            $fineAmount = $overdueChallans->count() * 500.00; // Fixed 500 fine per overdue challan

            $total = ($tuitionFee + $labFee + $fineAmount) - $discount;

            FeeChallan::create([
                'challan_number' => 'CH-MON-' . date('ymd') . '-' . str_pad($student->id, 4, '0', STR_PAD_LEFT),
                'student_id' => $student->id,
                'fee_type' => 'monthly',
                'month_year' => $monthYear,
                'issue_date' => now(),
                'due_date' => now()->addDays(10),
                'tuition_fee' => $tuitionFee,
                'lab_fee' => $labFee,
                'discount_amount' => $discount,
                'fine_amount' => $fineAmount,
                'total_amount' => max(0, $total),
                'paid_amount' => 0,
                'status' => 'unpaid',
            ]);

            $generatedCount++;
        }

        return $generatedCount;
    }

    /**
     * Process online/regional payment (JazzCash / Meezan Bank API / Bank Deposit).
     */
    public function processPayment(FeeChallan $challan, string $gateway, float $amount, array $gatewayPayload = []): FeeTransaction
    {
        return DB::transaction(function () use ($challan, $gateway, $amount, $gatewayPayload) {
            $transaction = FeeTransaction::create([
                'fee_challan_id' => $challan->id,
                'gateway' => $gateway,
                'amount' => $amount,
                'payment_date' => now(),
                'transaction_reference' => $gatewayPayload['tid'] ?? $gatewayPayload['reference_no'] ?? strtoupper(Str::random(12)),
                'jazzcash_mobile_number' => $gatewayPayload['mobile_number'] ?? null,
                'jazzcash_response_code' => $gatewayPayload['response_code'] ?? null,
                'meezan_account_number' => $gatewayPayload['account_number'] ?? null,
                'meezan_bill_reference' => $gatewayPayload['bill_reference'] ?? null,
                'gateway_raw_response' => $gatewayPayload,
                'status' => 'success',
            ]);

            $newPaidAmount = $challan->paid_amount + $amount;
            $status = ($newPaidAmount >= $challan->total_amount) ? 'paid' : 'partial';

            $challan->update([
                'paid_amount' => $newPaidAmount,
                'status' => $status,
            ]);

            // If this was an admission fee challan, trigger student enrollment
            if ($challan->fee_type === 'admission' && $status === 'paid') {
                $applicationId = (int) str_replace('ADM-TEMP-', '', $challan->student->admission_number);
                $application = AdmissionApplication::find($applicationId);
                if ($application && $application->admission_status !== 'enrolled') {
                    app(AdmissionService::class)->enrollStudent($application);
                }
            }

            return $transaction;
        });
    }
}
