<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    protected $guarded = [];

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }
}
