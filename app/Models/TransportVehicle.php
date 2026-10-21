<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportVehicle extends Model
{
    protected $table = 'transport_vehicles';

    protected $guarded = [];

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class, 'vehicle_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TransportAllocation::class, 'vehicle_id');
    }
}
