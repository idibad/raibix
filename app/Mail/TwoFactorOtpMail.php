<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $otp;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'noreply@raibix.com'),
                'Raibix Security'
            ),
            subject: 'Your Raibix Super Admin Verification Code',
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
        $userName = e($this->user->name);
        $code = e($this->otp);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FAFAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #111318;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #FAFAFC; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 480px; background-color: #FFFFFF; border: 1px solid #E6E8EE; border-radius: 16px; padding: 36px 32px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #111318;">Raibix</h2>
                            <p style="margin: 4px 0 0 0; font-size: 13px; color: #5B6270;">Root Security Verification</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 16px;">
                            <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #111318;">Hello <strong>{$userName}</strong>,</p>
                            <p style="margin: 8px 0 0 0; font-size: 13px; line-height: 1.5; color: #5B6270;">
                                A sign-in attempt was initiated for your Super Admin account. Use the verification code below to complete your authentication:
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 24px 0;">
                            <div style="display: inline-block; background-color: #EEF3FF; border: 1px solid #D6E4FF; border-radius: 12px; padding: 16px 36px; font-family: monospace; font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #2F6BFF;">
                                {$code}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 24px;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #5B6270;">
                                This security code is valid for <strong>10 minutes</strong>. If you did not request this login, please secure your credentials immediately.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #E6E8EE; padding-top: 20px;" align="center">
                            <p style="margin: 0; font-size: 11px; color: #8F94A0;">
                                &copy; 2026 Raibix Platform. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
