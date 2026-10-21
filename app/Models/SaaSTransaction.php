<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SaaSTransaction extends Model
{
    use CentralConnection;
    protected $table = 'saas_transactions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
