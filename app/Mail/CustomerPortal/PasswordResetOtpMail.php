<?php

namespace App\Mail\CustomerPortal;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly string $companyName,
        public readonly int $expiryMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Customer Portal – Password Reset Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-portal.password-reset-otp',
            with: [
                'otp' => $this->otp,
                'companyName' => $this->companyName,
                'expiryMinutes' => $this->expiryMinutes,
                'appName' => config('app.name', 'Project Management'),
            ],
        );
    }
}
