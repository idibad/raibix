<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SportsActivity;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SportsActivityController extends Controller
{
    /**
     * Store a new sports or extracurricular activity.
     */
    public function store(Request $request)
    {
        if ($request->has('name')) {
            $request->merge([
                'title' => $request->input('name'),
                'sport_type' => $request->input('category', 'Sports'),
                'schedule_date' => $request->input('schedule'),
                'coach_teacher_id' => $request->input('coach_user_id', Auth::id()),
                'status' => $request->input('status', 'Upcoming'),
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sport_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'schedule_date' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
            'coach_teacher_id' => 'nullable|exists:users,id',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'required|string',
        ]);

        SportsActivity::create([
            'title' => $validated['title'],
            'sport_type' => $validated['sport_type'],
            'description' => $validated['description'] ?? null,
            'schedule_date' => $validated['schedule_date'] ?? null,
            'venue' => $validated['venue'] ?? 'Main Sports Ground',
            'coach_teacher_id' => $validated['coach_teacher_id'] ?? Auth::id(),
            'max_participants' => $validated['max_participants'] ?? 30,
            'status' => $validated['status'],
            'participants' => [],
        ]);

        return redirect()->back()->with('success', 'Sports activity / tournament scheduled successfully!');
    }

    /**
     * Update an existing sports activity.
     */
    public function update(Request $request, int $id)
    {
        $activity = SportsActivity::findOrFail($id);

        if ($request->has('name')) {
            $request->merge([
                'title' => $request->input('name'),
                'sport_type' => $request->input('category'),
                'schedule_date' => $request->input('schedule'),
                'coach_teacher_id' => $request->input('coach_user_id'),
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sport_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'schedule_date' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
            'coach_teacher_id' => 'nullable|exists:users,id',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'required|string',
        ]);

        $activity->update($validated);

        return redirect()->back()->with('success', 'Sports activity details updated successfully!');
    }

    /**
     * Delete a sports activity.
     */
    public function destroy(int $id)
    {
        $activity = SportsActivity::findOrFail($id);
        $activity->delete();

        return redirect()->back()->with('success', 'Sports activity removed successfully.');
    }

    /**
     * Allow student to join/register for a sports activity.
     */
    public function join(Request $request, int $id)
    {
        $activity = SportsActivity::findOrFail($id);
        $user = Auth::user();

        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return redirect()->back()->withErrors(['error' => 'Only active students can register for sports activities.']);
        }

        $participants = $activity->participants ?? [];
        if (in_array($student->id, $participants)) {
            return redirect()->back()->with('info', 'You are already registered for this sports activity.');
        }

        if (count($participants) >= $activity->max_participants) {
            return redirect()->back()->withErrors(['error' => 'This sports activity has reached maximum capacity.']);
        }

        $participants[] = $student->id;
        $activity->update(['participants' => array_values(array_unique($participants))]);

        return redirect()->back()->with('success', "Successfully registered for {$activity->title}!");
    }

    /**
     * Allow student to leave/withdraw from a sports activity.
     */
    public function leave(Request $request, int $id)
    {
        $activity = SportsActivity::findOrFail($id);
        $user = Auth::user();

        $student = Student::where('user_id', $user->id)->first();
        if ($student) {
            $participants = $activity->participants ?? [];
            $participants = array_filter($participants, fn($pId) => $pId != $student->id);
            $activity->update(['participants' => array_values($participants)]);
        }

        return redirect()->back()->with('success', 'You have withdrawn from the sports activity.');
    }
}
