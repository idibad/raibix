<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'photo_embedding' => 'array',
        'date_of_birth' => 'date',
        'admission_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentsProfile::class, 'parent_id');
    }

    public function currentClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'current_class_id');
    }

    public function currentSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'current_section_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function feeChallans(): HasMany
    {
        return $this->hasMany(FeeChallan::class);
    }

    public function examMarks(): HasMany
    {
        return $this->hasMany(ExamMark::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(StudentStatusLog::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class)->latest();
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class)->latest();
    }

    public function noticeReads(): HasMany
    {
        return $this->hasMany(NoticeRead::class);
    }

    public function assignmentQuestions(): HasMany
    {
        return $this->hasMany(AssignmentQuestion::class)->latest();
    }
}
