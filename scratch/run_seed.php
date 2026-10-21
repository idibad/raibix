<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;

$tenant = Tenant::first();
if ($tenant) {
    tenancy()->initialize($tenant);
    try {
        echo "Seeding tenant {$tenant->id}...\n";
        $seeder = new DatabaseSeeder();
        $seeder->run();
        echo "Successfully seeded tenant {$tenant->id}!\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No tenants found.\n";
}
