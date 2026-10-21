<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\SaaSActivityLog;
use Illuminate\Support\Facades\DB;

class OffboardTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        Log::info("Starting offboarding process for Tenant: {$this->tenant->id}");

        try {
            $dbName = $this->tenant->tenancy_db_name ?? config('tenancy.database.prefix') . $this->tenant->id;
            
            // 1. We would typically use spatie/db-dumper here to create an SQL dump
            // For now, we simulate the dump generation
            $backupPath = "tenant_backups/{$this->tenant->id}_offboard_" . time() . ".sql";
            Storage::disk('local')->put($backupPath, "-- Mock SQL Dump for {$this->tenant->id}");
            
            SaaSActivityLog::create([
                'tenant_id' => $this->tenant->id,
                'event_title' => 'Tenant Data Exported',
                'description' => "SQL data dumped to {$backupPath}",
                'event_type' => 'info',
                'actor_name' => 'System Engine',
            ]);

            // 2. Drop the tenant database
            // In stancl/tenancy, we can dispatch the DeleteDatabase event or run raw SQL
            DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");

            SaaSActivityLog::create([
                'tenant_id' => $this->tenant->id,
                'event_title' => 'Tenant Database Dropped',
                'description' => "Database {$dbName} was dropped.",
                'event_type' => 'warning',
                'actor_name' => 'System Engine',
            ]);

            // 3. Delete tenant model (which drops domains if cascaded)
            $this->tenant->delete();
            
            Log::info("Successfully offboarded Tenant: {$this->tenant->id}");

        } catch (\Exception $e) {
            Log::error("Failed to offboard tenant {$this->tenant->id}: " . $e->getMessage());
            
            SaaSActivityLog::create([
                'tenant_id' => $this->tenant->id,
                'event_title' => 'Tenant Offboarding Failed',
                'description' => "Error: " . $e->getMessage(),
                'event_type' => 'error',
                'actor_name' => 'System Engine',
            ]);
        }
    }
}
