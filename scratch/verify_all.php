<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\Notice;
use App\Models\SportsActivity;
use Illuminate\Support\Facades\Schema;

echo "--- 1. Central Subscriptions Table Verification ---\n";
$hasTrialEndsAt = Schema::hasColumn('tenant_subscriptions', 'trial_ends_at');
$hasIsOverdue = Schema::hasColumn('tenant_subscriptions', 'is_overdue');
echo "Has trial_ends_at: " . ($hasTrialEndsAt ? 'YES' : 'NO') . "\n";
echo "Has is_overdue: " . ($hasIsOverdue ? 'YES' : 'NO') . "\n";

echo "\n--- 2. Tenant Logo Accessor Verification ---\n";
$tenant = Tenant::first();
if ($tenant) {
    echo "Raw Logo value: " . var_export($tenant->getRawOriginal('logo'), true) . "\n";
    echo "Accessor Logo value: " . var_export($tenant->logo, true) . "\n";
} else {
    echo "No tenants found to check logo.\n";
}

if ($tenant) {
    tenancy()->initialize($tenant);
    
    echo "\n--- 3. Teacher Notice Board Verification ---\n";
    $noticesCount = Notice::whereIn('target_audience', ['all', 'teachers'])->count();
    echo "Teacher/All Notice count: " . $noticesCount . "\n";
    
    echo "\n--- 4. Sports Activity virtual fields ---\n";
    $activity = SportsActivity::first();
    if ($activity) {
        $arr = $activity->toArray();
        echo "name (title): " . ($arr['name'] === $arr['title'] ? 'MATCH' : 'MISMATCH') . "\n";
        echo "category (sport_type): " . ($arr['category'] === $arr['sport_type'] ? 'MATCH' : 'MISMATCH') . "\n";
        echo "schedule (schedule_date): " . ($arr['schedule'] === $arr['schedule_date'] ? 'MATCH' : 'MISMATCH') . "\n";
        echo "coach_user_id (coach_teacher_id): " . ($arr['coach_user_id'] === $arr['coach_teacher_id'] ? 'MATCH' : 'MISMATCH') . "\n";
    } else {
        echo "No sports activities found.\n";
    }

    echo "\n--- 5. CSV Exporter Route & Response Verification ---\n";
    $adminController = app(\App\Http\Controllers\Tenant\AdminPortalController::class);
    $request = \Illuminate\Http\Request::create('/admin/api/reports/export', 'GET', ['type' => 'student_directory']);
    
    // Bind current user
    $adminUser = \App\Models\User::where('user_type', 'admin')->first();
    if ($adminUser) {
        \Illuminate\Support\Facades\Auth::login($adminUser);
    }
    
    try {
        $response = $adminController->exportReports($request);
        echo "Export response type: " . get_class($response) . "\n";
        echo "Export status code: " . $response->getStatusCode() . "\n";
    } catch (\Exception $e) {
        echo "Export Error: " . $e->getMessage() . "\n";
    }
}
