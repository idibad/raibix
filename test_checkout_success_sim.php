<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Central\SaaSCheckoutController;
use App\Models\Tenant;

echo "=== Testing SaaSCheckoutController showCheckoutSuccess ===\n";

$controller = app(SaaSCheckoutController::class);

// Find any existing tenant
$tenant = Tenant::first();
if (!$tenant) {
    echo "No tenant found in central DB!\n";
    exit(1);
}

echo "Testing with tenant ID: {$tenant->id}\n";
$request = Request::create("/checkout-success?tenant_id={$tenant->id}", 'GET');
$session = app('session.store');
$session->start();
$request->setLaravelSession($session);

try {
    $response = $controller->showCheckoutSuccess($request);
    echo "showCheckoutSuccess returned type: " . get_class($response) . "\n";
} catch (\Exception $e) {
    echo "EXCEPTION in showCheckoutSuccess: " . get_class($e) . " - " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
