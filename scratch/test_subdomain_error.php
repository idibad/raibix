<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req = \Illuminate\Http\Request::create('http://testsgchool.localhost:8000/', 'GET');
$resp = $kernel->handle($req);

echo "Status: " . $resp->getStatusCode() . "\n";
$content = $resp->getContent();
if (preg_match('/data-page="([^"]+)"/', $content, $m)) {
    $page = json_decode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), true);
    echo "Component: " . ($page['component'] ?? 'unknown') . "\n";
    echo "Props: " . json_encode($page['props'] ?? []) . "\n";
} else {
    echo "NO DATA PAGE MATCHED. FULL CONTENT:\n";
    echo $content . "\n";
}
