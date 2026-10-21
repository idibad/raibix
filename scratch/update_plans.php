<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SubscriptionPlan;

$starter = SubscriptionPlan::where('code', 'starter')->orWhere('id', 1)->first();
if ($starter) {
    $starter->update([
        'name' => 'Starter Plan',
        'code' => 'starter',
        'price_monthly' => 20000,
        'price_yearly' => 192000,
        'max_students' => 300,
        'features' => ['Core SIS & Admissions', 'Attendance & Biometrics', 'Automated Fee Management', 'Student & Parent Portals'],
    ]);
}

$essential = SubscriptionPlan::where('code', 'growth')->orWhere('code', 'essential')->orWhere('id', 2)->first();
if ($essential) {
    $essential->update([
        'name' => 'Essential Plan',
        'code' => 'essential',
        'price_monthly' => 40000,
        'price_yearly' => 384000,
        'max_students' => 1000,
        'features' => ['Everything in Starter', '1-Bill Banking Standard', 'LMS & Digital Gradebook', 'AI Facial Attendance', 'SMS Broadcasts', 'Multi-Campus Controls'],
    ]);
}

$sig = SubscriptionPlan::where('code', 'enterprise')->orWhere('code', 'signature')->orWhere('id', 3)->first();
if ($sig) {
    $sig->update([
        'name' => 'Signature Plan',
        'code' => 'signature',
        'price_monthly' => 50000,
        'price_yearly' => 480000,
        'max_students' => 5000,
        'features' => ['Everything in Essential', 'All 16 ERP Modules', 'Full Academic Ledger & Transcripts', 'Custom Domain & White-Label TLS', 'Dedicated Account Manager', '24/7 Priority SLA Support'],
    ]);
}

echo json_encode(SubscriptionPlan::all(), JSON_PRETTY_PRINT);
