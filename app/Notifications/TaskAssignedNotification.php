<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected ?string $url;
    protected array $channels = [];
    protected ?int $userId;
    protected ?int $projectId;
    protected array $emailDetails;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, ?string $url = null, array $channels = [], ?int $userId = null, ?int $projectId = null, array $emailDetails = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->channels = $channels;
        $this->userId = $userId;
        $this->projectId = $projectId;
        $this->emailDetails = $emailDetails;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subjectPrefix = filled(env('APP_NAME', '')) ? env('APP_NAME') . ' - ' : '';
        return (new MailMessage)
            ->subject($subjectPrefix . $this->title)
            ->view('emails.notifications.custom', [
                'title' => $subjectPrefix . $this->title,
                'messageText' => $this->message,
                'url' => $this->url,
                'details' => $this->emailDetails,
            ]);
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }

    public function databaseColumns(object $notifiable): array
    {
        return [
            'user_id' => $this->userId,
            'project_id' => $this->projectId,
        ];
    }
}
