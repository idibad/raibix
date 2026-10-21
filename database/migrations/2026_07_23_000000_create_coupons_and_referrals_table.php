<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('discount_percentage', 5, 2);
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('times_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referrer_tenant_id');
            $table->string('referred_tenant_id');
            $table->string('status')->default('pending'); // pending, rewarded
            $table->timestamps();
            
            $table->foreign('referrer_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('referred_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('coupons');
    }
};
