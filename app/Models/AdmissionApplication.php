<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_code',
        'inquiry_id',
        'student_name',
        'class_id',
        'father_name',
        'father_cnic',
        'father_phone',
        'mother_name',
        'mother_cnic',
        'documents',
        'test_date',
        'test_marks_obtained',
        'test_total_marks',
        'merit_status',
        'admission_status',
    ];

    protected $casts = [
        'documents' => 'array',
        'test_date' => 'date',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function inquiry()
    {
        return $this->belongsTo(AdmissionInquiry::class, 'inquiry_id');
    }
}
