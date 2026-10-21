<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionCommunication extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(AdmissionInquiry::class, 'inquiry_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class, 'application_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
