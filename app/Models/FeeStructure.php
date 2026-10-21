<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'admission_fee',
        'registration_fee',
        'security_fee',
        'monthly_tuition',
        'annual_charges',
        'lab_charges',
        'transport_charges',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
