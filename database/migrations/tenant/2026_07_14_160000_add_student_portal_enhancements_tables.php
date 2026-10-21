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
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->string('submission_file_name')->nullable();
            $table->boolean('appeal_requested')->default(false);
            $table->text('appeal_reason')->nullable();
            $table->string('appeal_status')->nullable(); // pending, reviewed, rejected
            $table->foreignId('reviewed_by_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->string('category')->default('General Circular');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('transport_route_id')->nullable();
            $table->string('library_card_number')->nullable();
        });

        Schema::create('notice_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained('notices')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();
            $table->unique(['notice_id', 'student_id']);
        });

        Schema::create('assignment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('message');
            $table->string('sender_type')->default('student'); // student, teacher
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_questions');
        Schema::dropIfExists('notice_reads');
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['transport_route_id', 'library_card_number']);
        });
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['category']);
        });
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'submission_file_name',
                'appeal_requested',
                'appeal_reason',
                'appeal_status',
                'reviewed_by_teacher_id',
                'reviewed_at',
            ]);
        });
    }
};
