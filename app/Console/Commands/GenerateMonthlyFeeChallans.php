<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\FeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyFeeChallans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:generate-monthly {--month= : Target month in Y-m format} {--tenant= : Specific tenant id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated monthly cron job to generate fee challans with regional gateways and sibling discounts for all active schools';

    /**
     * Execute the console command.
     */
    public function handle(FeeService $feeService): int
    {
        $month = $this->option('month') ?? date('Y-m');
        $tenantId = $this->option('tenant');

        $this->info("Starting Automated Monthly Fee Challan Generation for [{$month}]...");

        $tenants = $tenantId ? Tenant::where('id', $tenantId)->get() : Tenant::where('status', 'active')->get();

        $totalGeneratedAcrossAllSchools = 0;

        foreach ($tenants as $tenant) {
            $this->info("Processing Tenant School: [{$tenant->name}] ({$tenant->id})...");
            
            try {
                tenancy()->initialize($tenant);
                
                $count = $feeService->generateMonthlyChallans($month);
                $totalGeneratedAcrossAllSchools += $count;
                
                $this->info(" -> Generated {$count} challans for {$tenant->name}");
                Log::info("Monthly Fee Challan Cron run for Tenant {$tenant->id}: Generated {$count} challans.");
                
                tenancy()->end();
            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->id}: " . $e->getMessage());
                Log::error("Cron Job Error on Tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed successfully! Total challans generated across all schools: {$totalGeneratedAcrossAllSchools}.");

        return Command::SUCCESS;
    }
}
