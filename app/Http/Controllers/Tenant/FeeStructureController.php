<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeChallan;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeeStructureController extends Controller
{
    /**
     * Display fee structure dashboard data.
     */
    public function index(Request $request)
    {
        $structures = FeeStructure::with('schoolClass')->get();
        $overrides = DB::table('student_fee_overrides')
            ->join('students', 'student_fee_overrides.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('student_fee_overrides.*', 'users.name as student_name', 'students.roll_number', 'students.admission_number')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'structures' => $structures,
                'overrides' => $overrides
            ]);
        }

        return redirect()->back()->with(['structures' => $structures, 'overrides' => $overrides]);
    }

    /**
     * Update or create class fee structure.
     */
    public function updateClassStructure(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'admission_fee' => 'required|numeric|min:0',
            'registration_fee' => 'required|numeric|min:0',
            'security_fee' => 'required|numeric|min:0',
            'monthly_tuition' => 'required|numeric|min:0',
            'annual_charges' => 'required|numeric|min:0',
            'lab_charges' => 'required|numeric|min:0',
            'transport_charges' => 'required|numeric|min:0',
        ]);

        $structure = FeeStructure::updateOrCreate(
            ['class_id' => $validated['class_id']],
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Class fee structure updated successfully.',
                'structure' => $structure->load('schoolClass')
            ]);
        }

        return redirect()->back()->with('success', 'Class fee structure updated cleanly.');
    }

    /**
     * Store individual student fee override / scholarship (P1 #6).
     */
    public function storeStudentOverride(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_category' => 'required|string|in:tuition,lab,transport,annual',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'override_reason' => 'required|string|in:sibling_discount,merit_scholarship,staff_child,financial_aid,custom',
            'is_active' => 'boolean'
        ]);

        $id = DB::table('student_fee_overrides')->insertGetId(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        // Recalculate student challans dynamically
        $this->recalculateStudentChallans($validated['student_id']);

        // Trigger notice to parents/students
        $student = Student::with('user')->findOrFail($validated['student_id']);
        $discountDesc = !empty($validated['discount_percentage'])
            ? $validated['discount_percentage'] . '%'
            : 'PKR ' . $validated['discount_amount'];

        \App\Models\Notice::create([
            'title' => 'Fee Discount Applied: ' . $student->user->name,
            'content' => "A fee discount of {$discountDesc} on {$validated['fee_category']} fee has been successfully applied to student {$student->user->name} ({$validated['override_reason']}).",
            'target_audience' => 'all',
            'category' => 'Fee Update',
            'publish_date' => date('Y-m-d'),
            'created_by' => auth()->id(),
            'target_class_id' => $student->current_class_id,
            'send_sms_alert' => true,
            'send_whatsapp_alert' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Student fee override / scholarship applied successfully.',
                'override_id' => $id
            ]);
        }

        return redirect()->back()->with('success', 'Student fee override applied cleanly.');
    }

    /**
     * Remove student fee override.
     */
    public function destroyStudentOverride(Request $request, $id)
    {
        $override = DB::table('student_fee_overrides')->where('id', $id)->first();
        if ($override) {
            $studentId = $override->student_id;
            DB::table('student_fee_overrides')->where('id', $id)->delete();
            
            // Recalculate student challans dynamically
            $this->recalculateStudentChallans($studentId);
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Fee override removed.']);
        }

        return redirect()->back()->with('success', 'Fee override removed.');
    }

    /**
     * Helper to recalculate a student's active unpaid/partial challans dynamically
     */
    private function recalculateStudentChallans($studentId)
    {
        $student = Student::find($studentId);
        if (!$student || !$student->current_class_id) {
            return;
        }

        $structure = FeeStructure::where('class_id', $student->current_class_id)->first();
        if (!$structure) {
            return;
        }

        $overrides = DB::table('student_fee_overrides')
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->get();

        // Get all unpaid or partially paid challans
        $challans = FeeChallan::where('student_id', $studentId)
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($challans as $challan) {
            $tuitionFee = $structure->monthly_tuition;
            $labFee = $structure->lab_charges;
            $transportFee = $structure->transport_charges;
            $discountAmount = 0;

            foreach ($overrides as $override) {
                if ($override->fee_category === 'tuition') {
                    if ($override->discount_percentage > 0) {
                        $discountAmount += ($tuitionFee * ($override->discount_percentage / 100));
                    } else {
                        $discountAmount += $override->discount_amount;
                    }
                } elseif ($override->fee_category === 'lab') {
                    if ($override->discount_percentage > 0) {
                        $discountAmount += ($labFee * ($override->discount_percentage / 100));
                    } else {
                        $discountAmount += $override->discount_amount;
                    }
                }
            }

            $fineAmount = $challan->fine_amount; // preserve existing fine
            $totalAmount = max(0, $tuitionFee + $labFee + $transportFee - $discountAmount + $fineAmount);

            $challan->update([
                'tuition_fee' => $tuitionFee,
                'lab_fee' => $labFee,
                'transport_fee' => $transportFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => ($totalAmount <= $challan->paid_amount) ? 'paid' : ($challan->paid_amount > 0 ? 'partial' : 'unpaid')
            ]);
        }
    }

    /**
     * Recurring monthly fee invoice generator across active student body (P1 #6).
     */
    public function generateMonthlyChallans(Request $request)
    {
        $validated = $request->validate([
            'month_year' => 'required|string', // e.g. 2026-08
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'include_classes' => 'nullable|array' // optional filter by classes
        ]);

        $query = Student::where('status', 'active');
        if (!empty($validated['include_classes'])) {
            $query->whereIn('current_class_id', $validated['include_classes']);
        }

        $students = $query->with(['currentClass'])->get();
        $structures = FeeStructure::all()->keyBy('class_id');
        $allOverrides = DB::table('student_fee_overrides')->where('is_active', true)->get()->groupBy('student_id');

        $generatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                if (!$student->current_class_id || !isset($structures[$student->current_class_id])) {
                    continue;
                }

                $structure = $structures[$student->current_class_id];

                // Check if challan already generated for this student and month
                $existing = FeeChallan::where('student_id', $student->id)
                    ->where('month_year', $validated['month_year'])
                    ->where('fee_type', 'monthly')
                    ->first();

                if ($existing) {
                    continue; // skip double billing
                }

                // Compute base fees from class structure
                $tuitionFee = $structure->monthly_tuition;
                $labFee = $structure->lab_charges;
                $transportFee = $structure->transport_charges;
                $discountAmount = 0;

                // Check for student overrides & scholarships
                if (isset($allOverrides[$student->id])) {
                    foreach ($allOverrides[$student->id] as $override) {
                        if ($override->fee_category === 'tuition') {
                            if ($override->discount_percentage > 0) {
                                $discountAmount += ($tuitionFee * ($override->discount_percentage / 100));
                            } else {
                                $discountAmount += $override->discount_amount;
                            }
                        } elseif ($override->fee_category === 'lab') {
                            if ($override->discount_percentage > 0) {
                                $discountAmount += ($labFee * ($override->discount_percentage / 100));
                            } else {
                                $discountAmount += $override->discount_amount;
                            }
                        }
                    }
                }

                // Compute previous overdue balances & library fines
                $overdueBalance = FeeChallan::where('student_id', $student->id)
                    ->where('status', '!=', 'paid')
                    ->sum(DB::raw('total_amount - paid_amount'));

                $totalAmount = max(0, $tuitionFee + $labFee + $transportFee - $discountAmount + $overdueBalance);

                $challanNumber = 'CH-' . str_replace('-', '', $validated['month_year']) . '-' . str_pad($student->id, 4, '0', STR_PAD_LEFT);

                FeeChallan::create([
                    'challan_number' => $challanNumber,
                    'student_id' => $student->id,
                    'fee_type' => 'monthly',
                    'month_year' => $validated['month_year'],
                    'issue_date' => $validated['issue_date'],
                    'due_date' => $validated['due_date'],
                    'tuition_fee' => $tuitionFee,
                    'lab_fee' => $labFee,
                    'transport_fee' => $transportFee,
                    'discount_amount' => $discountAmount,
                    'fine_amount' => $overdueBalance,
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'status' => 'unpaid'
                ]);

                $generatedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate challans: ' . $e->getMessage()
            ], 500);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Successfully generated {$generatedCount} recurring monthly fee invoices for {$validated['month_year']}."
            ]);
        }

        if ($generatedCount === 0) {
            return redirect()->back()->with('error', "No fee invoices generated. Please ensure you have added a Fee Structure for the classes and that students are enrolled and active.");
        }

        return redirect()->back()->with('success', "Generated {$generatedCount} fee invoices cleanly.");
    }

    public function printChallan($id)
    {
        $challan = \App\Models\FeeChallan::with(['student.currentClass', 'transactions'])->findOrFail($id);
        
        return inertia('Tenant/Admin/PrintChallan', [
            'challan' => $challan
        ]);
    }
}
