<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentTeacherThread;
use App\Models\ParentTeacherMessage;
use App\Models\User;
use App\Models\Student;
use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ParentTeacherChatController extends Controller
{
    /**
     * Display chat threads list and contacts.
     */
    public function index()
    {
        $user = Auth::user();
        $threadsQuery = ParentTeacherThread::with(['parentUser', 'teacherUser', 'student.user', 'messages.sender']);

        $recipients = [];

        if ($user->user_type === 'parent') {
            // Logged in as parent
            $threadsQuery->where('parent_user_id', $user->id);

            // Parents can contact the class teachers of their children
            $parentProfile = \App\Models\ParentsProfile::where('user_id', $user->id)->with('students.currentSection.classTeacher')->first();
            if ($parentProfile) {
                foreach ($parentProfile->students as $student) {
                    $teacher = $student->currentSection?->classTeacher;
                    if ($teacher) {
                        $recipients[] = [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'role' => 'Teacher',
                            'student_id' => $student->id,
                            'student_name' => $student->user?->name,
                        ];
                    }
                }
            }

        } elseif (in_array($user->user_type, ['staff', 'teacher'])) {
            // Logged in as teacher
            $threadsQuery->where('teacher_user_id', $user->id);

            // Teachers can contact parents of students in their assigned sections
            $sections = Section::where('class_teacher_id', $user->id)->pluck('id');
            $students = Student::whereIn('current_section_id', $sections)->with(['parent.user', 'user'])->get();
            
            foreach ($students as $student) {
                $parentUser = $student->parent?->user;
                if ($parentUser) {
                    $recipients[] = [
                        'id' => $parentUser->id,
                        'name' => $parentUser->name . " (Parent of " . $student->user?->name . ")",
                        'role' => 'Parent',
                        'student_id' => $student->id,
                        'student_name' => $student->user?->name,
                    ];
                }
            }
        } elseif ($user->user_type === 'admin') {
            // Logged in as principal/admin
            $threadsQuery->where(function ($q) use ($user) {
                $q->where('parent_user_id', $user->id)
                  ->orWhere('teacher_user_id', $user->id);
            });

            // Admin can message any teacher
            $teachers = User::whereIn('user_type', ['staff', 'teacher'])->get();
            foreach ($teachers as $t) {
                $recipients[] = [
                    'id' => $t->id,
                    'name' => $t->name . " (Teacher)",
                    'role' => 'Teacher',
                    'student_id' => Student::first()?->id ?? 1,
                    'student_name' => 'General Topic',
                ];
            }

            // Admin can message any parent
            $students = Student::with(['parent.user', 'user'])->where('status', 'active')->get();
            foreach ($students as $student) {
                $parentUser = $student->parent?->user;
                if ($parentUser) {
                    $recipients[] = [
                        'id' => $parentUser->id,
                        'name' => $parentUser->name . " (Parent of " . $student->user?->name . ")",
                        'role' => 'Parent',
                        'student_id' => $student->id,
                        'student_name' => $student->user?->name,
                    ];
                }
            }
        }

        $threads = $threadsQuery->latest('updated_at')->get();

        // If rendering via Inertia, check roles and route accordingly
        $viewPath = $user->user_type === 'parent' 
            ? 'Tenant/Parent/Chat' 
            : ($user->user_type === 'admin' 
                ? 'Tenant/Admin/Chat' 
                : 'Tenant/Teacher/Chat');

        return Inertia::render($viewPath, [
            'threads' => $threads,
            'recipients' => collect($recipients)->unique('id')->values()->all(),
            'current_user' => $user,
        ]);
    }

    /**
     * Create a new message thread.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'student_id' => 'required|exists:students,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $user = Auth::user();

        if ($user->user_type === 'parent') {
            $parentUserId = $user->id;
            $teacherUserId = $validated['recipient_id'];
        } elseif ($user->user_type === 'admin') {
            $recipient = User::findOrFail($validated['recipient_id']);
            if ($recipient->user_type === 'parent') {
                $parentUserId = $recipient->id;
                $teacherUserId = $user->id;
            } else {
                $parentUserId = $user->id;
                $teacherUserId = $recipient->id;
            }
        } else {
            $parentUserId = $validated['recipient_id'];
            $teacherUserId = $user->id;
        }

        // Check if thread already exists
        $thread = ParentTeacherThread::where('parent_user_id', $parentUserId)
            ->where('teacher_user_id', $teacherUserId)
            ->where('student_id', $validated['student_id'])
            ->first();

        if (!$thread) {
            $thread = ParentTeacherThread::create([
                'parent_user_id' => $parentUserId,
                'teacher_user_id' => $teacherUserId,
                'student_id' => $validated['student_id'],
                'subject' => $validated['subject'],
            ]);
        }

        ParentTeacherMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        $thread->touch(); // Update updated_at timestamp

        return redirect()->back()->with('success', 'Message sent successfully.');
    }

    /**
     * Reply to an existing thread.
     */
    public function reply(Request $request, $threadId)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $thread = ParentTeacherThread::findOrFail($threadId);
        $user = Auth::user();

        // Security check
        if ($thread->parent_user_id !== $user->id && $thread->teacher_user_id !== $user->id) {
            abort(403, 'Unauthorized thread access.');
        }

        ParentTeacherMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        $thread->touch();

        return redirect()->back()->with('success', 'Reply posted.');
    }
}
