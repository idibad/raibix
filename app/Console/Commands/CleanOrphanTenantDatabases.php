<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanOrphanTenantDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:clean-orphans
                            {--path= : Custom directory path to scan for databases}
                            {--force : Force delete orphan tenant databases without confirmation}
                            {--dry-run : Only show orphan databases without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphan or test tenant SQLite database files not registered in the central database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dbPath = $this->option('path') ? (string) $this->option('path') : database_path();
        if (!File::isDirectory($dbPath)) {
            $this->error("Database path not found: {$dbPath}");
            return 1;
        }

        // Fetch all active/registered tenant IDs from central database
        $registeredTenants = Tenant::all()->pluck('id')->toArray();
        $validFileNames = [];

        foreach ($registeredTenants as $id) {
            $validFileNames["tenant{$id}"] = true;
            $validFileNames["tenant{$id}.sqlite"] = true;
        }

        $allFiles = File::files($dbPath);
        $orphanFiles = [];
        $totalBytes = 0;

        foreach ($allFiles as $file) {
            $filename = $file->getFilename();

            // Ignore central database, gitignore, and files not starting with 'tenant'
            if (!str_starts_with($filename, 'tenant')) {
                continue;
            }

            // If file is not in valid registered tenants list, mark as orphan
            if (!isset($validFileNames[$filename])) {
                $orphanFiles[] = $file;
                $totalBytes += $file->getSize();
            }
        }

        $orphanCount = count($orphanFiles);
        $mbFreed = round($totalBytes / (1024 * 1024), 2);

        $this->info("Registered school tenants found: " . count($registeredTenants));
        $this->info("Orphan/test database files detected: {$orphanCount} ({$mbFreed} MB)");

        if ($orphanCount === 0) {
            $this->info("No orphan tenant databases found. Database directory is clean.");
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->warn("Dry run active. Files that would be deleted (showing first 15):");
            foreach (array_slice($orphanFiles, 0, 15) as $orphan) {
                $this->line(" - " . $orphan->getFilename());
            }
            if ($orphanCount > 15) {
                $this->line(" ... and " . ($orphanCount - 15) . " more files.");
            }
            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Are you sure you want to permanently delete {$orphanCount} orphan database files ({$mbFreed} MB)?", false)) {
                $this->warn("Operation cancelled by user.");
                return 0;
            }
        }

        $deletedCount = 0;
        foreach ($orphanFiles as $orphan) {
            try {
                if (File::delete($orphan->getPathname())) {
                    $deletedCount++;
                }
            } catch (\Throwable $e) {
                $this->warn("Failed to delete {$orphan->getFilename()}: " . $e->getMessage());
            }
        }

        $this->info("Successfully deleted {$deletedCount}/{$orphanCount} orphan database files. Freed ~{$mbFreed} MB.");

        return 0;
    }
}
