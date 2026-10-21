<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'submission_text',
        'file_url',
        'submission_file_name',
        'obtained_marks',
        'teacher_remarks',
        'ai_graded',
        'ai_suggested_marks',
        'ai_feedback',
        'status',
        'appeal_requested',
        'appeal_reason',
        'appeal_status',
        'reviewed_by_teacher_id',
        'reviewed_at',
    ];

    protected $casts = [
        'ai_graded' => 'boolean',
        'appeal_requested' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewedByTeacher()
    {
        return $this->belongsTo(User::class, 'reviewed_by_teacher_id');
    }
}
