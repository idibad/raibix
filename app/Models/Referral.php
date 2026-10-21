<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Referral extends Model
{
    use CentralConnection;

    protected $fillable = [
        'referrer_tenant_id',
        'referred_tenant_id',
        'status',
    ];

    public function referrer()
    {
        return $this->belongsTo(Tenant::class, 'referrer_tenant_id');
    }

    public function referred()
    {
        return $this->belongsTo(Tenant::class, 'referred_tenant_id');
    }
}
