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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('cnic')->nullable();
            $table->string('user_type'); // admin, staff, student, parent
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('parents_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('father_name')->nullable();
            $table->string('father_cnic')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_cnic')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('parents_profile')->onDelete('set null');
            $table->string('admission_number')->unique();
            $table->string('roll_number')->nullable();
            $table->string('b_form')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable(); // male, female, other
            $table->date('admission_date')->nullable();
            $table->unsignedBigInteger('current_class_id')->nullable();
            $table->unsignedBigInteger('current_section_id')->nullable();
            $table->string('status')->default('active'); // active, promoted, graduated, dropped
            $table->text('photo_url')->nullable();
            $table->json('photo_embedding')->nullable(); // For AI Face Recognition vectors
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('parents_profile');
        Schema::dropIfExists('users');
    }
};
