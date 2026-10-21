<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StaffAttendanceController extends Controller
{
    /**
     * Display staff attendance dashboard data.
     */
    public function index(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $month = $request->query('month', now()->format('Y-m'));

        $staffList = User::whereIn('user_type', ['teacher', 'admin', 'accountant', 'registrar', 'librarian'])->get();

        // Get attendance logs for the requested date
        $dailyLogs = DB::table('staff_attendances')
            ->where('date', $date)
            ->get()
            ->keyBy('user_id');

        // Get leave requests
        $leaves = DB::table('staff_leaves')
            ->join('users', 'staff_leaves.user_id', '=', 'users.id')
            ->select('staff_leaves.*', 'users.name as staff_name', 'users.user_type as role')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Compute monthly summary for payroll
        $monthlySummary = DB::table('staff_attendances')
            ->where('date', 'like', "{$month}%")
            ->select(
                'user_id',
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days"),
                DB::raw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days"),
                DB::raw("SUM(CASE WHEN status = 'half_day' THEN 0.5 ELSE 0 END) as half_days"),
                DB::raw("COUNT(*) as total_logged_days")
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'staff_list' => $staffList,
                'daily_logs' => $dailyLogs,
                'leaves' => $leaves,
                'monthly_summary' => $monthlySummary,
                'selected_date' => $date,
                'selected_month' => $month
            ]);
        }

        return redirect()->back()->with([
            'staff_attendance_logs' => $dailyLogs,
            'staff_leaves' => $leaves,
            'monthly_summary' => $monthlySummary
        ]);
    }

    /**
     * Mark daily check-in / check-out for staff.
     */
    public function storeCheckIn(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|string|in:present,absent,leave,late,half_day',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'remarks' => 'nullable|string'
        ]);

        $log = DB::table('staff_attendances')->updateOrInsert(
            ['user_id' => $validated['user_id'], 'date' => $validated['date']],
            [
                'status' => $validated['status'],
                'check_in_time' => $validated['check_in_time'] ?? null,
                'check_out_time' => $validated['check_out_time'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'marked_by_id' => Auth::id() ?? 1,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Staff attendance marked successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Staff attendance marked.');
    }

    /**
     * Submit leave request for staff.
     */
    public function submitLeaveRequest(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string|in:casual,sick,annual,maternity',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        $daysCount = $start->diffInDays($end) + 1;

        $id = DB::table('staff_leaves')->insertGetId([
            'user_id' => $validated['user_id'],
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Leave request logged cleanly.',
                'leave_id' => $id
            ]);
        }

        return redirect()->back()->with('success', 'Leave request logged cleanly.');
    }

    /**
     * Principal/Admin approves or rejects a leave request.
     */
    public function updateLeaveStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:approved,rejected',
            'admin_remarks' => 'nullable|string'
        ]);

        DB::table('staff_leaves')->where('id', $id)->update([
            'status' => $validated['status'],
            'approved_by_id' => Auth::id() ?? 1,
            'admin_remarks' => $validated['admin_remarks'] ?? null,
            'updated_at' => now()
        ]);

        // If approved, automatically populate staff_attendances with 'leave' status for all dates
        if ($validated['status'] === 'approved') {
            $leave = DB::table('staff_leaves')->where('id', $id)->first();
            if ($leave) {
                $current = \Carbon\Carbon::parse($leave->start_date);
                $end = \Carbon\Carbon::parse($leave->end_date);
                while ($current->lte($end)) {
                    DB::table('staff_attendances')->updateOrInsert(
                        ['user_id' => $leave->user_id, 'date' => $current->format('Y-m-d')],
                        [
                            'status' => 'leave',
                            'remarks' => "Approved {$leave->leave_type} leave: " . $leave->reason,
                            'marked_by_id' => Auth::id() ?? 1,
                            'updated_at' => now(),
                            'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')
                        ]
                    );
                    $current->addDay();
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Leave request {$validated['status']}."
            ]);
        }

        return redirect()->back()->with('success', "Leave request {$validated['status']}.");
    }
}
