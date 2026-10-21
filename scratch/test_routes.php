<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// 1. Test Landing Page
$reqLanding = Illuminate\Http\Request::create('/', 'GET');
$resLanding = $kernel->handle($reqLanding);
echo "1. Landing Page Status: " . $resLanding->getStatusCode() . "\n";

// 2. Test Checkout Page with essential plan
$reqCheckout = Illuminate\Http\Request::create('/checkout/essential', 'GET');
$resCheckout = $kernel->handle($reqCheckout);
echo "2. Checkout (/checkout/essential) Status: " . $resCheckout->getStatusCode() . "\n";

// 3. Test Checkout Page with starter plan
$reqCheckoutStarter = Illuminate\Http\Request::create('/checkout/starter', 'GET');
$resCheckoutStarter = $kernel->handle($reqCheckoutStarter);
echo "3. Checkout (/checkout/starter) Status: " . $resCheckoutStarter->getStatusCode() . "\n";

// 4. Test Subdomain API check
$reqSub = Illuminate\Http\Request::create('/api/check-subdomain?q=beaconhouse', 'GET');
$resSub = $kernel->handle($reqSub);
echo "4. Check Subdomain Status: " . $resSub->getStatusCode() . " Body: " . substr($resSub->getContent(), 0, 80) . "\n";
