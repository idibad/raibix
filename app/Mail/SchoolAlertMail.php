<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $recipientName;
    public string $messageBody;
    public array $details;
    public ?string $actionUrl;
    public ?string $actionText;
    public string $alertType; // 'info', 'warning', 'danger', 'success'

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $title,
        string $recipientName,
        string $messageBody,
        array $details = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $alertType = 'info'
    ) {
        $this->title = $title;
        $this->recipientName = $recipientName;
        $this->messageBody = $messageBody;
        $this->details = $details;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->alertType = $alertType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $schoolName = function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant 
            ? tenancy()->tenant->name 
            : config('mail.from.name', 'School Portal');

        return new Envelope(
            from: new Address(
                config('mail.from.address', 'noreply@raibix.com'),
                $schoolName
            ),
            subject: "[{$schoolName}] {$this->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtmlString(),
        );
    }

    /**
     * Build clean responsive HTML email content.
     */
    protected function buildHtmlString(): string
    {
        $schoolName = function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant 
            ? tenancy()->tenant->name 
            : config('mail.from.name', 'School Portal');

        $accentColor = match ($this->alertType) {
            'warning' => '#D97706',
            'danger' => '#DC2626',
            'success' => '#16A34A',
            default => '#1A73E8',
        };

        $badgeBg = match ($this->alertType) {
            'warning' => '#FEF3C7',
            'danger' => '#FEE2E2',
            'success' => '#DCFCE7',
            default => '#E8F0FE',
        };

        $badgeText = match ($this->alertType) {
            'warning' => '#92400E',
            'danger' => '#991B1B',
            'success' => '#166534',
            default => '#1A73E8',
        };

        $detailsHtml = '';
        if (!empty($this->details)) {
            $detailsHtml .= '<table style="width: 100%; margin: 20px 0; border-collapse: collapse; font-size: 13px;">';
            foreach ($this->details as $key => $val) {
                $detailsHtml .= "
                <tr style=\"border-bottom: 1px solid #F1F3F4;\">
                    <td style=\"padding: 10px 8px; color: #5F6368; font-weight: 600; width: 35%;\">" . htmlspecialchars((string) $key) . "</td>
                    <td style=\"padding: 10px 8px; color: #202124; font-weight: 700;\">" . htmlspecialchars((string) $val) . "</td>
                </tr>";
            }
            $detailsHtml .= '</table>';
        }

        $buttonHtml = '';
        if ($this->actionUrl && $this->actionText) {
            $buttonHtml = "
            <div style=\"text-align: center; margin: 30px 0;\">
                <a href=\"{$this->actionUrl}\" style=\"background-color: {$accentColor}; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 14px;\">
                    {$this->actionText}
                </a>
            </div>";
        }

        return "
        <div style=\"font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #E0E2E6; border-radius: 12px; background-color: #ffffff;\">
            <div style=\"border-bottom: 1px solid #ECEFF1; padding-bottom: 16px; margin-bottom: 20px;\">
                <span style=\"display: inline-block; background-color: {$badgeBg}; color: {$badgeText}; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;\">Official Alert</span>
                <h2 style=\"color: #202124; font-size: 18px; margin: 12px 0 4px 0;\">" . htmlspecialchars($this->title) . "</h2>
                <div style=\"font-size: 12px; color: #5F6368;\">{$schoolName} Notifications</div>
            </div>

            <p style=\"color: #202124; font-size: 14px; line-height: 1.5;\">Dear <strong>" . htmlspecialchars($this->recipientName) . "</strong>,</p>
            <p style=\"color: #3C4043; font-size: 14px; line-height: 1.6;\">" . nl2br(htmlspecialchars($this->messageBody)) . "</p>

            {$detailsHtml}
            {$buttonHtml}

            <div style=\"border-top: 1px solid #ECEFF1; padding-top: 16px; margin-top: 30px; font-size: 11px; color: #80868B; line-height: 1.5;\">
                <p style=\"margin: 0;\">This is an automated notification dispatched by <strong>{$schoolName}</strong> via noreply@raibix.com. Please do not reply directly to this email address.</p>
            </div>
        </div>
        ";
    }
}
