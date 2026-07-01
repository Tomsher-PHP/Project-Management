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
        $subject = $this->contextualEmailSubject($notifiable) ?? $this->title;

        return (new MailMessage)
            ->subject($subjectPrefix . $subject)
            ->view('emails.notifications.custom', [
                'title' => $subjectPrefix . $subject,
                'messageText' => $this->message,
                'url' => $this->url,
                'details' => $this->emailDetails,
            ]);
    }

    private function contextualEmailSubject(object $notifiable): ?string
    {
        $recipientId = (int) $notifiable->getKey();
        $actor = $this->personalizedName($recipientId, 'actor', 'A team member');
        $assignee = $this->personalizedName($recipientId, 'assignee', 'Unassigned');
        $task = $this->emailSubjectContext['task_name'] ?? 'Task';

        return match ($this->emailSubjectContext['type'] ?? null) {
            'task_assignment' => ucfirst("{$actor} assigned {$task} to {$assignee}"),
            'break_request_submitted' => "Break Work Request Submitted by {$actor}",
            'break_request_approved' => "Break Work Request Approved by {$actor} ({$assignee})",
            'break_request_rejected' => "Break Work Request Rejected by {$actor} ({$assignee})",
            'task_time_log_change_request_submitted' => "Task Time Log Change Request Submitted by {$actor}",
            'task_time_log_change_request_approved' => "Task Time Log Change Request Approved by {$actor} ({$assignee})",
            'task_time_log_change_request_rejected' => "Task Time Log Change Request Rejected by {$actor} ({$assignee})",
            default => null,
        };
    }

    private function personalizedName(int $recipientId, string $role, string $fallback): string
    {
        return $recipientId === (int) ($this->emailSubjectContext["{$role}_id"] ?? 0)
            ? 'you'
            : ($this->emailSubjectContext["{$role}_name"] ?? $fallback);
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
