<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\SportsActivity;

$tenant = Tenant::first();
if ($tenant) {
    tenancy()->initialize($tenant);
    try {
        echo "Count of sports_activities: " . SportsActivity::count() . "\n";
        print_r(SportsActivity::first()?->toArray());
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No tenants found.\n";
}
