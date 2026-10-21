<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;

echo "Migrating & Seeding Test Users Across All School Tenants...\n";

// 1. First make sure Central Demo Data (Super Admin & Plans) exists
$seeder = new DatabaseSeeder();
$seeder->run();
echo "✓ Central Demo Data Verified.\n";

// 2. Loop over all existing tenants and seed them
$tenants = Tenant::all();
if ($tenants->isEmpty()) {
    echo "No tenants found in central DB. Creating default 'local' tenant...\n";
    $tenant = Tenant::create(['id' => 'local', 'data' => ['name' => 'Excellence High School', 'status' => 'active']]);
    $tenant->domains()->create(['domain' => 'local.localhost']);
    $tenants = Tenant::all();
}

foreach ($tenants as $tenant) {
    echo "Processing Tenant ID: {$tenant->id}...\n";
    try {
        // Run tenant migrations if needed
        \Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);
        
        // Initialize tenancy
        tenancy()->initialize($tenant);
        
        // Run tenant seeder
        $seeder->run();
        
        echo "  ✓ Successfully seeded test users for school '{$tenant->id}' (Principal, Teacher, Student, Parent)\n";
        
        tenancy()->end();
    } catch (\Exception $e) {
        echo "  ✗ Error processing '{$tenant->id}': " . $e->getMessage() . "\n";
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }
}

echo "\nMigration & Seeding Complete!\n";
