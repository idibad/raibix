<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TenantProvisioningService;
use App\Services\JazzCashService;
use Illuminate\Http\Request;
use App\Http\Controllers\Central\SaaSCheckoutController;

echo "=== Testing SaaSCheckoutController processCheckout ===\n";

$controller = app(SaaSCheckoutController::class);
$provisioningService = app(TenantProvisioningService::class);
$jazzCashService = app(JazzCashService::class);

$testId = 'simschool' . rand(100, 999);
$requestData = [
    'id' => $testId,
    'name' => 'Simulation High School ' . $testId,
    'domain' => $testId . '.localhost',
    'admin_name' => 'Dr. Sim Admin',
    'admin_email' => 'admin@' . $testId . '.test',
    'admin_phone' => '03001234567',
    'admin_password' => 'Secret@123',
    'plan_id' => 1,
    'billing_cycle' => 'monthly',
    'payment_gateway' => 'jazzcash',
    'mobile_number' => '03001234567',
];

$request = Request::create('/checkout', 'POST', $requestData);
// Start session for request
$session = app('session.store');
$session->start();
$request->setLaravelSession($session);

try {
    $response = $controller->processCheckout($request, $provisioningService, $jazzCashService);
    echo "processCheckout returned type: " . get_class($response) . "\n";
    if (method_exists($response, 'getTargetUrl')) {
        echo "Target URL: " . $response->getTargetUrl() . "\n";
    }
    echo "Session checkout_tenant id: " . ($session->get('checkout_tenant')['id'] ?? 'NULL') . "\n";
} catch (\Exception $e) {
    echo "EXCEPTION in processCheckout: " . get_class($e) . " - " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
