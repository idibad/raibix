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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('pricing_type')->default('per_student')->after('code'); // per_student, flat, custom
            $table->string('tagline')->nullable()->after('name'); // e.g. "Small schools", "Recommended"
            $table->text('summary')->nullable()->after('tagline'); // summary description
            $table->decimal('price_per_student', 10, 2)->default(20.00)->after('price_yearly');
            $table->decimal('minimum_monthly_bill', 10, 2)->default(2000.00)->after('price_per_student');
            $table->json('modules')->nullable()->after('features'); // accessible ERP module keys
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_type',
                'tagline',
                'summary',
                'price_per_student',
                'minimum_monthly_bill',
                'modules',
            ]);
        });
    }
};
