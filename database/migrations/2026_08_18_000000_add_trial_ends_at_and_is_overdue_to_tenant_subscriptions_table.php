<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_subscriptions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
            if (!Schema::hasColumn('tenant_subscriptions', 'is_overdue')) {
                $table->boolean('is_overdue')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            try {
                Schema::table('tenant_subscriptions', function (Blueprint $table) {
                    $table->dropColumn('trial_ends_at');
                    $table->dropColumn('is_overdue');
                });
            } catch (\Exception $e) {
                // Fail silently if not fully supported in local sqlite
            }
        });
    }
};
