<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\Section;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\LmsMaterial;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Subject;
use App\Models\StudentObservation;
use App\Models\SportsActivity;
use App\Models\StudentPenalty;
use App\Models\Leave;
use App\Services\AiVisionService;
use App\Jobs\SendAbsentSmsJob;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeacherPortalController extends Controller
{
    /**
     * Display Teacher Portal Dashboard with Today's Classes, LMS Materials, Assignments, Gradebook, Observations, and Leaves.
     */
    public function index(): Response
    {
        $teacher = Auth::user();
        $todayDay = date('l'); // Monday, Tuesday...

        $todayClasses = Timetable::with(['section.schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $todayDay)
            ->get();

        $sections = Section::with(['schoolClass', 'classTeacher'])->get();
        $subjects = Subject::with('schoolClass')->get();
        $recentAssignments = Assignment::where('teacher_id', $teacher->id)->with(['subject', 'section.schoolClass', 'submissions.student.user'])->latest()->take(15)->get();
        $materials = LmsMaterial::where('teacher_id', $teacher->id)->with('subject')->latest()->take(10)->get();

        // Exam gradebook terms and student marks
        $exams = Exam::latest()->get();
        $students = Student::with(['user', 'currentClass', 'currentSection'])
            ->where('status', 'active')
            ->get();

        // Staff leaves & student observations
        $myLeaves = DB::table('staff_leaves')->where('user_id', $teacher->id)->latest()->get();
        $observations = StudentObservation::with(['student.user'])->where('teacher_id', $teacher->id)->latest()->take(15)->get();

        // Student leave requests for sections this teacher teaches
        $taughtSectionIds = Timetable::where('teacher_id', $teacher->id)->pluck('section_id')->unique();
        // Also include sections where this teacher is the class teacher
        $classTeacherSectionIds = Section::where('class_teacher_id', $teacher->id)->pluck('id');
        $allRelevantSectionIds = $taughtSectionIds->merge($classTeacherSectionIds)->unique()->values();
        $studentLeaves = Leave::with(['student.user', 'student.currentClass', 'student.currentSection', 'approvedBy'])
            ->whereHas('student', function ($q) use ($allRelevantSectionIds) {
                $q->whereIn('current_section_id', $allRelevantSectionIds);
            })
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        $notices = Notice::with('creator')
            ->whereIn('target_audience', ['all', 'teachers'])
            ->latest()
            ->get();

        // Sports & extracurricular activities (teacher can create/manage activities)
        $sportsActivities = SportsActivity::with('coach')->latest()->get();

        // Student penalties issued by or visible to this teacher
        $penalties = StudentPenalty::with(['student.user', 'issuedBy'])->latest()->get();

        return Inertia::render('Tenant/Teacher/Dashboard', [
            'todayClasses' => $todayClasses,
            'sections' => $sections,
            'subjects' => $subjects,
            'assignments' => $recentAssignments,
            'materials' => $materials,
            'exams' => $exams,
            'students' => $students,
            'myLeaves' => $myLeaves,
            'observations' => $observations,
            'student_leaves' => $studentLeaves,
            'sports_activities' => $sportsActivities,
            'penalties' => $penalties,
            'notices' => $notices,
        ]);
    }

    /**
     * Show Attendance Marking Grid (Manual & AI Camera Snapshot Modes).
     */
    public function showAttendance(Request $request): Response
    {
        $sectionId = $request->get('section_id', Section::first()?->id);
        $date = $request->get('date', date('Y-m-d'));

        $section = Section::with('schoolClass')->find($sectionId);
        $students = $section ? Student::where('current_section_id', $section->id)->with('user')->get() : collect();

        $attendances = Attendance::where('section_id', $sectionId)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        return Inertia::render('Tenant/Teacher/AttendanceGrid', [
            'section' => $section,
            'students' => $students,
            'attendances' => $attendances,
            'date' => $date,
        ]);
    }

    /**
     * Store manual attendance or trigger SendAbsentSmsJob upon absence.
     */
    public function storeAttendance(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'attendances' => 'required|array', // ['student_id' => 'present/absent/leave/late']
        ]);

        foreach ($validated['attendances'] as $studentId => $status) {
            $attendance = Attendance::where('student_id', $studentId)
                ->whereDate('date', $validated['date'])
                ->first();

            if ($attendance) {
                $attendance->update([
                    'section_id' => $validated['section_id'],
                    'status' => $status,
                    'marked_by_user_id' => Auth::id(),
                    'marked_by_ai' => false,
                ]);
            } else {
                $attendance = Attendance::create([
                    'student_id' => $studentId,
                    'section_id' => $validated['section_id'],
                    'date' => $validated['date'],
                    'status' => $status,
                    'marked_by_user_id' => Auth::id(),
                    'marked_by_ai' => false,
                ]);
            }

            if ($status === 'absent') {
                // Dispatch instant SMS/WhatsApp notification worker
                SendAbsentSmsJob::dispatch($studentId, $attendance->id);
            }
        }

        try {
            event(new \App\Events\TenantBroadcastEvent(
                'attendance-' . $validated['section_id'],
                'AttendanceUpdated',
                ['section_id' => $validated['section_id'], 'date' => $validated['date']]
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to broadcast attendance update: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Class attendance updated successfully and absence SMS/WhatsApp alerts dispatched!');
    }

    /**
     * Process AI Classroom Camera Snapshot for instant face recognition attendance.
     */
    public function processAiAttendance(Request $request, AiVisionService $aiVisionService)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'snapshot_url' => 'nullable|string',
            'snapshot_image' => 'nullable|file|image|max:15360', // Allow up to 15MB live camera snapshot or classroom photo
            'date' => 'nullable|date',
        ]);

        $snapshotUrl = $validated['snapshot_url'] ?? 'https://cdn.lmscloud.test/snapshots/classroom-cam-section-8a-live.jpg';

        if ($request->hasFile('snapshot_image')) {
            $path = $request->file('snapshot_image')->store('ai_snapshots', 'public');
            $snapshotUrl = asset('storage/' . $path);
        }

        $result = $aiVisionService->processClassroomSnapshot($snapshotUrl, $validated['section_id'], $validated['date'] ?? null);

        try {
            event(new \App\Events\TenantBroadcastEvent(
                'attendance-' . $validated['section_id'],
                'AttendanceUpdated',
                ['section_id' => $validated['section_id'], 'date' => $validated['date'] ?? date('Y-m-d'), 'ai' => true]
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to broadcast AI attendance update: " . $e->getMessage());
        }

        return redirect()->back()->with('success', "AI Vision complete! Processed picture and marked {$result['marked_count']} students present with >95% verification confidence.");
    }

    /**
     * Store new LMS Lecture Material (PDFs, PPTs, Videos, Notes) via direct document upload or URL.
     */
    public function storeMaterial(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_type' => 'required|string',
            'file_url' => 'nullable|string',
            'file_upload' => 'nullable|file|max:20480', // Allow up to 20MB document upload
        ]);

        $fileUrl = $validated['file_url'] ?? '';

        if ($request->hasFile('file_upload')) {
            $path = $request->file('file_upload')->store('tenant_materials', 'public');
            $fileUrl = asset('storage/' . $path);
        }

        if (empty($fileUrl)) {
            return redirect()->back()->withErrors(['file_upload' => 'Please upload a document file or provide a valid file URL.']);
        }

        LmsMaterial::create([
            'subject_id' => $validated['subject_id'],
            'teacher_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'file_type' => $validated['file_type'],
            'file_url' => $fileUrl,
            'is_downloadable' => true,
        ]);

        return redirect()->back()->with('success', 'Learning material document uploaded and made available to students!');
    }

    /**
     * Store new Homework Assignment with AI Evaluation Toggle and document upload support.
     */
    public function storeAssignment(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
            'total_marks' => 'required|numeric|min:1',
            'attachment_url' => 'nullable|string',
            'attachment_file' => 'nullable|file|max:20480',
            'external_links' => 'nullable|array',
            'external_links.*.title' => 'nullable|string|max:255',
            'external_links.*.url' => 'nullable|string|max:1000',
            'ai_evaluation_enabled' => 'boolean',
        ]);

        $attachmentUrl = $validated['attachment_url'] ?? null;

        if ($request->hasFile('attachment_file')) {
            $path = $request->file('attachment_file')->store('tenant_assignments', 'public');
            $attachmentUrl = asset('storage/' . $path);
        }

        Assignment::create([
            'subject_id' => $validated['subject_id'],
            'section_id' => $validated['section_id'],
            'teacher_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'due_date' => $validated['due_date'],
            'total_marks' => $validated['total_marks'],
            'attachment_url' => $attachmentUrl,
            'external_links' => $request->external_links ?? [],
            'ai_evaluation_enabled' => $request->boolean('ai_evaluation_enabled', true),
        ]);

        return redirect()->back()->with('success', 'Assignment published with document attachments & external study links!');
    }

    /**
     * Grade or update review for a student's assignment submission.
     */
    public function gradeSubmission(int $submissionId, Request $request)
    {
        $validated = $request->validate([
            'obtained_marks' => 'required|numeric|min:0',
            'teacher_remarks' => 'nullable|string|max:1000',
        ]);

        $teacher = Auth::user();
        $submission = AssignmentSubmission::findOrFail($submissionId);

        $assignment = Assignment::findOrFail($submission->assignment_id);
        $isPrivileged = in_array(strtolower($teacher->user_type ?? ''), ['admin', 'principal']);
        if (!$isPrivileged && method_exists($teacher, 'hasAnyRole')) {
            try {
                $isPrivileged = $teacher->hasAnyRole(['Super Admin', 'Principal', 'Admin']);
            } catch (\Throwable $e) {}
        }
        if ((int) $assignment->teacher_id !== (int) $teacher->id && !$isPrivileged) {
            abort(403, 'Unauthorized: You cannot evaluate or grade assignments belonging to another teacher.');
        }

        $submission->update([
            'obtained_marks' => $validated['obtained_marks'],
            'teacher_remarks' => $validated['teacher_remarks'] ?? null,
            'status' => 'graded',
            'reviewed_by_teacher_id' => $teacher->id,
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Student submission graded and remarks saved successfully!');
    }

    /**
     * Teacher-End Only: Run AI Evaluation draft screening for a student's submission.
     * Generates ai_suggested_marks and ai_feedback internally for teacher review before finalizing.
     */
    public function aiEvaluateSubmission(int $submissionId, AiVisionService $aiVisionService)
    {
        $teacher = Auth::user();
        $submission = AssignmentSubmission::findOrFail($submissionId);
        $assignment = Assignment::findOrFail($submission->assignment_id);
        $isPrivileged = in_array(strtolower($teacher->user_type ?? ''), ['admin', 'principal']);
        if (!$isPrivileged && method_exists($teacher, 'hasAnyRole')) {
            try {
                $isPrivileged = $teacher->hasAnyRole(['Super Admin', 'Principal', 'Admin']);
            } catch (\Throwable $e) {}
        }
        if ((int) $assignment->teacher_id !== (int) $teacher->id && !$isPrivileged) {
            abort(403, 'Unauthorized: You cannot evaluate or grade assignments belonging to another teacher.');
        }

        $maxMarks = floatval($assignment->total_marks ?? 20);
        $evaluation = $aiVisionService->evaluateAssignment($submission, $maxMarks);

        $submission->update([
            'ai_graded' => true,
            'ai_suggested_marks' => $evaluation['suggested_marks'],
            'ai_feedback' => $evaluation['feedback'],
        ]);

        return redirect()->back()->with('success', 'AI Draft Evaluation generated! Please review and finalize the student grade below.');
    }

    /**
     * Store or bulk-update student marks inside the Exam Gradebook.
     */
    public function storeMarks(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks' => 'required|array', // [student_id => ['theory' => X, 'practical' => Y, 'viva' => Z]]
        ]);

        foreach ($validated['marks'] as $studentId => $scores) {
            $theory = floatval($scores['theory'] ?? 0);
            $practical = floatval($scores['practical'] ?? 0);
            $viva = floatval($scores['viva'] ?? 0);
            $total = $theory + $practical + $viva;
            $percentage = min(100, max(0, ($total / 100) * 100));

            $grade = 'F';
            $gpa = 0.00;
            if ($percentage >= 85) { $grade = 'A+'; $gpa = 4.00; }
            elseif ($percentage >= 75) { $grade = 'A'; $gpa = 3.70; }
            elseif ($percentage >= 65) { $grade = 'B'; $gpa = 3.00; }
            elseif ($percentage >= 50) { $grade = 'C'; $gpa = 2.00; }
            elseif ($percentage >= 40) { $grade = 'D'; $gpa = 1.00; }

            ExamMark::updateOrCreate(
                [
                    'exam_id' => $validated['exam_id'],
                    'student_id' => $studentId,
                    'subject_id' => $validated['subject_id'],
                ],
                [
                    'theory_marks_obtained' => $theory,
                    'practical_marks_obtained' => $practical,
                    'viva_marks_obtained' => $viva,
                    'total_marks_obtained' => $total,
                    'percentage' => $percentage,
                    'grade' => $grade,
                    'gpa' => $gpa,
                ]
            );
        }

        return redirect()->back()->with('success', 'Exam marks saved and grade calculations updated successfully!');
    }

    /**
     * Store new Behavioral / Academic Observation Note targeting a student and parent.
     */
    public function storeObservation(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|string', // commendation, warning, behavior, academic_alert
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'parent_visible' => 'boolean',
        ]);

        $validated['teacher_id'] = Auth::id();
        StudentObservation::create($validated);

        return redirect()->back()->with('success', 'Student observation logged and synced to Parent portal feed!');
    }

    /**
     * Submit leave application request from Teacher portal.
     */
    public function storeLeaveRequest(Request $request)
    {
        $validated = $request->validate([
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'required|string',
        ]);

        DB::table('staff_leaves')->insert([
            'user_id' => Auth::id(),
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave application submitted for Principal review & substitute assignment!');
    }

    /**
     * Update teacher personal contact info.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'phone' => 'nullable|string|max:50',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'phone' => $validated['phone'],
            'email' => $validated['email'],
        ]);

        return redirect()->back()->with('success', 'Profile contact details updated.');
    }

    /**
     * Change teacher account password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'The provided current password does not match your account password.']);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['new_password']),
        ]);

        return redirect()->back()->with('success', 'Password updated securely.');
    }

    /**
     * Approve or reject a student leave request (class teacher action).
     */
    public function updateStudentLeaveStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status'        => 'required|in:approved,rejected',
            'admin_remarks' => 'nullable|string|max:500',
        ]);

        $teacher = Auth::user();
        $leave = Leave::with('student')->findOrFail($id);

        $isAdmin = in_array(strtolower($teacher->user_type ?? ''), ['admin', 'principal']);
        if (!$isAdmin && method_exists($teacher, 'hasAnyRole')) {
            try {
                $isAdmin = $teacher->hasAnyRole(['Super Admin', 'Principal', 'Admin']);
            } catch (\Throwable $e) {}
        }

        if (!$isAdmin) {
            $student = $leave->student;
            $sectionId = $student?->current_section_id;

            $taughtSectionIds = Timetable::where('teacher_id', $teacher->id)->pluck('section_id')->unique();
            $classTeacherSectionIds = Section::where('class_teacher_id', $teacher->id)->pluck('id');
            $allRelevantSectionIds = $taughtSectionIds->merge($classTeacherSectionIds)->unique()->values();

            if (!$sectionId || !$allRelevantSectionIds->contains($sectionId)) {
                abort(403, 'Unauthorized: You are not assigned to this student\'s section.');
            }
        }

        $leave->update([
            'status'         => $validated['status'],
            'admin_remarks'  => $validated['admin_remarks'] ?? null,
            'approved_by_id' => $teacher->id,
        ]);

        return redirect()->back()->with('success', 'Student leave request ' . $validated['status'] . '.');
    }
}

