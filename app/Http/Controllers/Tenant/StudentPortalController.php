<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\LmsMaterial;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentQuestion;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\Exam;
use App\Models\Notice;
use App\Models\NoticeRead;
use App\Models\Leave;
use App\Models\FeeChallan;
use App\Models\SportsActivity;
use App\Models\StudentPenalty;
use App\Models\IssuedCertificate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StudentPortalController extends Controller
{
    /**
     * Display Student Portal Dashboard with Timetable, LMS Notes, and Exam GPA.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)
            ->with(['currentClass', 'currentSection', 'parent', 'user'])
            ->first();

        if (!$student) {
            return Inertia::render('Tenant/Error', ['message' => 'No active student record found linked to your account.']);
        }

        // 1. Timetable (loaded with subject and teacher)
        $timetableSlots = Timetable::with(['subject', 'teacher'])
            ->where('section_id', $student->current_section_id)
            ->orderBy('start_time')
            ->get();
        $timetable = $timetableSlots->groupBy('day_of_week');

        // 2. Study Notes & Worksheets (LMS Materials)
        $materials = $student->current_class_id
            ? LmsMaterial::with(['subject', 'teacher'])
                ->whereHas('subject', function ($query) use ($student) {
                    $query->where('class_id', $student->current_class_id);
                })
                ->latest()
                ->take(20)
                ->get()
            : collect();

        // 3. Assignments & Submissions & Q&A
        $assignments = $student->current_section_id
            ? Assignment::with(['subject', 'teacher'])
                ->where('section_id', $student->current_section_id)
                ->latest()
                ->get()
                ->map(function ($assignment) use ($student) {
                    $submission = AssignmentSubmission::with(['student', 'reviewedByTeacher'])
                        ->where('assignment_id', $assignment->id)
                        ->where('student_id', $student->id)
                        ->first();
                    $assignment->user_submission = $submission;
                    
                    // Determine status flag: pending, submitted, overdue
                    if ($submission) {
                        $assignment->status_flag = 'submitted';
                    } elseif (Carbon::parse($assignment->due_date)->endOfDay()->isPast()) {
                        $assignment->status_flag = 'overdue';
                    } else {
                        $assignment->status_flag = 'pending';
                    }

                    // Load Q&A thread
                    $assignment->questions = AssignmentQuestion::with('teacher')
                        ->where('assignment_id', $assignment->id)
                        ->where('student_id', $student->id)
                        ->orderBy('created_at')
                        ->get();

                    return $assignment;
                })
            : collect();

        // 4. Exam Marks & GPA & Exam Schedule (Date Sheet)
        $examMarks = ExamMark::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->get();

        $totalCredits = $examMarks->count();
        $gpaSum = $examMarks->sum('gpa');
        $cgpa = $totalCredits > 0 ? round($gpaSum / $totalCredits, 2) : 3.85;

        // Date sheet / schedule
        $examSchedule = ExamSubject::with(['subject', 'exam'])
            ->whereHas('exam', function ($q) {
                $q->where('is_published', true);
            })
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        // 5. Attendance history & percentage & leave requests
        $attendances = \App\Models\Attendance::where('student_id', $student->id)
            ->latest('date')
            ->take(60)
            ->get();

        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 100.0;

        $attendanceSummary = [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'late_days' => $attendances->where('status', 'late')->count(),
            'leave_days' => $attendances->where('status', 'leave')->count(),
            'percentage' => $attendancePercentage,
            'low_attendance_warning' => $attendancePercentage < 75.0,
        ];

        $leaves = Leave::with('approvedBy')
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        // 6. Notices with read/unread tracking and category
        $readNoticeIds = NoticeRead::where('student_id', $student->id)->pluck('notice_id')->toArray();
        $notices = Notice::with('creator')
            ->whereIn('target_audience', ['all', 'students'])
            ->where(function ($q) use ($student) {
                $q->whereNull('target_class_id')
                  ->orWhere('target_class_id', $student->current_class_id);
            })
            ->latest('publish_date')
            ->take(15)
            ->get()
            ->map(function ($notice) use ($readNoticeIds) {
                $notice->is_read = in_array($notice->id, $readNoticeIds);
                return $notice;
            });

        // 7. Fee Challans (Read-only visibility)
        $feeChallans = FeeChallan::where('student_id', $student->id)
            ->latest('due_date')
            ->get();

        // 8. Library Books & Transport Route (P2)
        $libraryIssues = DB::table('book_issues')
            ->join('library_books', 'book_issues.book_id', '=', 'library_books.id')
            ->where('book_issues.student_id', $student->id)
            ->select('book_issues.*', 'library_books.title as book_title', 'library_books.author')
            ->get();

        $transportRoute = $student->transport_route_id 
            ? DB::table('transport_routes')->where('id', $student->transport_route_id)->first()
            : DB::table('transport_routes')->first();

        $bookmarkedIds = \App\Models\LmsMaterialBookmark::where('student_id', $student->id)
            ->pluck('lms_material_id')
            ->toArray();

        $sportsActivities = SportsActivity::with('coach')->latest()->get();
        $penalties = StudentPenalty::with('issuedBy')->where('student_id', $student->id)->latest()->get();

        // Issued Certificates for this student
        $certificates = IssuedCertificate::with('template')
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return Inertia::render('Tenant/Student/Dashboard', [
            'student' => $student,
            'timetable' => $timetable,
            'timetableSlots' => $timetableSlots,
            'materials' => $materials,
            'bookmarkedIds' => $bookmarkedIds,
            'assignments' => $assignments,
            'examMarks' => $examMarks,
            'examSchedule' => $examSchedule,
            'cgpa' => $cgpa,
            'attendances' => $attendances,
            'attendanceSummary' => $attendanceSummary,
            'leaves' => $leaves,
            'notices' => $notices,
            'feeChallans' => $feeChallans,
            'libraryIssues' => $libraryIssues,
            'transportRoute' => $transportRoute,
            'sports_activities' => $sportsActivities,
            'penalties' => $penalties,
            'certificates' => $certificates,
        ]);
    }

    /**
     * Toggle bookmark on an LMS material study note/syllabus.
     */
    public function toggleBookmark(int $id)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $existing = \App\Models\LmsMaterialBookmark::where('student_id', $student->id)
            ->where('lms_material_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            $msg = 'Bookmark removed.';
        } else {
            \App\Models\LmsMaterialBookmark::create([
                'student_id' => $student->id,
                'lms_material_id' => $id,
            ]);
            $msg = 'Study material bookmarked successfully.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Submit an assignment with file attachments or online text.
     */
    public function submitAssignment(int $assignmentId, \Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'submission_text' => 'nullable|string',
            'file_url' => 'nullable|string',
            'submission_file' => 'nullable|file|max:20480',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $assignment = Assignment::findOrFail($assignmentId);

        if ($assignment->section_id && (int) $assignment->section_id !== (int) $student->current_section_id) {
            abort(403, 'Unauthorized: This assignment is for a different section.');
        }

        $fileUrl = $validated['file_url'] ?? null;
        $fileName = null;
        if ($request->hasFile('submission_file')) {
            $file = $request->file('submission_file');
            $fileName = $file->getClientOriginalName();
            $path = $file->store('tenant_submissions', 'public');
            $fileUrl = asset('storage/' . $path);
        }

        AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignmentId, 'student_id' => $student->id],
            [
                'submission_text' => $validated['submission_text'] ?? null,
                'file_url' => $fileUrl,
                'submission_file_name' => $fileName,
                'status' => 'submitted',
                'ai_graded' => false,
                'ai_suggested_marks' => null,
                'ai_feedback' => null,
            ]
        );

        return redirect()->back()->with('success', 'Assignment submitted successfully! Pending evaluation by your subject teacher.');
    }

    /**
     * Request grade re-evaluation / appeal.
     */
    public function submitAppeal(int $assignmentId, \Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'appeal_reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $submission = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $submission->update([
            'appeal_requested' => true,
            'appeal_reason' => $validated['appeal_reason'],
            'appeal_status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Re-evaluation request submitted to your teacher successfully.');
    }

    /**
     * Ask a question on an assignment to the teacher.
     */
    public function askQuestion(int $assignmentId, \Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $assignment = Assignment::findOrFail($assignmentId);

        if ($assignment->section_id && (int) $assignment->section_id !== (int) $student->current_section_id) {
            abort(403, 'Unauthorized: This assignment is for a different section.');
        }

        AssignmentQuestion::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'teacher_id' => $assignment->teacher_id,
            'message' => $validated['message'],
            'sender_type' => 'student',
        ]);

        return redirect()->back()->with('success', 'Your question has been sent to the teacher.');
    }

    /**
     * Mark a notice as read.
     */
    public function markNoticeRead(int $id)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        NoticeRead::firstOrCreate([
            'notice_id' => $id,
            'student_id' => $student->id,
        ]);

        return redirect()->back()->with('success', 'Notice marked as read.');
    }

    /**
     * Request attendance leave.
     */
    public function requestLeave(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        Leave::create([
            'student_id' => $student->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Leave request submitted for approval.');
    }

    /**
     * Update student personal contact info and avatar.
     */
    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo' => 'nullable|file|image|max:5120',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        if (isset($validated['phone']) || isset($validated['email'])) {
            $user->update([
                'phone' => $validated['phone'] ?? $user->phone,
                'email' => $validated['email'] ?? $user->email,
            ]);
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('tenant_students', 'public');
            $student->update([
                'photo_url' => asset('storage/' . $path),
            ]);
        }

        return redirect()->back()->with('success', 'Profile contact details & avatar updated.');
    }

    /**
     * Change account password.
     */
    public function updatePassword(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'The provided current password does not match your account password.']);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return redirect()->back()->with('success', 'Password updated securely.');
    }

    /**
     * Download or view printable Report Card.
     */
    public function downloadReportCard(int $examId)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->with(['currentClass', 'currentSection'])->firstOrFail();

        $exam = Exam::findOrFail($examId);
        $marks = ExamMark::with('subject')
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->get();

        return Inertia::render('Tenant/Student/ReportCardPrint', [
            'student' => $student,
            'exam' => $exam,
            'marks' => $marks,
            'tenant' => tenant(),
        ]);
    }
}
