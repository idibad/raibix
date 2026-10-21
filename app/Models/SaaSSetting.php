<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaaSSetting extends Model
{
    protected $table = 'saas_settings';

    protected $guarded = [];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'public_onboarding' => 'boolean',
        'require_email_verification' => 'boolean',
        'tax_exemption_enabled' => 'boolean',
        'default_trial_days' => 'integer',
    ];
}
