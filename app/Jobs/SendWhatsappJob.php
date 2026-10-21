<?php

namespace App\Jobs;

use App\Models\WhatsappLog;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $phone;
    public string $message;
    public string $eventType;
    public ?string $templateName;
    public ?string $tenantId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $message, string $eventType = 'general', ?string $templateName = null, ?string $tenantId = null)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->eventType = $eventType;
        $this->templateName = $templateName;
        $this->tenantId = $tenantId ?: (function_exists('tenant') && tenant() ? tenant()->getTenantKey() : null);
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): WhatsappLog
    {
        if ($this->tenantId && (!function_exists('tenant') || !tenant() || tenant()->getTenantKey() !== $this->tenantId)) {
            tenancy()->initialize($this->tenantId);
        }

        return $notificationService->sendWhatsapp($this->phone, $this->message, $this->eventType, $this->templateName);
    }
}
