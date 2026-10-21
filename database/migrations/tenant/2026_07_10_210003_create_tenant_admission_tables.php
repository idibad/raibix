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
        Schema::create('admission_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_code')->unique(); // INQ-2026-0001
            $table->string('student_name');
            $table->string('previous_school')->nullable();
            $table->foreignId('desired_class_id')->constrained('classes')->onDelete('cascade');
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('parent_email')->nullable();
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status')->default('new'); // new, contacted, scheduled, closed
            $table->timestamps();
        });

        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_code')->unique(); // APP-2026-0001
            $table->foreignId('inquiry_id')->nullable()->constrained('admission_inquiries')->onDelete('set null');
            $table->string('student_name');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('father_name');
            $table->string('father_cnic')->nullable();
            $table->string('father_phone');
            $table->string('mother_name')->nullable();
            $table->string('mother_cnic')->nullable();
            $table->json('documents')->nullable(); // B-Form, Birth Cert, Parent CNIC, Result Cards, etc.
            $table->date('test_date')->nullable();
            $table->decimal('test_marks_obtained', 8, 2)->nullable();
            $table->decimal('test_total_marks', 8, 2)->default(100);
            $table->string('merit_status')->default('pending'); // pending, approved, rejected, waiting
            $table->string('admission_status')->default('applied'); // applied, test_scheduled, merit_decided, fee_pending, enrolled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
        Schema::dropIfExists('admission_inquiries');
    }
};
