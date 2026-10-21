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
         Schema::table('users', function (Blueprint $table) {
             if (!Schema::hasColumn('users', 'qualification')) {
                 $table->string('qualification')->nullable()->after('user_type');
             }
             if (!Schema::hasColumn('users', 'last_login_at')) {
                 $table->timestamp('last_login_at')->nullable()->after('remember_token');
             }
             if (!Schema::hasColumn('users', 'deleted_at')) {
                 $table->softDeletes();
             }
         });

         Schema::table('students', function (Blueprint $table) {
             if (!Schema::hasColumn('students', 'deleted_at')) {
                 $table->softDeletes();
             }
         });

         Schema::create('student_status_logs', function (Blueprint $table) {
             $table->id();
             $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
             $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
             $table->string('old_status')->nullable();
             $table->string('new_status');
             $table->text('reason')->nullable();
             $table->timestamps();
         });

         Schema::create('student_documents', function (Blueprint $table) {
             $table->id();
             $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
             $table->string('title'); // e.g. Birth Certificate, Previous School Leaving Certificate, Medical Form
             $table->string('document_type')->default('general');
             $table->text('file_url');
             $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->onDelete('set null');
             $table->timestamps();
         });

         Schema::create('admission_communications', function (Blueprint $table) {
             $table->id();
             $table->foreignId('inquiry_id')->nullable()->constrained('admission_inquiries')->onDelete('cascade');
             $table->foreignId('application_id')->nullable()->constrained('admission_applications')->onDelete('cascade');
             $table->string('communication_type')->default('call'); // call, email, sms, whatsapp
             $table->text('message');
             $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->onDelete('set null');
             $table->timestamps();
         });

         Schema::create('admission_settings', function (Blueprint $table) {
             $table->id();
             $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('cascade');
             $table->boolean('is_window_open')->default(true);
             $table->integer('seat_capacity')->default(40);
             $table->decimal('application_fee', 8, 2)->default(1500.00);
             $table->decimal('testing_fee', 8, 2)->default(1000.00);
             $table->timestamps();
         });
     }

     /**
      * Reverse the migrations.
      */
     public function down(): void
     {
         Schema::dropIfExists('admission_settings');
         Schema::dropIfExists('admission_communications');
         Schema::dropIfExists('student_documents');
         Schema::dropIfExists('student_status_logs');

         Schema::table('students', function (Blueprint $table) {
             $table->dropSoftDeletes();
         });

         Schema::table('users', function (Blueprint $table) {
             $table->dropColumn(['qualification', 'last_login_at']);
             $table->dropSoftDeletes();
         });
     }
};
