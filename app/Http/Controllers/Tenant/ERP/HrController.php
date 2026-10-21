<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SalaryStructure;
use App\Models\Payroll;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class HrController extends Controller
{
    /**
     * Display HR Dashboard with Salary Structures, Payroll runs, and active Payslips.
     */
    public function index()
    {
        $staff = User::whereIn('user_type', ['teacher', 'admin', 'staff', 'registrar', 'accountant', 'front_desk', 'librarian'])
            ->with('roles')
            ->get();

        $salaryStructures = SalaryStructure::with('user')->get()->keyBy('user_id');
        $payrolls = Payroll::with('payslips.user')->latest()->get();

        return Inertia::render('Tenant/Admin/ERP/HR/Index', [
            'staff' => $staff,
            'salaryStructures' => $salaryStructures,
            'payrolls' => $payrolls,
        ]);
    }

    /**
     * Store or update a staff member's salary structure.
     */
    public function storeSalaryStructure(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'base_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|array',
            'allowances.*.name' => 'required|string',
            'allowances.*.amount' => 'required|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*.name' => 'required|string',
            'deductions.*.amount' => 'required|numeric|min:0',
        ]);

        $allowances = $validated['allowances'] ?? [];
        $deductions = $validated['deductions'] ?? [];

        $totalAllowances = collect($allowances)->sum('amount');
        $totalDeductions = collect($deductions)->sum('amount');
        $netSalary = $validated['base_salary'] + $totalAllowances - $totalDeductions;

        SalaryStructure::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'base_salary' => $validated['base_salary'],
                'allowances' => $allowances,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
            ]
        );

        return redirect()->back()->with('success', 'Salary structure saved successfully.');
    }

    /**
     * Generate bulk payroll for a given month and year.
     */
    public function generatePayroll(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2035',
        ]);

        $exists = Payroll::where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Payroll for this month/year already generated.');
        }

        $salaryStructures = SalaryStructure::all();
        if ($salaryStructures->isEmpty()) {
            return redirect()->back()->with('error', 'No salary structures configured yet. Please configure salary structures first.');
        }

        DB::beginTransaction();
        try {
            $payroll = Payroll::create([
                'month' => $validated['month'],
                'year' => $validated['year'],
                'status' => 'generated',
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($salaryStructures as $structure) {
                $allowancesSum = collect($structure->allowances)->sum('amount');
                $deductionsSum = collect($structure->deductions)->sum('amount');

                Payslip::create([
                    'payroll_id' => $payroll->id,
                    'user_id' => $structure->user_id,
                    'basic_pay' => $structure->base_salary,
                    'allowances' => $allowancesSum,
                    'deductions' => $deductionsSum,
                    'net_pay' => $structure->net_salary,
                    'status' => 'unpaid',
                ]);

                $totalAmount += $structure->net_salary;
            }

            $payroll->update(['total_amount' => $totalAmount]);

            DB::commit();
            return redirect()->back()->with('success', 'Payroll run generated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to generate payroll: ' . $e->getMessage());
        }
    }

    /**
     * Mark an individual payslip as paid.
     */
    public function payPayslip(int $id)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->update(['status' => 'paid']);

        // Check if all payslips in this payroll are paid
        $payroll = Payroll::with('payslips')->findOrFail($payslip->payroll_id);
        $unpaidCount = $payroll->payslips->where('status', 'unpaid')->count();
        if ($unpaidCount === 0) {
            $payroll->update(['status' => 'paid']);
        } else {
            $payroll->update(['status' => 'generated']);
        }

        return redirect()->back()->with('success', 'Payslip marked as paid.');
    }
}
