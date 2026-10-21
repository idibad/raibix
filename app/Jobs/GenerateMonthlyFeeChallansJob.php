<?php

namespace App\Jobs;

use App\Models\FeeChallan;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyFeeChallansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $monthYear;
    public string $issueDate;
    public string $dueDate;
    public ?array $includeClasses;
    public ?string $tenantId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(string $monthYear, string $issueDate, string $dueDate, ?array $includeClasses = null, ?string $tenantId = null)
    {
        $this->monthYear = $monthYear;
        $this->issueDate = $issueDate;
        $this->dueDate = $dueDate;
        $this->includeClasses = $includeClasses;
        $this->tenantId = $tenantId ?: (function_exists('tenant') && tenant() ? tenant()->getTenantKey() : null);
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        if ($this->tenantId && (!function_exists('tenant') || !tenant() || tenant()->getTenantKey() !== $this->tenantId)) {
            tenancy()->initialize($this->tenantId);
        }

        $query = Student::where('status', 'active');
        if (!empty($this->includeClasses)) {
            $query->whereIn('current_class_id', $this->includeClasses);
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
                    ->where('month_year', $this->monthYear)
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

                $challanNumber = 'CH-' . str_replace('-', '', $this->monthYear) . '-' . str_pad($student->id, 4, '0', STR_PAD_LEFT);

                FeeChallan::create([
                    'challan_number' => $challanNumber,
                    'student_id' => $student->id,
                    'fee_type' => 'monthly',
                    'month_year' => $this->monthYear,
                    'issue_date' => $this->issueDate,
                    'due_date' => $this->dueDate,
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
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $generatedCount;
    }
}
