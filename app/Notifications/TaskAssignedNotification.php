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
    protected array $emailSubjectContext;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, ?string $url = null, array $channels = [], ?int $userId = null, ?int $projectId = null, array $emailDetails = [], array $emailSubjectContext = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->channels = $channels;
        $this->userId = $userId;
        $this->projectId = $projectId;
        $this->emailDetails = $emailDetails;
        $this->emailSubjectContext = $emailSubjectContext;
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
        $subject = $this->taskAssignmentSubject($notifiable) ?? $this->title;

        return (new MailMessage)
            ->subject($subjectPrefix . $subject)
            ->view('emails.notifications.custom', [
                'title' => $subjectPrefix . $subject,
                'messageText' => $this->message,
                'url' => $this->url,
                'details' => $this->emailDetails,
            ]);
    }

    private function taskAssignmentSubject(object $notifiable): ?string
    {
        if (($this->emailSubjectContext['type'] ?? null) !== 'task_assignment') {
            return null;
        }

        $recipientId = (int) $notifiable->getKey();
        $actor = $recipientId === (int) ($this->emailSubjectContext['actor_id'] ?? 0)
            ? 'you'
            : ($this->emailSubjectContext['actor_name'] ?? 'A team member');
        $assignee = $recipientId === (int) ($this->emailSubjectContext['assignee_id'] ?? 0)
            ? 'you'
            : ($this->emailSubjectContext['assignee_name'] ?? 'Unassigned');
        $task = $this->emailSubjectContext['task_name'] ?? 'Task';

        return ucfirst("{$actor} assigned {$task} to {$assignee}");
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
