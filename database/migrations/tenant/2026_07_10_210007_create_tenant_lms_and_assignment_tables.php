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
        Schema::create('lms_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_type'); // pdf, ppt, video, recorded_lecture, note, online_class_link
            $table->text('file_url');
            $table->boolean('is_downloadable')->default(true);
            $table->timestamps();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->date('due_date');
            $table->decimal('total_marks', 6, 2)->default(100);
            $table->text('attachment_url')->nullable();
            $table->boolean('ai_evaluation_enabled')->default(true); // AI evaluation for short answers/MCQs
            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->text('submission_text')->nullable();
            $table->text('file_url')->nullable();
            $table->decimal('obtained_marks', 6, 2)->nullable();
            $table->text('teacher_remarks')->nullable();
            
            // AI Evaluation columns
            $table->boolean('ai_graded')->default(false);
            $table->decimal('ai_suggested_marks', 6, 2)->nullable();
            $table->text('ai_feedback')->nullable();
            
            $table->string('status')->default('submitted'); // submitted, graded, returned
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('lms_materials');
    }
};
