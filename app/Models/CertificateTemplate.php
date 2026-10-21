<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variables' => 'array',
    ];

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(IssuedCertificate::class, 'template_id');
    }
}
