<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TenantSubscription;
use App\Models\Tenant;
use App\Models\SaaSActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RunDunningCycle extends Command
{
    protected $signature = 'saas:run-dunning';
    protected $description = 'Run the daily dunning cycle to check for overdue subscriptions and suspend tenants if grace period is exceeded.';

    public function handle()
    {
        $this->info('Starting SaaS Dunning Cycle...');

        // Find subscriptions that are active but expired
        $expiredSubscriptions = TenantSubscription::with('plan', 'tenant')
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->get();

        $suspendedCount = 0;
        $overdueCount = 0;

        foreach ($expiredSubscriptions as $sub) {
            $gracePeriod = $sub->plan->grace_period_days ?? 3;
            $daysOverdue = now()->diffInDays($sub->ends_at);

            if ($daysOverdue > $gracePeriod) {
                // Suspend the tenant
                $sub->status = 'suspended';
                $sub->save();

                $tenant = $sub->tenant;
                if ($tenant) {
                    $data = is_array($tenant->data) ? $tenant->data : (json_decode($tenant->data ?? '[]', true) ?? []);
                    $data['status'] = 'suspended';
                    $tenant->data = $data;
                    $tenant->status = 'suspended';
                    $tenant->save();

                    SaaSActivityLog::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => null, // System action
                        'action' => 'Auto-Suspended',
                        'description' => "Tenant suspended automatically after exceeding {$gracePeriod} days grace period.",
                        'type' => 'error'
                    ]);

                    // TODO: Send email to school admin
                }
                
                $suspendedCount++;
                $this->warn("Suspended tenant: {$sub->tenant_id}");
            } else {
                // Mark as overdue but within grace period
                if (!$sub->is_overdue) {
                    $sub->is_overdue = true;
                    $sub->save();
                    
                    SaaSActivityLog::create([
                        'tenant_id' => $sub->tenant_id,
                        'user_id' => null,
                        'action' => 'Subscription Overdue',
                        'description' => "Subscription expired. Grace period ends in " . ($gracePeriod - $daysOverdue) . " days.",
                        'type' => 'warning'
                    ]);
                }
                $overdueCount++;
                $this->info("Marked overdue: {$sub->tenant_id}");
            }
        }

        $this->info("Dunning Cycle Complete. Overdue: {$overdueCount}. Suspended: {$suspendedCount}.");
    }
}
