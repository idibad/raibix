<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $guarded = [];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
