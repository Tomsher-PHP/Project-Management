<?php

namespace App\Services;

use App\Models\HostingServiceStatus;
use App\Models\ProjectHostingRecord;
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
        // $timezone = Configuration::
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
     * Process hosting expiry notifications based on global settings.
     */
    public function processHostingExpiryNotifications(): void
    {
        $setting = ProjectNotificationSetting::with(['users', 'emailRecipients'])
            ->where('notification_type', ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON)
            ->first();

        // 1. Requirements: Send nothing when the setting does not exist or is disabled
        if (!$setting || !$setting->is_enabled) {
            return;
        }

        // 2. Recipients: Retrieve and deduplicate recipients
        $recipients = $this->getRecipients($setting);
        if ($recipients['users']->isEmpty() && empty($recipients['additional_emails'])) {
            return;
        }

        // 3. Date & days_before calculation
        $today = Carbon::today(config('app.timezone'));
        $daysBefore = max(1, (int) $setting->days_before);

        // 4. Eligible hosting records:
        // Exclude cancelled and terminated records; must have expiry_date; project not deleted.
        $ineligibleStatusIds = HostingServiceStatus::whereIn('code', ['cancelled', 'terminated'])
            ->pluck('id');

        $hostingRecords = ProjectHostingRecord::with(['project', 'hostingProvider', 'hostingService', 'hostingServiceStatus'])
            ->whereNotNull('expiry_date')
            ->when($ineligibleStatusIds->isNotEmpty(), function ($query) use ($ineligibleStatusIds) {
                $query->whereNotIn('hosting_service_status_id', $ineligibleStatusIds);
            })
            ->whereHas('project', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get();

        foreach ($hostingRecords as $record) {
            $occasion = $this->determineHostingDueOccasionForToday($record, $today, $daysBefore);

            if ($occasion !== null) {
                $this->dispatchNotificationForHostingRecord($record, $setting, $today->toDateString(), $occasion, $recipients);
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
     * Determine if today matches any notification occasion for the given hosting record.
     * Returns the occasion key/name if due today, or null if not due today.
     *
     * Occasions:
     * 1. The configured number of days before expiry, using days_before.
     * 2. One day before expiry.
     * 3. On the expiry date.
     * 4. One day after expiry.
     *
     * If notification dates overlap (e.g. days_before = 1), only one notification is sent for that date.
     */
    public function determineHostingDueOccasionForToday(ProjectHostingRecord $record, Carbon $today, int $daysBefore): ?string
    {
        if (!$record->expiry_date) {
            return null;
        }

        $expiry = Carbon::parse($record->expiry_date, config('app.timezone'))->startOfDay();
        $todayDate = $today->copy()->startOfDay();

        // Calculate occasion dates
        $configuredDate = $expiry->copy()->subDays($daysBefore)->toDateString();
        $oneDayBeforeDate = $expiry->copy()->subDays(1)->toDateString();
        $expiryDate = $expiry->toDateString();
        $oneDayAfterDate = $expiry->copy()->addDays(1)->toDateString();

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
     * Dispatch notification for hosting record if not already queued or sent today.
     */
    protected function dispatchNotificationForHostingRecord(ProjectHostingRecord $record, ProjectNotificationSetting $setting, string $todayDateStr, string $occasion, array $recipients): void
    {
        DB::transaction(function () use ($record, $setting, $todayDateStr, $occasion, $recipients) {
            // Lock and check if a log already exists for this hosting record and date
            $existingLog = ProjectNotificationLog::where('link_type', ProjectHostingRecord::class)
                ->where('link_id', $record->id)
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
                    'link_type' => ProjectHostingRecord::class,
                    'link_id' => $record->id,
                    'scheduled_for' => $todayDateStr,
                    'reminder_occasion' => $occasion,
                    'status' => ProjectNotificationLog::STATUS_QUEUED,
                ]);

                $this->sendHostingNotification($record, $recipients, $log->id, $occasion, $todayDateStr);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Handle concurrent duplicate attempts safely
                return;
            }
        });
    }

    /**
     * Retrieve and deduplicate recipients between PMS users and additional emails.
     *
     * @return array{users: EloquentCollection<int, \App\Models\User>, additional_emails: array<int, string>}
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
     * Send email notifications using the ProjectNotification class for timelines.
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

    /**
     * Send email notifications using the ProjectNotification class for hosting records.
     */
    protected function sendHostingNotification(ProjectHostingRecord $record, array $recipients, int $logId, string $occasion, string $todayDateStr): void
    {
        $projectName = $record->project ? $record->project->name : 'Project';
        $hostingName = $record->name;
        $expiryDate = $record->expiry_date ? $record->expiry_date->format('Y-m-d') : '';

        $providerName = $record->hostingProvider ? $record->hostingProvider->name : '-';
        $serviceName = $record->hostingService ? $record->hostingService->name : '-';

        // Calculate remaining or overdue days
        $today = Carbon::parse($todayDateStr, config('app.timezone'))->startOfDay();
        $expiry = Carbon::parse($expiryDate, config('app.timezone'))->startOfDay();
        $diffDays = (int) $today->diffInDays($expiry, false);

        if ($occasion === 'on_expiry_date') {
            $title = "Hosting Expiring Today";
            $message = "The hosting service '{$hostingName}' in project '{$projectName}' expires today ({$expiryDate}).";
            $daysStatusLabel = "Expiring today";
        } elseif ($occasion === '1_day_after') {
            $title = "Hosting Expired";
            $message = "The hosting service '{$hostingName}' in project '{$projectName}' expired yesterday on {$expiryDate}.";
            $daysStatusLabel = "1 day overdue";
        } else {
            $title = "Hosting Expiring Soon";
            $message = "The hosting service '{$hostingName}' in project '{$projectName}' is expiring soon on {$expiryDate}.";
            $daysStatusLabel = $diffDays > 0 ? "{$diffDays} days remaining" : "Expiring soon";
        }

        $url = $record->project ? url('projects/' . $record->project_id . '/edit') : null;

        $channels = ['mail'];

        $emailDetails = [
            ['label' => 'Project', 'value' => $projectName],
            ['label' => 'Hosting Name', 'value' => $hostingName],
            ['label' => 'Hosting Provider', 'value' => $providerName],
            ['label' => 'Hosting Service', 'value' => $serviceName],
        ];

        if (!empty($record->domain)) {
            $emailDetails[] = ['label' => 'Domain / Hostname', 'value' => $record->domain];
        }

        $emailDetails[] = ['label' => 'Expiry Date', 'value' => $expiryDate];
        $emailDetails[] = ['label' => 'Status', 'value' => $daysStatusLabel];

        // 1. Send to PMS Users
        if ($recipients['users']->isNotEmpty()) {
            Notification::send(
                $recipients['users'],
                new ProjectNotification($title, $message, $url, $channels, $record->project_id, $emailDetails, $logId)
            );
        }

        // 2. Send to Additional Email Recipients
        foreach ($recipients['additional_emails'] as $email) {
            Notification::route('mail', $email)->notify(
                new ProjectNotification($title, $message, $url, $channels, $record->project_id, $emailDetails, $logId)
            );
        }
    }
}
