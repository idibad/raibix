<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenants = Illuminate\Support\Facades\DB::table('tenants')->get();
foreach ($tenants as $t) {
    $data = json_decode($t->data ?? '{}', true) ?? [];
    $logo = isset($data['logo']) ? $data['logo'] : null;
    if (!empty($logo) && (strpos($logo, 'http://') === 0 || strpos($logo, 'https://') === 0)) {
        $path = parse_url($logo, PHP_URL_PATH);
        if (strpos($path, '/uploads/') !== false) {
            $newPath = preg_replace('#^.*/uploads/#', '/uploads/', $path);
            $publicFile = __DIR__ . '/public' . $newPath;
            if (file_exists($publicFile)) {
                $data['logo'] = $newPath;
                echo 'Fixed ' . $t->id . ' to ' . $newPath . "\n";
            } else {
                $data['logo'] = null;
                echo 'Cleared ' . $t->id . ' (file missing: ' . $publicFile . ')' . "\n";
            }
            Illuminate\Support\Facades\DB::table('tenants')->where('id', $t->id)->update(['data' => json_encode($data)]);
        }
    } elseif (!empty($logo)) {
        echo $t->id . ' logo OK: ' . $logo . "\n";
    } else {
        echo $t->id . ' no logo' . "\n";
    }
}
echo 'Done.' . "\n";
