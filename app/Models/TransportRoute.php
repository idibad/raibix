<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    protected $table = 'transport_routes';

    protected $guarded = [];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TransportStop::class, 'route_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TransportAllocation::class, 'route_id');
    }
}
