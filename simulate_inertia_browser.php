<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// 1. GET /login as browser
$req1 = \Illuminate\Http\Request::create('http://schooltest.localhost:8000/login', 'GET');
$resp1 = $kernel->handle($req1);
$kernel->terminate($req1, $resp1);

$cookies = $resp1->headers->getCookies();
$cookieMap = [];
$xsrf = '';
foreach ($cookies as $c) {
    $cookieMap[$c->getName()] = $c->getValue();
    if ($c->getName() === 'XSRF-TOKEN') $xsrf = urldecode($c->getValue());
}
$cookieHeader = implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($cookieMap), $cookieMap));
$encrypter = app('encrypter');
$decSessionId1 = explode('|', $encrypter->decrypt($cookieMap['laravel-session'], false))[1] ?? $encrypter->decrypt($cookieMap['laravel-session'], false);
$s1 = \Illuminate\Support\Facades\DB::connection('tenant')->table('sessions')->where('id', $decSessionId1)->first();
$dbToken = unserialize(base64_decode($s1->payload))['_token'] ?? $xsrf;

// 2. POST /login with X-Inertia
$req2 = \Illuminate\Http\Request::create('http://schooltest.localhost:8000/login', 'POST', [
    '_token' => $dbToken,
    'email' => 'student@schooltest.lmscloud.test',
    'password' => 'Student@123',
    'remember' => false,
]);
$req2->headers->set('Cookie', $cookieHeader);
$req2->headers->set('X-XSRF-TOKEN', $dbToken);
$req2->headers->set('X-Inertia', 'true');
$req2->headers->set('X-Inertia-Version', 'old-version-hash');
$req2->headers->set('X-Requested-With', 'XMLHttpRequest');

$resp2 = $kernel->handle($req2);
$kernel->terminate($req2, $resp2);

foreach ($resp2->headers->getCookies() as $c) {
    if ($c->getName() === 'laravel-session') {
        $cookieMap[$c->getName()] = $c->getValue();
    }
}
$cookieHeader2 = implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($cookieMap), $cookieMap));

// 3. Step 4: GET /student/dashboard as FULL HTML PAGE LOAD (after 409 reload!)
$req4 = \Illuminate\Http\Request::create('http://schooltest.localhost:8000/student/dashboard', 'GET');
$req4->headers->set('Cookie', $cookieHeader2);

$resp4 = $kernel->handle($req4);
$kernel->terminate($req4, $resp4);

echo "Step 4 (Full HTML load) Status: " . $resp4->getStatusCode() . "\n";
echo "Step 4 Location: " . $resp4->headers->get('Location') . "\n";

$content4 = $resp4->getContent();
// check what data-page attribute is in the HTML:
if (preg_match('/data-page="([^"]+)"/', $content4, $m)) {
    $page = json_decode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), true);
    echo "Step 4 Component: " . ($page['component'] ?? 'unknown') . "\n";
    echo "Step 4 Version: " . ($page['version'] ?? 'unknown') . "\n";
} else {
    echo "Step 4 No data-page found in HTML\n";
}
