<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'section_id',
        'teacher_id',
        'title',
        'description',
        'due_date',
        'total_marks',
        'attachment_url',
        'external_links',
        'ai_evaluation_enabled',
    ];

    protected $casts = [
        'due_date' => 'date',
        'external_links' => 'array',
        'ai_evaluation_enabled' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function questions()
    {
        return $this->hasMany(AssignmentQuestion::class);
    }
}
