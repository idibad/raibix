<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamGradebookController extends Controller
{
    /**
     * Display exams and gradebook data.
     */
    public function index(Request $request)
    {
        $examId = $request->query('exam_id');
        $subjectId = $request->query('subject_id');
        $classId = $request->query('class_id');

        $exams = Exam::orderBy('start_date', 'desc')->get();
        $marks = [];

        if ($examId && $subjectId) {
            $query = ExamMark::with(['student.user', 'student.currentClass', 'subject'])
                ->where('exam_id', $examId)
                ->where('subject_id', $subjectId);

            if ($classId) {
                $query->whereHas('student', function ($q) use ($classId) {
                    $q->where('current_class_id', $classId);
                });
            }

            $marks = $query->get()->keyBy('student_id');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'exams' => $exams,
                'marks' => $marks
            ]);
        }

        return redirect()->back()->with(['exams' => $exams, 'exam_marks' => $marks]);
    }

    /**
     * Store or update an exam term / session.
     */
    public function storeExam(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:exams,id',
            'academic_year_id' => 'required|integer',
            'term_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_published' => 'boolean'
        ]);

        if (!empty($validated['id'])) {
            $exam = Exam::findOrFail($validated['id']);
            $exam->update($validated);
        } else {
            $exam = Exam::create($validated);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Exam definition saved cleanly.',
                'exam' => $exam
            ]);
        }

        return redirect()->back()->with('success', 'Exam definition saved cleanly.');
    }

    /**
     * Bulk save marks for a subject across multiple students and compute grades automatically (P1 #9).
     */
    public function bulkSaveMarks(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks' => 'required|array',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.theory_marks' => 'nullable|numeric|min:0',
            'marks.*.practical_marks' => 'nullable|numeric|min:0',
            'marks.*.viva_marks' => 'nullable|numeric|min:0',
            'marks.*.teacher_remarks' => 'nullable|string'
        ]);

        $subject = Subject::findOrFail($validated['subject_id']);
        $maxMarks = $subject->total_marks > 0 ? $subject->total_marks : 100;

        $processedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['marks'] as $row) {
                $theory = floatval($row['theory_marks'] ?? 0);
                $practical = floatval($row['practical_marks'] ?? 0);
                $viva = floatval($row['viva_marks'] ?? 0);
                $total = $theory + $practical + $viva;

                $percentage = ($total / $maxMarks) * 100;
                $percentage = min(100, max(0, round($percentage, 2)));

                // Compute standard Grade & GPA
                if ($percentage >= 90) {
                    $grade = 'A+';
                    $gpa = 4.00;
                } elseif ($percentage >= 80) {
                    $grade = 'A';
                    $gpa = 3.70;
                } elseif ($percentage >= 70) {
                    $grade = 'B';
                    $gpa = 3.00;
                } elseif ($percentage >= 60) {
                    $grade = 'C';
                    $gpa = 2.00;
                } elseif ($percentage >= 50) {
                    $grade = 'D';
                    $gpa = 1.00;
                } else {
                    $grade = 'F';
                    $gpa = 0.00;
                }

                ExamMark::updateOrCreate(
                    [
                        'exam_id' => $validated['exam_id'],
                        'student_id' => $row['student_id'],
                        'subject_id' => $validated['subject_id']
                    ],
                    [
                        'theory_marks_obtained' => $theory,
                        'practical_marks_obtained' => $practical,
                        'viva_marks_obtained' => $viva,
                        'total_marks_obtained' => $total,
                        'percentage' => $percentage,
                        'grade' => $grade,
                        'gpa' => $gpa,
                        'teacher_remarks' => $row['teacher_remarks'] ?? null
                    ]
                );

                $processedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save gradebook marks: ' . $e->getMessage()
            ], 500);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Successfully recorded grades for {$processedCount} students."
            ]);
        }

        return redirect()->back()->with('success', "Recorded grades for {$processedCount} students cleanly.");
    }

    /**
     * Automated Report Card Generator with attendance, marks, and remarks (P1 #9).
     */
    public function generateReportCard(Request $request, $examId, $studentId)
    {
        $exam = Exam::findOrFail($examId);
        $student = Student::with(['user', 'parent', 'currentClass', 'currentSection'])->findOrFail($studentId);

        $marks = ExamMark::with('subject')
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->get();

        // Compute overall statistics across all subjects
        $totalObtained = $marks->sum('total_marks_obtained');
        $totalMax = $marks->sum(function ($m) {
            return $m->subject ? $m->subject->total_marks : 100;
        });

        $overallPercentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;
        $averageGpa = $marks->count() > 0 ? round($marks->avg('gpa'), 2) : 0;

        if ($overallPercentage >= 90) {
            $overallGrade = 'A+';
        } elseif ($overallPercentage >= 80) {
            $overallGrade = 'A';
        } elseif ($overallPercentage >= 70) {
            $overallGrade = 'B';
        } elseif ($overallPercentage >= 60) {
            $overallGrade = 'C';
        } elseif ($overallPercentage >= 50) {
            $overallGrade = 'D';
        } else {
            $overallGrade = 'F';
        }

        // Get student attendance during the exam term / period
        $attendanceStats = DB::table('attendances')
            ->where('student_id', $studentId)
            ->whereBetween('date', [$exam->start_date, $exam->end_date])
            ->select(
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days"),
                DB::raw("COUNT(*) as total_days")
            )
            ->first();

        $attendancePercentage = ($attendanceStats && $attendanceStats->total_days > 0)
            ? round(($attendanceStats->present_days / $attendanceStats->total_days) * 100, 1)
            : 100; // default if no attendance logged during term

        $reportData = [
            'exam' => $exam,
            'student' => $student,
            'subject_marks' => $marks,
            'summary' => [
                'total_marks_obtained' => $totalObtained,
                'total_max_marks' => $totalMax,
                'overall_percentage' => $overallPercentage,
                'overall_grade' => $overallGrade,
                'average_gpa' => $averageGpa,
                'attendance_percentage' => $attendancePercentage,
                'days_present' => $attendanceStats ? $attendanceStats->present_days : 0,
                'total_school_days' => $attendanceStats ? $attendanceStats->total_days : 0,
                'remarks' => $marks->pluck('teacher_remarks')->filter()->implode(' | ') ?: 'Consistent performance and conduct throughout the term.'
            ]
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'report_card' => $reportData
            ]);
        }

        return redirect()->back()->with('report_card', $reportData);
    }
}
