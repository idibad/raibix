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
        Schema::create('saas_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name')->default('LMS Cloud Pakistan (Central SaaS)');
            $table->string('contact_email')->default('admin@lmscloud.pk');
            $table->string('support_phone')->default('+92 300 1234567');
            $table->boolean('maintenance_mode')->default(false);
            $table->boolean('public_onboarding')->default(true);
            $table->boolean('require_email_verification')->default(false);
            $table->integer('default_trial_days')->default(14);
            $table->boolean('tax_exemption_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('saas_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_title');
            $table->text('description');
            $table->string('event_type')->default('info'); // success, info, warning, error
            $table->string('actor_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('saas_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique(); // e.g. REV-9921
            $table->string('school_name');
            $table->string('tenant_id');
            $table->string('plan_name');
            $table->decimal('amount', 10, 2);
            $table->string('gateway');
            $table->string('reference_no');
            $table->string('secure_hash')->nullable();
            $table->string('status')->default('paid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_transactions');
        Schema::dropIfExists('saas_activity_logs');
        Schema::dropIfExists('saas_settings');
    }
};
