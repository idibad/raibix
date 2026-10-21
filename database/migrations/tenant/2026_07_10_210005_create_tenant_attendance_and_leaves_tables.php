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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->date('date');
            $table->string('status'); // present, absent, leave, late
            $table->text('remarks')->nullable();
            
            // AI Face Recognition specific tracking
            $table->boolean('marked_by_ai')->default(false);
            $table->decimal('ai_confidence', 5, 2)->nullable(); // e.g. 98.45%
            $table->string('snapshot_url')->nullable(); // Snapshot verified by AI
            
            $table->foreignId('marked_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['student_id', 'date']);
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('attendances');
    }
};
