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
        $connection = config('tenancy.database.central_connection', config('database.default'));

        Schema::connection($connection)->create('platform_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('institution')->nullable();
            $table->text('message')->nullable();
            $table->string('type')->default('contact'); // 'contact' or 'expert_call'
            $table->string('status')->default('new'); // 'new', 'contacted', 'archived'
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('tenancy.database.central_connection', config('database.default'));
        Schema::connection($connection)->dropIfExists('platform_inquiries');
    }
};
