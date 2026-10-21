<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\StudentPenalty;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenaltyController extends Controller
{
    /**
     * Issue a disciplinary penalty / fine to a student.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'penalty_type' => 'required|string|max:150',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string',
            'due_date' => 'nullable|date',
        ]);

        StudentPenalty::create([
            'student_id' => $validated['student_id'],
            'issued_by_user_id' => Auth::id(),
            'penalty_type' => $validated['penalty_type'],
            'amount' => $validated['amount'] ?? 0.00,
            'reason' => $validated['reason'],
            'status' => 'Issued',
            'due_date' => $validated['due_date'] ?? now()->addDays(7)->toDateString(),
        ]);

        return redirect()->back()->with('success', 'Disciplinary penalty/fine issued successfully.');
    }

    /**
     * Update status (Waived, Resolved, Paid).
     */
    public function updateStatus(Request $request, int $id)
    {
        $penalty = StudentPenalty::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:Issued,Resolved,Waived,Paid',
        ]);

        $penalty->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', "Penalty status updated to {$validated['status']}.");
    }

    /**
     * Delete penalty record.
     */
    public function destroy(int $id)
    {
        $penalty = StudentPenalty::findOrFail($id);
        $penalty->delete();

        return redirect()->back()->with('success', 'Penalty record deleted.');
    }
}
