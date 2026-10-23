<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SubscriptionPlan extends Model
{
    use CentralConnection;

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'modules' => 'array',
        'price_per_student' => 'float',
        'minimum_monthly_bill' => 'float',
        'price_monthly' => 'float',
        'price_yearly' => 'float',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * Calculate monthly bill based on enrolled students with minimum floor guarantee.
     */
    public function calculateMonthlyBill(int $studentCount): float
    {
        if ($this->pricing_type === 'custom') {
            return 0.00;
        }

        if ($this->pricing_type === 'flat') {
            return (float) ($this->price_monthly ?: 0.00);
        }

        $calculated = $studentCount * ($this->price_per_student ?: 20.00);
        $minimum = (float) ($this->minimum_monthly_bill ?: 2000.00);

        return (float) max($minimum, $calculated);
    }

    /**
     * Check whether this subscription plan permits access to a specific system module.
     */
    public function hasModule(string $module): bool
    {
        $modules = $this->modules ?? [];

        if (in_array('all', $modules, true)) {
            return true;
        }

        return in_array($module, $modules, true);
    }
}
