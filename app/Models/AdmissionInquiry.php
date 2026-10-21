<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_code',
        'student_name',
        'previous_school',
        'desired_class_id',
        'parent_name',
        'parent_phone',
        'parent_email',
        'notes',
        'follow_up_date',
        'status',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    public function desiredClass()
    {
        return $this->belongsTo(SchoolClass::class, 'desired_class_id');
    }
}
