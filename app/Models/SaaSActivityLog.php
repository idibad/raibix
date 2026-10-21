<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SaaSActivityLog extends Model
{
    use CentralConnection;

    protected $table = 'saas_activity_logs';

    protected $guarded = [];
}
