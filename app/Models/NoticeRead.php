<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'notice_id',
        'student_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notice()
    {
        return $this->belongsTo(Notice::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
