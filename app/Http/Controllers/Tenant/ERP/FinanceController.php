<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Expense;
use App\Models\RefundRequest;
use App\Models\FeeChallan;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * Display Finance & Expense Dashboard with Analytics, Vendors, Expenses, and Refunds.
     */
    public function index()
    {
        $vendors = Vendor::all();
        $expenses = Expense::with('vendor')->latest()->get();
        $refundRequests = RefundRequest::with('student.user', 'feeChallan')->latest()->get();

        // Finance Metrics (integrating real fee challans data)
        $recentTransactions = FeeChallan::where('status', 'paid')
            ->with('student.user')
            ->latest('updated_at')
            ->take(20)
            ->get();

        $totalOutstanding = FeeChallan::where('status', 'unpaid')
            ->sum(DB::raw('total_amount - paid_amount'));

        $totalCollectionYtd = FeeChallan::where('status', 'paid')
            ->whereYear('updated_at', date('Y'))
            ->sum('paid_amount');

        // Monthly collection vs monthly target
        $currentMonthCollection = FeeChallan::where('status', 'paid')
            ->whereMonth('updated_at', date('m'))
            ->whereYear('updated_at', date('Y'))
            ->sum('paid_amount');
        
        $monthlyTarget = 500000; // Customizable benchmark target
        $percentageAchieved = $monthlyTarget > 0 ? min(100, round(($currentMonthCollection / $monthlyTarget) * 100, 2)) : 0;

        return Inertia::render('Tenant/Admin/ERP/Finance/Index', [
            'vendors' => $vendors,
            'expenses' => $expenses,
            'refundRequests' => $refundRequests,
            'metrics' => [
                'recent_transactions' => $recentTransactions,
                'total_outstanding' => floatval($totalOutstanding),
                'ytd_collection' => floatval($totalCollectionYtd),
                'monthly_collection' => floatval($currentMonthCollection),
                'monthly_target' => floatval($monthlyTarget),
                'collection_percentage' => floatval($percentageAchieved),
            ]
        ]);
    }

    /**
     * Store new Vendor.
     */
    public function storeVendor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        Vendor::create($validated);

        return redirect()->back()->with('success', 'Vendor registered successfully.');
    }

    /**
     * Store new Expense.
     */
    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'vendor_id' => 'nullable|exists:vendors,id',
            'reference' => 'nullable|string|max:255',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Expense::create($validated);

        return redirect()->back()->with('success', 'Expense recorded successfully.');
    }

    /**
     * Update Refund Request status.
     */
    public function updateRefundRequest(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,refunded',
            'admin_notes' => 'nullable|string',
        ]);

        $refund = RefundRequest::findOrFail($id);

        DB::beginTransaction();
        try {
            $refund->update($validated);

            // If status is refunded, deduct amount from paid_amount or handle accounts
            if ($validated['status'] === 'refunded' && $refund->fee_challan_id) {
                $challan = FeeChallan::find($refund->fee_challan_id);
                if ($challan) {
                    $challan->paid_amount = max(0, $challan->paid_amount - $refund->amount);
                    if ($challan->paid_amount < $challan->total_amount) {
                        $challan->status = 'unpaid';
                    }
                    $challan->save();
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Refund request status updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update refund request: ' . $e->getMessage());
        }
    }
}
