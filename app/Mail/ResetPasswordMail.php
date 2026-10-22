<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $schoolName = function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant 
            ? tenancy()->tenant->name 
            : config('app.name', 'Excellence School Portal');

        return new Envelope(
            from: new Address(
                config('mail.from.address', 'noreply@raibix.com'),
                $schoolName
            ),
            subject: $schoolName . ' - Reset Your Password',
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
        $schoolName = function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant ? tenancy()->tenant->name : config('app.name', 'Excellence School Portal');
        return "
        <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #ffffff;\">
            <h2 style=\"color: #1a73e8; margin-bottom: 20px;\">Reset Your Password</h2>
            <p>Dear <strong>{$this->user->name}</strong>,</p>
            <p>We received a request to reset your password for your school portal account. Click the button below to set a new password:</p>
            
            <div style=\"text-align: center; margin: 30px 0;\">
                <a href=\"{$this->resetUrl}\" style=\"background-color: #1a73e8; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;\">Reset Password</a>
            </div>
            
            <p>Or copy and paste this URL into your browser:</p>
            <p style=\"word-break: break-all;\"><a href=\"{$this->resetUrl}\">{$this->resetUrl}</a></p>
            
            <p style=\"color: #ea4335; font-size: 13px;\"><em>If you did not request a password reset, please ignore this email. This link is valid for 60 minutes.</em></p>
            
            <p style=\"margin-top: 30px;\">Best Regards,<br><strong>System IT Support</strong><br>{$schoolName}</p>
        </div>
        ";
    }
}
