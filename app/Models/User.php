<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function parentsProfile(): HasOne
    {
        return $this->hasOne(ParentsProfile::class);
    }

    public function hasNoHistoricalData(): bool
    {
        // Check if this user has any historical records attached
        if ($this->student || $this->parentsProfile) {
            return false;
        }
        $attendanceCount = \App\Models\Attendance::where('student_id', $this->id)->count();
        if ($attendanceCount > 0) {
            return false;
        }
        return true;
    }
}
