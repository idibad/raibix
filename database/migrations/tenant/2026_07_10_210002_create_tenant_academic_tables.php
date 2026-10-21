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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. 2026-2027 Session
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('name'); // e.g. Term 1, Mid Term, Term 2, Final Term
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nursery, Prep, Grade 1..Grade 10, FSc Part 1, FSc Part 2
            $table->string('level'); // junior, primary, middle, matric, college
            $table->integer('numeric_grade')->default(0); // 0 for Nursery/Prep, 1..12
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('name'); // A, B, C
            $table->string('room_number')->nullable();
            $table->foreignId('class_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('name'); // English, Urdu, Physics Intro, Mathematics, etc.
            $table->string('code')->nullable();
            $table->string('type')->default('theory'); // theory, practical, both
            $table->integer('total_marks')->default(100);
            $table->timestamps();
        });

        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('day_of_week'); // Monday .. Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room_number')->nullable();
            $table->timestamps();
        });

        // Now add foreign key constraints for students.current_class_id and current_section_id
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('current_class_id')->references('id')->on('classes')->onDelete('set null');
            $table->foreign('current_section_id')->references('id')->on('sections')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['current_class_id']);
            $table->dropForeign(['current_section_id']);
        });
        Schema::dropIfExists('timetables');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
    }
};
