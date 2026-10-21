<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantHealthCheckController extends Controller
{
    /**
     * Tenant system health check verifying isolated database, cache, and storage without leaking topology.
     */
    public function check(Request $request): JsonResponse
    {
        $services = [
            'database' => 'down',
            'cache' => 'down',
            'storage' => 'down',
        ];

        $overallHealthy = true;

        if (!function_exists('tenant') || !tenant()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant context not initialized.',
            ], 503);
        }

        // 1. Verify tenant database connectivity
        try {
            DB::connection()->getPdo();
            $services['database'] = 'up';
        } catch (\Throwable $e) {
            $overallHealthy = false;
            Log::error('Tenant Health Check - Database Connection Failure: ' . $e->getMessage());
        }

        // 2. Verify cache store access
        try {
            $cacheKey = 'tenant_health_' . tenant()->getTenantKey() . '_' . time();
            Cache::put($cacheKey, 'ok', 5);
            if (Cache::get($cacheKey) === 'ok') {
                Cache::forget($cacheKey);
                $services['cache'] = 'up';
            } else {
                $overallHealthy = false;
            }
        } catch (\Throwable $e) {
            $overallHealthy = false;
            Log::error('Tenant Health Check - Cache Failure: ' . $e->getMessage());
        }

        // 3. Verify local storage writeability
        try {
            $testFileName = 'tenant_health_' . time() . '.tmp';
            Storage::disk('local')->put($testFileName, 'healthy');
            if (Storage::disk('local')->exists($testFileName)) {
                Storage::disk('local')->delete($testFileName);
                $services['storage'] = 'up';
            } else {
                $overallHealthy = false;
            }
        } catch (\Throwable $e) {
            $overallHealthy = false;
            Log::error('Tenant Health Check - Storage Failure: ' . $e->getMessage());
        }

        $statusCode = $overallHealthy ? 200 : 503;

        return response()->json([
            'status' => $overallHealthy ? 'ok' : 'error',
            'tenant' => 'active',
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ], $statusCode);
    }
}
