<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send instant absence alert SMS and WhatsApp message to parent when student is marked absent.
     */
    public function sendAbsenceAlert(Student $student, Attendance $attendance): bool
    {
        if (!$student->parent || !$student->parent->user) {
            return false;
        }

        $parentPhone = $student->parent->user->phone ?? $student->parent->father_phone ?? null;
        if (!$parentPhone) {
            return false;
        }

        $studentName = $student->user ? $student->user->name : 'Your child';
        $sectionName = $student->currentSection ? $student->currentSection->name : 'Class';
        $className = $student->currentClass ? $student->currentClass->name : '';
        $dateFormatted = $attendance->date ? $attendance->date->format('d-M-Y') : date('d-M-Y');

        $schoolName = function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant ? tenancy()->tenant->name : config('app.name', 'Excellence High School');
        $message = "Your child {$studentName} (Roll # {$student->roll_number}) was absent today ({$dateFormatted}) in {$className}-{$sectionName}. - Principal, {$schoolName}";

        // Dispatch SMS
        $this->sendSms($parentPhone, $message, 'attendance_absent');

        // Dispatch WhatsApp
        $this->sendWhatsapp($parentPhone, $message, 'attendance_absent', 'absence_alert_template');

        return true;
    }

    /**
     * Mask sensitive telephone numbers for audit and log outputs (e.g. 0300****123).
     */
    public function maskPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        $length = strlen($clean);
        if ($length <= 5) {
            return str_repeat('*', $length);
        }

        return substr($clean, 0, 4) . str_repeat('*', max(2, $length - 6)) . substr($clean, -2);
    }

    /**
     * Sanitize message contents for logging to strip OTPs, PINs, and passwords.
     */
    public function sanitizeMessageForLogging(string $message): string
    {
        // Mask 4-6 digit numeric OTPs/PINs
        $sanitized = preg_replace('/\b\d{4,6}\b/', '****', $message);

        return substr($sanitized, 0, 80) . (strlen($sanitized) > 80 ? '...' : '');
    }

    /**
     * Asynchronously queue an SMS for background dispatch.
     */
    public function queueSms(string $phone, string $message, string $eventType = 'general'): \Illuminate\Foundation\Bus\PendingDispatch
    {
        return \App\Jobs\SendSmsJob::dispatch($phone, $message, $eventType);
    }

    /**
     * Asynchronously queue a WhatsApp message for background dispatch.
     */
    public function queueWhatsapp(string $phone, string $message, string $eventType = 'general', ?string $templateName = null): \Illuminate\Foundation\Bus\PendingDispatch
    {
        return \App\Jobs\SendWhatsappJob::dispatch($phone, $message, $eventType, $templateName);
    }

    /**
     * Send multi-gateway pluggable SMS (Twilio, UrduBit, PariSMS, Clicksat).
     */
    public function sendSms(string $phone, string $message, string $eventType = 'general'): SmsLog
    {
        $provider = config('services.sms.provider', 'parisms'); // default regional Pakistani provider
        $status = 'sent';
        $gatewayResponse = ['status' => 'success', 'mock' => true];
        $maskedPhone = $this->maskPhoneNumber($phone);
        $sanitizedMsg = $this->sanitizeMessageForLogging($message);

        try {
            if ($provider === 'twilio' && config('services.twilio.sid')) {
                // Real Twilio API invocation
                $response = Http::withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                    ->asForm()->post("https://api.twilio.com/2010-04-01/Accounts/" . config('services.twilio.sid') . "/Messages.json", [
                        'From' => config('services.twilio.from'),
                        'To' => $phone,
                        'Body' => $message,
                    ]);
                $gatewayResponse = $response->json();
                $status = $response->successful() ? 'delivered' : 'failed';
            } elseif ($provider === 'urdubit' && config('services.urdubit.api_key')) {
                // Real UrduBit API invocation
                $response = Http::get("https://api.urdubit.com/sms/send", [
                    'key' => config('services.urdubit.api_key'),
                    'sender' => config('services.urdubit.mask', config('app.name', 'EXCELLENCE')),
                    'mobile' => $phone,
                    'message' => $message,
                ]);
                $gatewayResponse = $response->json();
                $status = $response->successful() ? 'sent' : 'failed';
            } else {
                // Regional gateway (PariSMS / Clicksat) mock / fallback log
                Log::info("SMS Dispatched via [{$provider}] to [{$maskedPhone}]: {$sanitizedMsg}");
            }
        } catch (\Exception $e) {
            $status = 'failed';
            $gatewayResponse = ['error' => $e->getMessage()];
            Log::error("SMS Gateway Error ({$provider}) for [{$maskedPhone}]: " . $e->getMessage());
        }

        return SmsLog::create([
            'provider' => $provider,
            'recipient_phone' => $phone,
            'message_body' => $message,
            'status' => $status,
            'gateway_response' => json_encode($gatewayResponse),
            'event_type' => $eventType,
        ]);
    }

    /**
     * Send WhatsApp notification via WhatsApp Cloud API or Twilio WhatsApp.
     */
    public function sendWhatsapp(string $phone, string $message, string $eventType = 'general', string $templateName = null): WhatsappLog
    {
        $provider = config('services.whatsapp.provider', 'cloud_api');
        $status = 'sent';
        $gatewayResponse = ['status' => 'success', 'mock' => true];
        $maskedPhone = $this->maskPhoneNumber($phone);
        $sanitizedMsg = $this->sanitizeMessageForLogging($message);

        try {
            if ($provider === 'cloud_api' && config('services.whatsapp.token')) {
                $phoneFormatted = preg_replace('/[^0-9]/', '', $phone);
                $response = Http::withToken(config('services.whatsapp.token'))
                    ->post("https://graph.facebook.com/v19.0/" . config('services.whatsapp.phone_id') . "/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $phoneFormatted,
                        'type' => 'text',
                        'text' => ['body' => $message],
                    ]);
                $gatewayResponse = $response->json();
                $status = $response->successful() ? 'sent' : 'failed';
            } else {
                Log::info("WhatsApp Dispatched via [{$provider}] to [{$maskedPhone}]: {$sanitizedMsg}");
            }
        } catch (\Exception $e) {
            $status = 'failed';
            $gatewayResponse = ['error' => $e->getMessage()];
            Log::error("WhatsApp Gateway Error ({$provider}) for [{$maskedPhone}]: " . $e->getMessage());
        }

        return WhatsappLog::create([
            'provider' => $provider,
            'recipient_phone' => $phone,
            'template_name' => $templateName,
            'message_body' => $message,
            'status' => $status,
            'gateway_response' => json_encode($gatewayResponse),
            'event_type' => $eventType,
        ]);
    }
}
