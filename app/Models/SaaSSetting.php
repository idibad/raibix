<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SaaSSetting extends Model
{
    use CentralConnection;

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
