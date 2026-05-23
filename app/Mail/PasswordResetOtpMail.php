<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $otp;
    public User $user;

    public function __construct(int $otp, User $user)
    {
        $this->otp = $otp;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Password Reset OTP')
            ->view('emails.password-reset-otp')
            ->with([
                'otp' => $this->otp,
                'user' => $this->user,
                'appName' => config('app.name'),
            ]);
    }
}
