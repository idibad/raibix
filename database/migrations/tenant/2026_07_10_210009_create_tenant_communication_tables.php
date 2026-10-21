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
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('target_audience'); // all, teachers, students, parents, class_specific
            $table->unsignedBigInteger('target_class_id')->nullable();
            $table->date('publish_date');
            $table->boolean('send_sms_alert')->default(false);
            $table->boolean('send_whatsapp_alert')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // twilio, urdubit, parisms, clicksat
            $table->string('recipient_phone');
            $table->text('message_body');
            $table->string('status'); // queued, sent, failed, delivered
            $table->text('gateway_response')->nullable();
            $table->string('event_type')->nullable(); // attendance_absent, fee_challan, exam_result, notice
            $table->timestamps();
        });

        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('cloud_api'); // cloud_api, twilio_whatsapp
            $table->string('recipient_phone');
            $table->string('template_name')->nullable();
            $table->text('message_body');
            $table->string('status'); // queued, sent, failed, read
            $table->text('gateway_response')->nullable();
            $table->string('event_type')->nullable(); // attendance_absent, fee_challan, exam_result, notice
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('notices');
    }
};
