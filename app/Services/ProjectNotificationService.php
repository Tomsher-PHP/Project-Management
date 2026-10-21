<?php

namespace App\Services;

use App\Models\ProjectNotificationSetting;
use App\Models\ProjectNotificationLog;
use App\Models\ProjectTimeline;
use App\Notifications\ProjectNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class ProjectNotificationService
{
    /**
     * Process timeline ending soon notifications based on global settings.
     */
    public function processTimelineEndingSoon(): void
    {
        $setting = ProjectNotificationSetting::with(['users', 'emailRecipients'])
            ->where('notification_type', ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON)
            ->first();

        // 1. Requirements: Send nothing when the setting does not exist or is disabled
        if (!$setting || !$setting->is_enabled) {
            return;
        }

        // 3. Recipients: Retrieve and deduplicate recipients
        $recipients = $this->getRecipients($setting);
        if ($recipients['users']->isEmpty() && empty($recipients['additional_emails'])) {
            return;
        }

        // 2. Notification Delivery Dates: Evaluate active timelines
        $today = Carbon::today(config('app.timezone'));
        $daysBefore = max(1, (int) $setting->days_before);

        // Fetch eligible active timelines with valid end dates and active projects
        $timelines = ProjectTimeline::with('project')
            ->where('status', ProjectTimeline::STATUS_ACTIVE)
            ->whereNotNull('end_date')
            ->whereHas('project', function ($query) {
                // Respect project scoping & soft deletion
                $query->whereNull('deleted_at');
            })
            ->get();

        foreach ($timelines as $timeline) {
            $occasion = $this->determineDueOccasionForToday($timeline, $today, $daysBefore);

            if ($occasion !== null) {
                $this->dispatchNotificationForTimeline($timeline, $setting, $today->toDateString(), $occasion, $recipients);
            }
        }
    }

    /**
     * Determine if today matches any notification occasion for the given timeline.
     * Returns the occasion key/name if due today, or null if not due today.
     *
     * Occasions:
     * 1. The configured number of days before the timeline expires, using days_before.
     * 2. One day before the expiry date.
     * 3. On the expiry date.
     * 4. One day after the expiry date.
     *
     * If notification dates overlap (e.g. days_before = 1), only one notification is sent for that date.
     */
    public function determineDueOccasionForToday(ProjectTimeline $timeline, Carbon $today, int $daysBefore): ?string
    {
        if (!$timeline->end_date) {
            return null;
        }

        $endDate = Carbon::parse($timeline->end_date, config('app.timezone'))->startOfDay();
        $todayDate = $today->copy()->startOfDay();

        // Calculate occasion dates
        $configuredDate = $endDate->copy()->subDays($daysBefore)->toDateString();
        $oneDayBeforeDate = $endDate->copy()->subDays(1)->toDateString();
        $expiryDate = $endDate->toDateString();
        $oneDayAfterDate = $endDate->copy()->addDays(1)->toDateString();

        $todayStr = $todayDate->toDateString();

        // Match with precedence (if days_before == 1, configuredDate == oneDayBeforeDate; return single occasion)
        if ($todayStr === $configuredDate) {
            return 'days_before_' . $daysBefore;
        }

        if ($todayStr === $oneDayBeforeDate) {
            return '1_day_before';
        }

        if ($todayStr === $expiryDate) {
            return 'on_expiry_date';
        }

        if ($todayStr === $oneDayAfterDate) {
            return '1_day_after';
        }

        return null;
    }

    /**
     * Dispatch notification for timeline if not already queued or sent today.
     */
    protected function dispatchNotificationForTimeline(ProjectTimeline $timeline, ProjectNotificationSetting $setting, string $todayDateStr, string $occasion, array $recipients): void
    {
        DB::transaction(function () use ($timeline, $setting, $todayDateStr, $occasion, $recipients) {
            // Lock and check if a log already exists for this timeline and date
            $existingLog = ProjectNotificationLog::where('link_type', ProjectTimeline::class)
                ->where('link_id', $timeline->id)
                ->where('scheduled_for', $todayDateStr)
                ->lockForUpdate()
                ->first();

            if ($existingLog) {
                // Already dispatched or queued for this date
                return;
            }

            try {
                $log = ProjectNotificationLog::create([
                    'project_notification_setting_id' => $setting->id,
                    'link_type' => ProjectTimeline::class,
                    'link_id' => $timeline->id,
                    'scheduled_for' => $todayDateStr,
                    'reminder_occasion' => $occasion,
                    'status' => ProjectNotificationLog::STATUS_QUEUED,
                ]);

                $this->sendNotification($timeline, $recipients, $log->id, $occasion);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Handle concurrent duplicate attempts safely
                return;
            }
        });
    }

    /**
     * Retrieve and deduplicate recipients between PMS users and additional emails.
     *
     * @return array{users: EloquentCollection<int, User>, additional_emails: array<int, string>}
     */
    public function getRecipients(ProjectNotificationSetting $setting): array
    {
        // 1. PMS Users: Retrieve selected users through existing relationship
        $users = $setting->users()
            ->whereNotNull('users.email')
            ->where('users.email', '!=', '')
            ->get();

        $seenEmails = [];
        $validUsers = new EloquentCollection();

        foreach ($users as $user) {
            $normalizedEmail = strtolower(trim((string) $user->email));
            if (filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
                $seenEmails[$normalizedEmail] = true;
                $validUsers->push($user);
            }
        }

        // 2. Additional Email Recipients: Retrieve records where email is populated
        $additionalEmails = [];
        $emailRecords = $setting->emailRecipients()->get();

        foreach ($emailRecords as $record) {
            $rawEmail = (string) $record->email;
            $normalizedEmail = strtolower(trim($rawEmail));

            if (filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) && !isset($seenEmails[$normalizedEmail])) {
                $seenEmails[$normalizedEmail] = true;
                $additionalEmails[] = $normalizedEmail;
            }
        }

        return [
            'users' => $validUsers,
            'additional_emails' => $additionalEmails,
        ];
    }

    /**
     * Send email notifications using the ProjectNotification class.
     */
    protected function sendNotification(ProjectTimeline $timeline, array $recipients, int $logId, string $occasion): void
    {
        $projectName = $timeline->project ? $timeline->project->name : 'Project';
        $timelineName = $timeline->name;
        $endDate = $timeline->end_date ? $timeline->end_date->format('Y-m-d') : '';

        $title = "Timeline Ending Soon";
        $message = "The timeline '{$timelineName}' in project '{$projectName}' is ending soon on {$endDate}.";

        if ($occasion === 'on_expiry_date') {
            $title = "Timeline Ending Today";
            $message = "The timeline '{$timelineName}' in project '{$projectName}' is ending today ({$endDate}).";
        } elseif ($occasion === '1_day_after') {
            $title = "Timeline Expired";
            $message = "The timeline '{$timelineName}' in project '{$projectName}' expired yesterday on {$endDate}.";
        }

        $url = $timeline->project ? url('projects/' . $timeline->project_id . '/edit') : null;

        // Email notifications only
        $channels = ['mail'];

        $emailDetails = [
            ['label' => 'Project', 'value' => $projectName],
            ['label' => 'Timeline', 'value' => $timelineName],
            ['label' => 'End Date', 'value' => $endDate],
        ];

        // 1. Send to PMS Users
        if ($recipients['users']->isNotEmpty()) {
            Notification::send(
                $recipients['users'],
                new ProjectNotification($title, $message, $url, $channels, $timeline->project_id, $emailDetails, $logId)
            );
        }

        // 2. Send to Additional Email Recipients
        foreach ($recipients['additional_emails'] as $email) {
            Notification::route('mail', $email)->notify(
                new ProjectNotification($title, $message, $url, $channels, $timeline->project_id, $emailDetails, $logId)
            );
        }
    }
}
