<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ProjectNotificationLog;
use Illuminate\Support\Carbon;

class ProjectNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected ?string $url;
    protected array $channels = [];
    protected ?int $projectId;
    protected array $emailDetails;
    public ?int $logId;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, ?string $url = null, array $channels = [], ?int $projectId = null, array $emailDetails = [], ?int $logId = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->channels = $channels;
        $this->projectId = $projectId;
        $this->emailDetails = $emailDetails;
        $this->logId = $logId;
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

    public function toBroadcast(object $notifiable): BroadcastMessage
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
            'project_id' => $this->projectId,
            // Fallback for user_id so it does not fail if database schema expects it
            'user_id' => $notifiable->id,
        ];
    }

    /**
     * Handle notification sent event equivalent by tracking delivery status.
     * Laravel 10/11 handles sending cleanly, but to mark the log as sent,
     * we can do this when the job completes successfully.
     * Wait, ShouldQueue handles the job. A clean way is to mark it sent in
     * the command or through an event listener. Since it's batched, the safest
     * is an event listener for NotificationSent.
     */
}
