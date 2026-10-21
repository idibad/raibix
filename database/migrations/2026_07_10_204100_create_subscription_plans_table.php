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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Starter, Growth, Enterprise
            $table->string('code')->unique(); // starter, growth, enterprise
            $table->integer('max_students')->default(500);
            $table->integer('max_teachers')->default(50);
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->integer('api_rate_limit')->default(100);
            $table->integer('storage_limit_mb')->default(5000);
            $table->integer('grace_period_days')->default(3);
            $table->integer('trial_days')->default(14);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('payment_gateway')->default('jazzcash'); // jazzcash, meezan, manual
            $table->string('transaction_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->default('active'); // active, expired, pending, suspended
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
