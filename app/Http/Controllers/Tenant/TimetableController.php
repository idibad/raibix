<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    /**
     * Display timetable grid data for a class/section.
     */
    public function index(Request $request)
    {
        $sectionId = $request->query('section_id');
        $classId = $request->query('class_id');

        $query = Timetable::with(['section.schoolClass', 'subject', 'teacher']);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        } elseif ($classId) {
            $query->whereHas('section', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        $timetables = $query->orderBy('day_of_week')->orderBy('start_time')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'timetables' => $timetables
            ]);
        }

        return redirect()->back()->with('timetables', $timetables);
    }

    /**
     * Store or update a timetable slot with intelligent conflict detection.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:timetables,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'room_number' => 'nullable|string|max:50',
        ]);

        // 1. Conflict Detection Engine (Check Teacher Double-Booking)
        if (!empty($validated['teacher_id'])) {
            $conflictQuery = Timetable::where('teacher_id', $validated['teacher_id'])
                ->where('day_of_week', $validated['day_of_week'])
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
                });

            if (!empty($validated['id'])) {
                $conflictQuery->where('id', '!=', $validated['id']);
            }

            $conflict = $conflictQuery->with('section.schoolClass')->first();

            if ($conflict) {
                $teacher = User::find($validated['teacher_id']);
                $teacherName = $teacher ? $teacher->name : 'This teacher';
                $conflictClass = $conflict->section && $conflict->section->schoolClass ? $conflict->section->schoolClass->name : 'Class';
                $conflictSection = $conflict->section ? $conflict->section->name : 'Section';

                throw ValidationException::withMessages([
                    'teacher_id' => ["Conflict detected: {$teacherName} is already assigned to {$conflictClass} ({$conflictSection}) on {$validated['day_of_week']} from {$conflict->start_time} to {$conflict->end_time}."]
                ]);
            }
        }

        // 2. Room Conflict Detection
        if (!empty($validated['room_number'])) {
            $roomConflictQuery = Timetable::where('room_number', $validated['room_number'])
                ->where('day_of_week', $validated['day_of_week'])
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
                });

            if (!empty($validated['id'])) {
                $roomConflictQuery->where('id', '!=', $validated['id']);
            }

            $roomConflict = $roomConflictQuery->with('section.schoolClass')->first();

            if ($roomConflict) {
                $conflictClass = $roomConflict->section && $roomConflict->section->schoolClass ? $roomConflict->section->schoolClass->name : 'Class';
                throw ValidationException::withMessages([
                    'room_number' => ["Room conflict: Room {$validated['room_number']} is already booked for {$conflictClass} on {$validated['day_of_week']} from {$roomConflict->start_time} to {$roomConflict->end_time}."]
                ]);
            }
        }

        if (!empty($validated['id'])) {
            $timetable = Timetable::findOrFail($validated['id']);
            $timetable->update($validated);
        } else {
            $timetable = Timetable::create($validated);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Timetable slot scheduled successfully without conflicts.',
                'timetable' => $timetable->load(['section.schoolClass', 'subject', 'teacher'])
            ]);
        }

        return redirect()->back()->with('success', 'Timetable slot scheduled cleanly without conflicts.');
    }

    /**
     * Remove the specified timetable slot.
     */
    public function destroy(Request $request, $id)
    {
        $timetable = Timetable::findOrFail($id);
        $timetable->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Timetable slot removed.'
            ]);
        }

        return redirect()->back()->with('success', 'Timetable slot removed.');
    }
}
