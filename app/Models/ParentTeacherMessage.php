<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentTeacherMessage extends Model
{
    protected $guarded = [];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ParentTeacherThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
