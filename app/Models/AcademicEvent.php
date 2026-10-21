<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_school_off' => 'boolean',
    ];
}
