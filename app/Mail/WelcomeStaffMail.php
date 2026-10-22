<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeStaffMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $password;
    public string $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password, string $loginUrl)
    {
        $this->user = $user;
        $this->password = $password;
        $this->loginUrl = $loginUrl;
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
            subject: 'Welcome to ' . $schoolName . ' - Staff Account Created',
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
            <h2 style=\"color: #1a73e8; margin-bottom: 20px;\">Welcome to {$schoolName}!</h2>
            <p>Dear <strong>{$this->user->name}</strong>,</p>
            <p>Your official staff account has been successfully created. Below are your login credentials to access the school portal:</p>
            
            <div style=\"background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #1a73e8;\">
                <p style=\"margin: 5px 0;\"><strong>Portal URL:</strong> <a href=\"{$this->loginUrl}\">{$this->loginUrl}</a></p>
                <p style=\"margin: 5px 0;\"><strong>Email:</strong> {$this->user->email}</p>
                <p style=\"margin: 5px 0;\"><strong>Temporary Password:</strong> {$this->password}</p>
            </div>
            
            <p style=\"color: #ea4335; font-size: 13px;\"><em>For security reasons, please change your password immediately after logging in.</em></p>
            
            <p style=\"margin-top: 30px;\">Best Regards,<br><strong>Administration Team</strong><br>{$schoolName}</p>
        </div>
        ";
    }
}
