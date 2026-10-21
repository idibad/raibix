<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sport_type',
        'description',
        'schedule_date',
        'venue',
        'coach_teacher_id',
        'max_participants',
        'status',
        'participants',
        'name',
        'category',
        'schedule',
        'coach_user_id',
    ];

    protected $casts = [
        'participants' => 'array',
    ];

    protected $appends = [
        'name',
        'category',
        'schedule',
        'coach_user_id',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_teacher_id');
    }

    public function getNameAttribute()
    {
        return $this->attributes['title'] ?? null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['title'] = $value;
    }

    public function getCategoryAttribute()
    {
        return $this->attributes['sport_type'] ?? null;
    }

    public function setCategoryAttribute($value)
    {
        $this->attributes['sport_type'] = $value;
    }

    public function getScheduleAttribute()
    {
        return $this->attributes['schedule_date'] ?? null;
    }

    public function setScheduleAttribute($value)
    {
        $this->attributes['schedule_date'] = $value;
    }

    public function getCoachUserIdAttribute()
    {
        return $this->attributes['coach_teacher_id'] ?? null;
    }

    public function setCoachUserIdAttribute($value)
    {
        $this->attributes['coach_teacher_id'] = $value;
    }
}
