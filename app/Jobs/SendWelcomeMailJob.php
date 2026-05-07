<?php

namespace App\Jobs;

use App\Mail\WelcomeUserMail;
use App\Models\Configuration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeMailJob implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $password;
    protected ?Configuration $configuration;

    public function __construct($user, $password, $configuration = null)
    {
        $this->user = $user;
        $this->password = $password;
        $this->configuration = $configuration;
    }

    public function handle()
    {
        Mail::to($this->user->email)
            ->send(new WelcomeUserMail($this->user, $this->password, $this->configuration));
    }
}