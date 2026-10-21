<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Central system health check verifying database and cache without leaking infrastructure topology.
     */
    public function check(Request $request): JsonResponse
    {
        $services = [
            'database' => 'down',
            'cache' => 'down',
        ];

        $overallHealthy = true;

        // Check central database
        try {
            DB::connection('central')->getPdo();
            $services['database'] = 'up';
        } catch (\Throwable $e) {
            $overallHealthy = false;
            Log::error('Central Health Check - Database Connection Failure: ' . $e->getMessage());
        }

        // Check cache store
        try {
            $cacheKey = 'health_check_' . time();
            Cache::put($cacheKey, 'ok', 5);
            if (Cache::get($cacheKey) === 'ok') {
                Cache::forget($cacheKey);
                $services['cache'] = 'up';
            } else {
                $overallHealthy = false;
            }
        } catch (\Throwable $e) {
            $overallHealthy = false;
            Log::error('Central Health Check - Cache Failure: ' . $e->getMessage());
        }

        $statusCode = $overallHealthy ? 200 : 503;

        return response()->json([
            'status' => $overallHealthy ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ], $statusCode);
    }
}
