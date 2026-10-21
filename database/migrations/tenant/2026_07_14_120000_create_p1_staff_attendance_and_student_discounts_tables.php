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
        // 1. Staff daily check-in/out and attendance logs (P1 #7)
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->string('status')->default('present'); // present, absent, leave, late, half_day
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->foreignId('marked_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        // 2. Staff leave requests & approval workflow (P1 #7)
        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('leave_type')->default('casual'); // casual, sick, annual, maternity
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_count')->default(1);
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_remarks')->nullable();
            $table->timestamps();
        });

        // 3. Individual student fee overrides & scholarships (P1 #6)
        Schema::create('student_fee_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('fee_category')->default('tuition'); // tuition, lab, transport, annual
            $table->decimal('discount_percentage', 5, 2)->default(0); // e.g. 20.00 for 20%
            $table->decimal('discount_amount', 10, 2)->default(0); // fixed amount if percentage is 0
            $table->string('override_reason'); // sibling_discount, merit_scholarship, staff_child, financial_aid
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_overrides');
        Schema::dropIfExists('staff_leaves');
        Schema::dropIfExists('staff_attendances');
    }
};
