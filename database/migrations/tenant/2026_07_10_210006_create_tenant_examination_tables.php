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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->constrained('terms')->onDelete('set null');
            $table->string('name'); // Monthly Test, Mid Term, Send-Up, Final Exam
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->date('exam_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('max_theory_marks', 6, 2)->default(100);
            $table->decimal('max_practical_marks', 6, 2)->default(0);
            $table->decimal('max_viva_marks', 6, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->decimal('theory_marks_obtained', 6, 2)->default(0);
            $table->decimal('practical_marks_obtained', 6, 2)->default(0);
            $table->decimal('viva_marks_obtained', 6, 2)->default(0);
            $table->decimal('total_marks_obtained', 6, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade')->nullable(); // A+, A, B, C, D, F
            $table->decimal('gpa', 4, 2)->nullable();
            $table->text('teacher_remarks')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id', 'subject_id']);
        });

        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->string('grade_name'); // A+, A, B, C, D, F
            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);
            $table->decimal('gpa_point', 4, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_scales');
        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_subjects');
        Schema::dropIfExists('exams');
    }
};
