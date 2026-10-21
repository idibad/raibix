<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category',
        'target_audience',
        'target_class_id',
        'publish_date',
        'send_sms_alert',
        'send_whatsapp_alert',
        'created_by',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'send_sms_alert' => 'boolean',
        'send_whatsapp_alert' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetClass()
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }

    public function reads()
    {
        return $this->hasMany(NoticeRead::class);
    }
}
