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
     * Determine if the notification should be sent.
     * Prevents stale queued timeline and hosting notifications from being delivered late.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($this->logId === null) {
            return true;
        }

        $log = ProjectNotificationLog::find($this->logId);
        if (!$log) {
            return false;
        }

        $todayStr = Carbon::today(config('app.timezone'))->toDateString();

        // 1. Freshness check for timeline notifications
        if ($log->link_type === \App\Models\ProjectTimeline::class) {
            // Verify that the timeline exists
            $timeline = \App\Models\ProjectTimeline::find($log->link_id);
            if (!$timeline) {
                if ($log->status !== ProjectNotificationLog::STATUS_FAILED && $log->status !== ProjectNotificationLog::STATUS_SENT) {
                    $log->update(['status' => ProjectNotificationLog::STATUS_FAILED]);
                }
                return false;
            }

            $scheduledDateStr = $log->scheduled_for
                ? Carbon::parse($log->scheduled_for, config('app.timezone'))->toDateString()
                : null;

            // If scheduled date is not today in app timezone, notification is stale
            if ($scheduledDateStr !== $todayStr) {
                if ($log->status !== ProjectNotificationLog::STATUS_FAILED && $log->status !== ProjectNotificationLog::STATUS_SENT) {
                    $log->update([
                        'status' => ProjectNotificationLog::STATUS_FAILED,
                    ]);
                }

                return false;
            }
        }

        // 2. Freshness and validity check for hosting notifications
        if ($log->link_type === \App\Models\ProjectHostingRecord::class) {
            $hostingRecord = \App\Models\ProjectHostingRecord::with('hostingServiceStatus')->find($log->link_id);
            if (!$hostingRecord) {
                if ($log->status !== ProjectNotificationLog::STATUS_FAILED && $log->status !== ProjectNotificationLog::STATUS_SENT) {
                    $log->update(['status' => ProjectNotificationLog::STATUS_FAILED]);
                }
                return false;
            }

            // Exclude cancelled or terminated hosting records
            if ($hostingRecord->hostingServiceStatus && in_array($hostingRecord->hostingServiceStatus->code, ['cancelled', 'terminated'])) {
                if ($log->status !== ProjectNotificationLog::STATUS_FAILED && $log->status !== ProjectNotificationLog::STATUS_SENT) {
                    $log->update(['status' => ProjectNotificationLog::STATUS_FAILED]);
                }
                return false;
            }

            $scheduledDateStr = $log->scheduled_for
                ? Carbon::parse($log->scheduled_for, config('app.timezone'))->toDateString()
                : null;

            // If scheduled date is not today in app timezone, notification is stale
            if ($scheduledDateStr !== $todayStr) {
                if ($log->status !== ProjectNotificationLog::STATUS_FAILED && $log->status !== ProjectNotificationLog::STATUS_SENT) {
                    $log->update([
                        'status' => ProjectNotificationLog::STATUS_FAILED,
                    ]);
                }
                return false;
            }

            // Check if record has valid expiry date
            $expiryDate = $hostingRecord->expiry_date
                ? Carbon::parse($hostingRecord->expiry_date, config('app.timezone'))->startOfDay()
                : null;

            if (!$expiryDate) {
                if ($log->status !== ProjectNotificationLog::STATUS_FAILED && $log->status !== ProjectNotificationLog::STATUS_SENT) {
                    $log->update(['status' => ProjectNotificationLog::STATUS_FAILED]);
                }
                return false;
            }

            // Verify that occasion is still valid for current expiry date (e.g. not renewed in the meantime)
            $occasion = $log->reminder_occasion;
            $isOccasionValid = false;

            if ($occasion === 'on_expiry_date') {
                $isOccasionValid = ($expiryDate->toDateString() === $todayStr);
            } elseif ($occasion === '1_day_after') {
                $isOccasionValid = ($expiryDate->copy()->addDays(1)->toDateString() === $todayStr);
            } elseif (str_starts_with((string) $occasion, 'days_before_')) {
                $days = (int) substr($occasion, strlen('days_before_'));
                $isOccasionValid = ($expiryDate->copy()->subDays($days)->toDateString() === $todayStr);
            } elseif ($occasion === '1_day_before') {
                $isOccasionValid = ($expiryDate->copy()->subDays(1)->toDateString() === $todayStr);
            }

            if (!$isOccasionValid) {
                if ($log->status !== ProjectNotificationLog::STATUS_FAILED && $log->status !== ProjectNotificationLog::STATUS_SENT) {
                    $log->update(['status' => ProjectNotificationLog::STATUS_FAILED]);
                }
                return false;
            }
        }

        return true;
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

    public function databaseColumns(?object $notifiable = null): array
    {
        return [
            'project_id' => $this->projectId,
            // Fallback for user_id so it does not fail if database schema expects it or if notifiable is anonymous / null
            'user_id' => is_object($notifiable) ? ($notifiable->id ?? null) : null,
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
