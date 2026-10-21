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
        if (!Schema::hasTable('sports_activities')) {
            Schema::create('sports_activities', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('sport_type')->default('Football'); // Football, Cricket, Badminton, Athletics, Basketball, Swimming, Chess
                $table->text('description')->nullable();
                $table->dateTime('schedule_date')->nullable();
                $table->string('venue')->nullable();
                $table->foreignId('coach_teacher_id')->nullable()->constrained('users')->onDelete('set null');
                $table->integer('max_participants')->default(30);
                $table->string('status')->default('Upcoming'); // Upcoming, Ongoing, Completed
                $table->json('participants')->nullable(); // Array of student_ids or student info
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_penalties')) {
            Schema::create('student_penalties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('penalty_type')->default('Disciplinary Warning'); // Late Attendance Penalty, Disciplinary Fine, Library Damage, Uniform Violation, Homework Non-Submission
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->text('reason')->nullable();
                $table->string('status')->default('Issued'); // Issued, Paid, Waived, Resolved
                $table->date('due_date')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('assignments') && !Schema::hasColumn('assignments', 'external_links')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->json('external_links')->nullable()->after('attachment_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports_activities');
        Schema::dropIfExists('student_penalties');
        if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'external_links')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropColumn('external_links');
            });
        }
    }
};
