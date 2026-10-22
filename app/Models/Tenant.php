<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'status',
        ];
    }

    /**
     * Get the dynamic domain suffix based on current request host or configured central domain.
     */
    public static function getDomainSuffix(): string
    {
        if ($suffix = env('TENANT_DOMAIN_SUFFIX')) {
            return $suffix;
        }

        $host = request()?->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host . '.nip.io';
        }

        return $host;
    }

    public function subscription()
    {
        return $this->hasOne(TenantSubscription::class, 'tenant_id');
    }

    /**
     * Resolve the active subscription plan for this tenant.
     */
    public function getPlanAttribute(): ?SubscriptionPlan
    {
        try {
            $subscription = $this->subscription()->with('plan')->latest()->first();
            if ($subscription && $subscription->plan) {
                return $subscription->plan;
            }
        } catch (\Throwable $e) {
            // Central DB might be disconnected or executing in isolated context
        }

        $planId = $this->plan_id ?? ($this->data['plan_id'] ?? null);
        if ($planId) {
            return SubscriptionPlan::find($planId);
        }

        return SubscriptionPlan::where('code', 'starter')->first();
    }

    /**
     * Check whether this tenant is permitted to access a specific system module.
     */
    public function canAccessModule(string $module): bool
    {
        $plan = $this->plan;
        if (!$plan) {
            return true;
        }

        return $plan->hasModule($module);
    }

    public function getLogoAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // Already a clean root-relative path (new format: /uploads/logos/...)
        if (str_starts_with($value, '/uploads/')) {
            return $value;
        }

        // Legacy uploads were stored behind the storage symlink, but the files
        // now live directly in public/uploads so they must use that URL.
        if (str_starts_with($value, '/storage/uploads/')) {
            return preg_replace('#^/storage/#', '/', $value);
        }

        // Preserve other storage paths that may still be valid.
        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        // Old format: full absolute URL like http://192.168.100.7:8000/storage/uploads/logos/...
        // Extract just the path so it works on any tenant subdomain
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $parsed = parse_url($value);
            $path   = $parsed['path'] ?? '';
            // Convert /storage/uploads/logos/... → /uploads/logos/...
            if (str_contains($path, '/uploads/')) {
                return preg_replace('#^.*/uploads/#', '/uploads/', $path);
            }
            // Keep as-is if we can't identify the path
            return $path ?: $value;
        }

        // Bare relative path like 'uploads/logos/...' or 'storage/uploads/...'
        $trimmed = ltrim($value, '/');
        if (str_starts_with($trimmed, 'storage/uploads/')) {
            return '/' . preg_replace('#^storage/#', '', $trimmed);
        }

        if (str_starts_with($trimmed, 'storage/')) {
            return '/' . $trimmed;
        }

        return '/' . $trimmed;
    }
}
