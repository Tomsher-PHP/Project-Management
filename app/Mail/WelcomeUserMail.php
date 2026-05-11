<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    public $user;
    public $password;
    public $configuration;

    public function __construct($user, $password, $configuration = null)
    {
        $this->user = $user;
        $this->password = $password;
        $this->configuration = $configuration;
    }

     public function build()
    {
        return $this->subject('Welcome to ' . ($this->configuration->company_name ?? 'Our Company'))
            ->view('emails.welcome-user')
            ->with([
                'user' => $this->user,
                'password' => $this->password,
                'company' => $this->configuration,
            ]);
    }
}