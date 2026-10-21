<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Coupon extends Model
{
    use CentralConnection;

    protected $fillable = [
        'code',
        'discount_percentage',
        'expires_at',
        'max_uses',
        'times_used',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
