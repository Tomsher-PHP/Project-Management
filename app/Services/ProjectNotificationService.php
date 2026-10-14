<?php

namespace App\Services;

use App\Models\ProjectNotificationSetting;
use App\Models\ProjectNotificationLog;
use App\Models\ProjectTimeline;
use App\Notifications\ProjectNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class ProjectNotificationService
{
    public function processTimelineEndingSoon(): void
    {
        $setting = ProjectNotificationSetting::with('users')
            ->where('notification_type', 'timeline_ending_soon')
            ->first();

        if (!$setting || !$setting->is_enabled || $setting->users->isEmpty()) {
            return;
        }

        $daysBefore = (int) $setting->days_before;
        $targetDate = Carbon::today()->addDays($daysBefore)->format('Y-m-d');

        // Find active timelines that end on the target date
        $timelines = ProjectTimeline::with('project')
            ->leftJoin('project_notification_logs as log', 'log.project_timeline_id', '=', 'project_timelines.id')
            ->where('project_timelines.status', ProjectTimeline::STATUS_ACTIVE)
            ->whereNotNull('project_timelines.end_date')
            ->where('project_timelines.end_date', '>=', Carbon::today()->format('Y-m-d'))
            ->whereDate('project_timelines.end_date', '<=', $targetDate)
            ->where(function ($query) {
                $query->whereNull('log.id')
                    ->orWhereNotIn('log.status', [
                        ProjectNotificationLog::STATUS_SENT,
                        ProjectNotificationLog::STATUS_QUEUED,
                    ]);
            })
            ->select('project_timelines.*')
            ->distinct()
            ->get();

        foreach ($timelines as $timeline) {
            $this->dispatchNotificationForTimeline($timeline, $setting, $targetDate);
        }
    }

    protected function dispatchNotificationForTimeline(ProjectTimeline $timeline, ProjectNotificationSetting $setting, string $targetDate): void
    {
        DB::transaction(function () use ($timeline, $setting, $targetDate) {
            // Deduplication check via DB constraint or firstOrCreate
            $log = ProjectNotificationLog::firstOrCreate(
                [
                    'project_notification_setting_id' => $setting->id,
                    'project_timeline_id' => $timeline->id,
                ],
                [
                    'scheduled_for' => $targetDate,
                    'status' => ProjectNotificationLog::STATUS_QUEUED,
                ]
            );

            if ($log->wasRecentlyCreated) {
                $this->sendNotification($timeline, $setting, $log->id);
            }
        });
    }

    protected function sendNotification(ProjectTimeline $timeline, ProjectNotificationSetting $setting, int $logId): void
    {
        $projectName = $timeline->project ? $timeline->project->name : 'Project';
        $timelineName = $timeline->name;
        $endDate = $timeline->end_date->format('Y-m-d');

        $title = "Timeline Ending Soon";
        $message = "The timeline '{$timelineName}' in project '{$projectName}' is ending soon on {$endDate}.";

        $url = $timeline->project ? url('projects/' . $timeline->project_id . '/edit') : null;

        $channels = ['mail', 'database', 'broadcast'];

        $emailDetails = [
            ['label' => 'Project', 'value' => $projectName],
            ['label' => 'Timeline', 'value' => $timelineName],
            ['label' => 'End Date', 'value' => $endDate],
        ];

        Notification::send(
            $setting->users,
            new ProjectNotification($title, $message, $url, $channels, $timeline->project_id, $emailDetails, $logId)
        );
    }
}
