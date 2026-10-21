<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'testadmin@lms.com')->first();
if ($user) {
    $matches = \Illuminate\Support\Facades\Hash::check('testadmin', $user->password);
    echo "testadmin password matches: " . ($matches ? "YES" : "NO") . "\n";
    if (!$matches) {
        // test other common passwords
        foreach (['password', 'admin', 'admin123', 'Secret@123'] as $p) {
            if (\Illuminate\Support\Facades\Hash::check($p, $user->password)) {
                echo "Matches with: $p\n";
            }
        }
    }
}
