<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'gateway_raw_response' => 'array',
    ];

    public function feeChallan(): BelongsTo
    {
        return $this->belongsTo(FeeChallan::class);
    }
}
