<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyWorkReportDelivery;
use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportSetting;
use App\Models\User;
use App\Models\UserReportPreference;
use App\Services\DailyWorkReportRecipientService;
use App\Services\DailyWorkReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateDailyWorkReports extends Command
{
    protected $signature = 'app:generate-daily-work-reports';

    protected $description = 'Generate and send scheduled daily work reports';

    public function handle(
        DailyWorkReportService $reportService,
        DailyWorkReportRecipientService $recipientService
    ): int {
        $settings = DailyWorkReportSetting::current();

        if (! $settings->enabled) {
            $this->info('Daily work reports are disabled.');

            return self::SUCCESS;
        }

        $timezone = $settings->timezone ?: config('app.timezone');

        $now = Carbon::now($timezone);

        if (! $this->isDeliveryDue($settings, $now)) {
            $this->line('Daily work report delivery is not due yet.');

            return self::SUCCESS;
        }

        [$start, $end, $reportDate] = $this->getReportingPeriod(
            $settings->reporting_period,
            $settings->frequency,
            $now
        );

        $reportType = $settings->frequency === 'weekly'
            ? 'weekly'
            : 'daily';

        $sections = $settings->report_sections ?? [];

        /*
        |--------------------------------------------------------------------------
        | Eligible Employees
        |--------------------------------------------------------------------------
        */

        $employeesQuery = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if (! $settings->include_inactive_users) {
            $employeesQuery->where('is_active', true);
        }

        $employees = $employeesQuery->get();

        $this->info("Found {$employees->count()} employees.");

        $reportKey = 'daily_work';

        /*
        |--------------------------------------------------------------------------
        | Employee Report Preferences
        |--------------------------------------------------------------------------
        |
        | No preference row means the report is enabled.
        |
        */

        $dailyReportPreferences = UserReportPreference::query()
            ->where('report_key', $reportKey)
            ->whereIn('user_id', $employees->pluck('id'))
            ->pluck('is_enabled', 'user_id');

        $eligibleEmployees = $employees
            ->filter(fn ($employee) => (bool) $dailyReportPreferences->get(
                $employee->id,
                true
            ))
            ->values();

        $eligibleEmployeeIds = $eligibleEmployees
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->info(
            "Processing {$eligibleEmployees->count()} eligible employees for consolidated reports."
        );

        /*
        |--------------------------------------------------------------------------
        | Processing Counters
        |--------------------------------------------------------------------------
        */

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        /*
        |--------------------------------------------------------------------------
        | Generate Consolidated Reports
        |--------------------------------------------------------------------------
        |
        | Each employee is considered as a potential report owner.
        |
        | Employees with subordinates receive one consolidated report
        | containing all their direct and indirect subordinates.
        |
        */

        foreach ($eligibleEmployees as $reportOwner) {
            try {
                /*
                |--------------------------------------------------------------------------
                | Get All Subordinates
                |--------------------------------------------------------------------------
                */

                $subordinateIds = $this->getAllSubordinateUserIds(
                    $reportOwner->id,
                    $eligibleEmployeeIds
                );

                // Employees without subordinates do not receive
                // a separate hierarchy report.
                if (empty($subordinateIds)) {
                    $this->line(
                        "Skipped {$reportOwner->name}: no subordinates."
                    );

                    $skipped++;

                    continue;
                }

                $subordinates = $eligibleEmployees
                    ->whereIn('id', $subordinateIds)
                    ->unique('id')
                    ->values();

                if ($subordinates->isEmpty()) {
                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent Duplicate Reports
                |--------------------------------------------------------------------------
                */

                $existing = DailyWorkReportLog::query()
                    ->where('user_id', $reportOwner->id)
                    ->whereDate('report_date', $reportDate)
                    ->where('report_type', $reportType)
                    ->where('report_key', $reportKey)
                    ->exists();

                if ($existing) {
                    $this->line(
                        "Skipped {$reportOwner->name}: report already exists."
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Generate Consolidated Report
                |--------------------------------------------------------------------------
                */

                $report = $reportService->generateConsolidated(
                    $reportOwner,
                    $subordinates,
                    $start,
                    $end
                );

                /*
                |--------------------------------------------------------------------------
                | Apply Selected Report Sections
                |--------------------------------------------------------------------------
                |
                | Apply the same General Report Settings to every employee
                | included in the consolidated report.
                |
                */

                $report['employees'] = collect($report['employees'] ?? [])
                    ->map(function ($employeeReport) use ($sections) {
                        return $this->applySections(
                            $employeeReport,
                            $sections
                        );
                    })
                    ->values()
                    ->all();

                /*
                |--------------------------------------------------------------------------
                | Check Activity
                |--------------------------------------------------------------------------
                */

                if (
                    ! $settings->send_zero_activity
                    && ! $this->hasConsolidatedActivity($report)
                ) {
                    $this->line(
                        "Skipped {$reportOwner->name}: no subordinate activity."
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve Recipients
                |--------------------------------------------------------------------------
                |
                | The report owner receives one consolidated report.
                | User-specific and team-specific recipients are included.
                |
                | Global recipients are intentionally handled separately.
                |
                */

                $recipients = $recipientService->resolveForHierarchy(
                    $reportOwner,
                    $subordinates,
                    $reportDate,
                    $reportKey
                );

                $recipientSnapshot = $recipients->values()->all();

                /*
                |--------------------------------------------------------------------------
                | Create Report Log and Delivery Records
                |--------------------------------------------------------------------------
                */

                $log = DB::transaction(function () use (
                    $reportOwner,
                    $reportDate,
                    $start,
                    $end,
                    $reportType,
                    $reportKey,
                    $report,
                    $recipientSnapshot,
                    $recipients
                ) {
                    $log = DailyWorkReportLog::firstOrCreate(
                        [
                            'user_id' => $reportOwner->id,
                            'report_date' => $reportDate,
                            'report_type' => $reportType,
                            'report_key' => $reportKey,
                        ],
                        [
                            'period_start' => $start,
                            'period_end' => $end,
                            'status' => $recipients->isEmpty()
                                ? 'skipped'
                                : 'pending',
                            'report_data' => $report,
                            'recipients_snapshot' => $recipientSnapshot,
                            'generated_at' => now(),
                            'completed_at' => $recipients->isEmpty()
                                ? now()
                                : null,
                        ]
                    );

                    // Another scheduler process may have created
                    // this report already.
                    if (! $log->wasRecentlyCreated) {
                        return null;
                    }

                    foreach ($recipients as $recipient) {
                        DailyWorkReportDelivery::create([
                            'daily_work_report_log_id' => $log->id,
                            'recipient_email' => $recipient['email'],
                            'recipient_name' => $recipient['name'] ?? null,
                            'recipient_type' => $recipient['sources'][0] ?? null,
                            'recipient_sources' => $recipient['sources'] ?? [],
                            'status' => 'pending',
                        ]);
                    }

                    return $log;
                });

                if (! $log) {
                    $skipped++;

                    continue;
                }

                if ($recipients->isEmpty()) {
                    $this->warn(
                        "No recipients found for {$reportOwner->name}."
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Dispatch Delivery Jobs
                |--------------------------------------------------------------------------
                |
                | Set the report status before dispatching jobs.
                | This also supports QUEUE_CONNECTION=sync.
                |
                */

                $log->update([
                    'status' => 'queued',
                    'attempt_count' => 0,
                ]);

                $deliveries = $log->deliveries()->get();

                foreach ($deliveries as $delivery) {
                    try {
                        $delivery->update([
                            'status' => 'queued',
                            'last_error' => null,
                        ]);

                        SendDailyWorkReportDelivery::dispatch(
                            $delivery->id
                        );

                    } catch (Throwable $exception) {
                        $delivery->update([
                            'status' => 'failed',
                            'last_error' => mb_substr(
                                $exception->getMessage(),
                                0,
                                60000
                            ),
                        ]);

                        report($exception);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Refresh Report Status
                |--------------------------------------------------------------------------
                */

                $this->refreshLogStatus($log->fresh());

                $generated++;

                $this->info(
                    "Generated consolidated report for {$reportOwner->name} ({$subordinates->count()} employees)."
                );

            } catch (Throwable $exception) {
                $failed++;

                report($exception);

                $this->error(
                    "Failed for {$reportOwner->name}: {$exception->getMessage()}"
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Processing Summary
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info('Daily work report processing completed.');

        $this->table(
            ['Result', 'Count'],
            [
                ['Generated', $generated],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Direct and Indirect Subordinates
    |--------------------------------------------------------------------------
    |
    | Traverse both Reporting To and Manager relationships.
    |
    | reporter_id and manager_id are both considered hierarchy links.
    | Duplicate employees and circular relationships are prevented.
    |
    */

    private function getAllSubordinateUserIds(
        int $userId,
        array $eligibleEmployeeIds
    ): array {
        $subordinateIds = [];

        // Prevent the report owner from being included in their own report.
        $visitedIds = [$userId];

        $currentLevelIds = [$userId];

        while (! empty($currentLevelIds)) {
            $nextLevelIds = User::query()
                ->whereIn('id', $eligibleEmployeeIds)
                ->whereHas('details', function ($query) use ($currentLevelIds) {
                    $query->whereIn('reporter_id', $currentLevelIds)
                        ->orWhereIn('manager_id', $currentLevelIds);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn ($id) => in_array($id, $visitedIds, true))
                ->values()
                ->all();

            if (empty($nextLevelIds)) {
                break;
            }

            $subordinateIds = array_merge(
                $subordinateIds,
                $nextLevelIds
            );

            $visitedIds = array_merge(
                $visitedIds,
                $nextLevelIds
            );

            $currentLevelIds = $nextLevelIds;
        }

        return array_values(array_unique($subordinateIds));
    }

    /*
    |--------------------------------------------------------------------------
    | Delivery Schedule
    |--------------------------------------------------------------------------
    */

    private function isDeliveryDue($settings, Carbon $now): bool
    {
        if (
            $settings->frequency === 'weekdays'
            && $now->isWeekend()
        ) {
            return false;
        }

        if (
            $settings->frequency === 'weekly'
            && (int) $now->dayOfWeekIso !== (int) $settings->weekly_day
        ) {
            return false;
        }

        $deliveryTime = substr(
            (string) $settings->delivery_time,
            0,
            5
        );

        // Run at the configured time or any time after it.
        return $now->format('H:i') >= $deliveryTime;
    }

    /*
    |--------------------------------------------------------------------------
    | Reporting Period
    |--------------------------------------------------------------------------
    */

    private function getReportingPeriod(
        string $reportingPeriod,
        string $frequency,
        Carbon $now
    ): array {
        if ($frequency === 'weekly') {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfDay();

            return [
                $start,
                $end,
                $now->toDateString(),
            ];
        }

        if ($reportingPeriod === 'previous_working_day') {
            $date = $now->copy()->subDay();

            while ($date->isWeekend()) {
                $date->subDay();
            }
        } else {
            $date = $now->copy();
        }

        return [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
            $now->toDateString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Selected Report Sections
    |--------------------------------------------------------------------------
    */

    private function applySections(
        array $report,
        array $sections
    ): array {
        // Calculate deadlines before removing the current workload section.
        if (in_array('deadlines', $sections, true)) {
            $tasks = $report['current_workload']['tasks'] ?? [];

            $report['deadlines'] = collect($tasks)
                ->filter(fn ($task) => ! empty($task['due_date']))
                ->filter(fn ($task) => empty($task['completed_at']))
                ->filter(fn ($task) => Carbon::parse(
                    $task['due_date']
                )->isPast())
                ->values()
                ->all();
        }

        $allowedSections = [
            'current_tasks' => 'current_workload',
            'daily_activity' => 'daily_activity',
            'time_logs' => 'time_logs',
            'comments' => 'comments',
            'developer_notes' => 'developer_notes',
        ];

        foreach ($allowedSections as $setting => $reportKey) {
            if (! in_array($setting, $sections, true)) {
                unset($report[$reportKey]);
            }
        }

        if (! in_array('deadlines', $sections, true)) {
            unset($report['deadlines']);
        }

        return $report;
    }

    /*
    |--------------------------------------------------------------------------
    | Check Individual Report Activity
    |--------------------------------------------------------------------------
    */

    private function hasActivity(array $report): bool
    {
        return ! empty($report['daily_activity']['status_changes'])
            || ! empty($report['daily_activity']['new_assignments'])
            || ! empty($report['time_logs']['entries'])
            || ! empty($report['comments'])
            || ! empty($report['developer_notes']);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Consolidated Report Activity
    |--------------------------------------------------------------------------
    */

    private function hasConsolidatedActivity(array $report): bool
    {
        foreach ($report['employees'] ?? [] as $employeeReport) {
            if ($this->hasActivity($employeeReport)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Report Log Status
    |--------------------------------------------------------------------------
    |
    | Do not mark the report as failed while deliveries are still queued.
    |
    */

    private function refreshLogStatus(DailyWorkReportLog $log): void
    {
        $deliveries = $log->deliveries()->get();

        $total = $deliveries->count();

        $sent = $deliveries->where('status', 'sent')->count();

        $failed = $deliveries->where('status', 'failed')->count();

        $status = 'queued';
        $completedAt = null;

        if ($total > 0 && $sent === $total) {
            $status = 'sent';
            $completedAt = now();

        } elseif ($total > 0 && ($sent + $failed) === $total) {
            $status = $sent > 0
                ? 'partial'
                : 'failed';

            $completedAt = now();
        }

        $log->update([
            'status' => $status,
            'completed_at' => $completedAt,
        ]);
    }
}
