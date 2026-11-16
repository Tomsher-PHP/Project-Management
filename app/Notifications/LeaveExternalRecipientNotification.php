<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveExternalRecipientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;

    protected string $message;

    protected ?string $url;

    protected array $emailDetails;

    public function __construct(
        string $title,
        string $message,
        ?string $url = null,
        array $emailDetails = []
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->emailDetails = $emailDetails;

        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        $subject = filled($appName)
            ? $appName . ' - ' . $this->title
            : $this->title;

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notifications.custom', [
                'title' => $subject,
                'messageText' => $this->message,
                'url' => $this->url,
                'details' => $this->emailDetails,
            ]);
    }
}
