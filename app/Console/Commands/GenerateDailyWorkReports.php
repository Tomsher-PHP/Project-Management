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
    protected $signature = 'app:generate-daily-work-reports {--date= : Generate the report for a specific date (Y-m-d)}';

    protected $description = 'Generate and send scheduled daily work reports';

    public function handle(DailyWorkReportService $reportService, DailyWorkReportRecipientService $recipientService): int
    {
        $settings = DailyWorkReportSetting::current();

        if (! $settings->enabled) {
            $this->info('Daily work reports are disabled.');

            return self::SUCCESS;
        }

        $timezone = $settings->timezone ?: config('app.timezone');
        $now = Carbon::now($timezone);

        /* Determine Reporting Period */

        $dateOption = $this->option('date');

        if ($dateOption !== null) {
            try {
                $reportDateOverride = Carbon::createFromFormat(
                    '!Y-m-d',
                    $dateOption,
                    $timezone
                );

                if (
                    ! $reportDateOverride ||
                    $reportDateOverride->format('Y-m-d') !== $dateOption
                ) {
                    throw new \InvalidArgumentException('Invalid date.');
                }
            } catch (Throwable $exception) {
                $this->error('Invalid date. Use YYYY-MM-DD.');

                return self::FAILURE;
            }

            if ($settings->frequency === 'weekly') {
                $this->error('Date override is not supported for weekly reports.');

                return self::FAILURE;
            }

            $start = $reportDateOverride->copy()->startOfDay();
            $end = $reportDateOverride->copy()->endOfDay();
            $reportDate = $reportDateOverride->toDateString();

            $this->info("Manual report generation requested for: {$reportDate}");
        } else {
            if (! $this->isDeliveryDue($settings, $now)) {
                $this->line('Daily work report delivery is not due yet.');

                return self::SUCCESS;
            }

            [$start, $end, $reportDate] = $this->getReportingPeriod(
                $settings->reporting_period,
                $settings->frequency,
                $now
            );
        }

        $this->info("Reporting period: {$start->toDateTimeString()} to {$end->toDateTimeString()}");
        $this->info("Report date: {$reportDate}");

        $reportType = $settings->frequency === 'weekly'
            ? 'weekly'
            : 'daily';

        $sections = $settings->report_sections ?? [];
        $reportKey = 'daily_work';

        /* Eligible Employees */

        $employeesQuery = User::query();

        if (! $settings->include_inactive_users) {
            $employeesQuery->where('is_active', true);
        }

        $employees = $employeesQuery->get();

        $this->info("Found {$employees->count()} employees.");

        /**
         * Employee Report Preferences
         * 
         * is_enabled: Controls inclusion in another employee's consolidated report.
         * receive_individual_report: Controls whether the employee receives their own report.
         * No preference row means both preferences are enabled.
         */
        $dailyReportPreferences = UserReportPreference::query()
            ->where('report_key', $reportKey)
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $enabledEmployees = $employees
            ->filter(fn($employee) => (bool) (
                $dailyReportPreferences->get($employee->id)?->is_enabled ?? true
            ))
            ->values();

        $individualReportEmployees = $employees
            ->filter(fn($employee) => (bool) (
                $dailyReportPreferences->get($employee->id)?->receive_individual_report ?? true
            ))
            ->values();

        // Keep all employee IDs for hierarchy traversal.
        $employeeIds = $employees
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->info("Processing {$individualReportEmployees->count()} individual reports.");

        $this->info("Processing {$employees->count()} potential consolidated report owners.");

        /* Processing Counters */

        $generated = 0;
        $skipped = 0;
        $failed = 0;
        $deliveryFailures = 0;

        /* Generate Individual Reports */

        foreach ($individualReportEmployees as $employee) {
            try {
                $existing = DailyWorkReportLog::query()
                    ->where('user_id', $employee->id)
                    ->whereDate('report_date', $reportDate)
                    ->where('report_type', $reportType)
                    ->where('report_key', $reportKey)
                    ->where('report_variant', 'individual')
                    ->exists();

                if ($existing) {
                    $this->line("Skipped individual report for {$employee->name}: report already exists.");
                    $skipped++;
                    continue;
                }

                $report = $reportService->generate($employee, $start, $end);

                $report = $this->applySections($report, $sections, $reportDate);

                if (! $settings->send_zero_activity && ! $this->hasActivity($report)) {
                    $this->line("Skipped individual report for {$employee->name}: no activity.");
                    $skipped++;
                    continue;
                }

                $recipients = $recipientService->resolve($employee, $reportDate, $reportKey);

                $recipientSnapshot = $recipients->values()->all();

                $log = DB::transaction(function () use (
                    $employee,
                    $reportDate,
                    $start,
                    $end,
                    $reportType,
                    $reportKey,
                    $report,
                    $recipientSnapshot,
                    $recipients
                ) {
                    $appTimezone = config('app.timezone', 'UTC');
                    $generatedAt = Carbon::now($appTimezone);

                    $log = DailyWorkReportLog::firstOrCreate(
                        [
                            'user_id' => $employee->id,
                            'report_date' => $reportDate,
                            'report_type' => $reportType,
                            'report_key' => $reportKey,
                            'report_variant' => 'individual',
                        ],
                        [
                            'period_start' => $start,
                            'period_end' => $end,
                            'status' => $recipients->isEmpty()
                                ? 'skipped'
                                : 'pending',
                            'report_data' => $report,
                            'recipients_snapshot' => $recipientSnapshot,
                            'generated_at' => $generatedAt,
                            'completed_at' => $recipients->isEmpty()
                                ? $generatedAt->copy()
                                : null,
                        ]
                    );

                    if (! $log->wasRecentlyCreated) {
                        return null;
                    }

                    $uniqueRecipients = collect($recipients)
                        ->map(function ($recipient) {
                            $recipient = (array) $recipient;
                            $recipient['email'] = mb_strtolower(trim((string) ($recipient['email'] ?? '')));

                            return $recipient;
                        })
                        ->filter(fn($r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
                        ->unique('email')
                        ->values();

                    foreach ($uniqueRecipients as $recipient) {
                        DailyWorkReportDelivery::firstOrCreate(
                            [
                                'daily_work_report_log_id' => $log->id,
                                'recipient_email' => $recipient['email'],
                            ],
                            [
                                'recipient_name' => $recipient['name'] ?? null,
                                'recipient_type' => $recipient['sources'][0] ?? null,
                                'recipient_sources' => $recipient['sources'] ?? [],
                                'status' => 'pending',
                            ]
                        );
                    }

                    return $log;
                });

                if (! $log) {
                    $skipped++;

                    continue;
                }

                if ($recipients->isEmpty()) {
                    $this->warn("No recipients found for individual report of {$employee->name}.");
                    $skipped++;
                    continue;
                }

                $log->update([
                    'status' => 'queued',
                    'attempt_count' => 0,
                ]);

                foreach ($log->deliveries()->get() as $delivery) {
                    try {
                        $delivery->update([
                            'status' => 'queued',
                            'last_error' => null,
                        ]);

                        SendDailyWorkReportDelivery::dispatch($delivery->id);
                    } catch (Throwable $exception) {
                        $deliveryFailures++;

                        $delivery->update([
                            'status' => 'failed',
                            'last_error' => mb_substr(
                                $exception->getMessage(),
                                0,
                                60000
                            ),
                        ]);

                        report($exception);

                        $this->error("Delivery dispatch failed for {$delivery->recipient_email}: {$exception->getMessage()}");
                    }
                }

                $this->refreshLogStatus(
                    $log->fresh(),
                    $timezone
                );

                $generated++;

                $this->info("Generated individual report for {$employee->name}.");
            } catch (Throwable $exception) {
                $failed++;

                report($exception);

                $this->error("Individual report failed for {$employee->name}: {$exception->getMessage()}");
            }
        }

        /**
         * Generate Consolidated Reports
         * 
         * Every employee can own a consolidated report.
         * Only employees with is_enabled = true are included as subordinates.
         */
        foreach ($employees as $reportOwner) {
            try {
                $subordinateIds = $this->getAllSubordinateUserIds(
                    $reportOwner->id,
                    $employeeIds
                );

                if (empty($subordinateIds)) {
                    $this->line("Skipped {$reportOwner->name}: no subordinates.");
                    $skipped++;
                    continue;
                }

                $subordinates = $enabledEmployees
                    ->whereIn('id', $subordinateIds)
                    ->unique('id')
                    ->values();

                if ($subordinates->isEmpty()) {
                    $skipped++;
                    continue;
                }

                $existing = DailyWorkReportLog::query()
                    ->where('user_id', $reportOwner->id)
                    ->whereDate('report_date', $reportDate)
                    ->where('report_type', $reportType)
                    ->where('report_key', $reportKey)
                    ->where('report_variant', 'consolidated')
                    ->exists();

                if ($existing) {
                    $this->line("Skipped {$reportOwner->name}: consolidated report already exists.");
                    $skipped++;
                    continue;
                }

                $report = $reportService->generateConsolidated(
                    $reportOwner,
                    $subordinates,
                    $start,
                    $end
                );

                $report['employees'] = collect($report['employees'] ?? [])
                    ->map(function ($employeeReport) use ($sections, $reportDate) {
                        return $this->applySections(
                            $employeeReport,
                            $sections,
                            $reportDate
                        );
                    })
                    ->values()
                    ->all();

                if (! $settings->send_zero_activity && ! $this->hasConsolidatedActivity($report)) {
                    $this->line("Skipped {$reportOwner->name}: no subordinate activity.");
                    $skipped++;
                    continue;
                }

                $recipients = $recipientService->resolveForHierarchy(
                    $reportOwner,
                    $subordinates,
                    $reportDate,
                    $reportKey
                );

                $recipientSnapshot = $recipients->values()->all();

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
                    $appTimezone = config('app.timezone', 'UTC');
                    $generatedAt = Carbon::now($appTimezone);

                    $log = DailyWorkReportLog::firstOrCreate(
                        [
                            'user_id' => $reportOwner->id,
                            'report_date' => $reportDate,
                            'report_type' => $reportType,
                            'report_key' => $reportKey,
                            'report_variant' => 'consolidated',
                        ],
                        [
                            'period_start' => $start,
                            'period_end' => $end,
                            'status' => $recipients->isEmpty()
                                ? 'skipped'
                                : 'pending',
                            'report_data' => $report,
                            'recipients_snapshot' => $recipientSnapshot,
                            'generated_at' => $generatedAt,
                            'completed_at' => $recipients->isEmpty()
                                ? $generatedAt->copy()
                                : null,
                        ]
                    );

                    if (! $log->wasRecentlyCreated) {
                        return null;
                    }

                    $uniqueRecipients = collect($recipients)
                        ->map(function ($recipient) {
                            $recipient = (array) $recipient;
                            $recipient['email'] = mb_strtolower(trim((string) ($recipient['email'] ?? '')));

                            return $recipient;
                        })
                        ->filter(fn($r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
                        ->unique('email')
                        ->values();

                    foreach ($uniqueRecipients as $recipient) {
                        DailyWorkReportDelivery::firstOrCreate(
                            [
                                'daily_work_report_log_id' => $log->id,
                                'recipient_email' => $recipient['email'],
                            ],
                            [
                                'recipient_name' => $recipient['name'] ?? null,
                                'recipient_type' => $recipient['sources'][0] ?? null,
                                'recipient_sources' => $recipient['sources'] ?? [],
                                'status' => 'pending',
                            ]
                        );
                    }

                    return $log;
                });

                if (! $log) {
                    $skipped++;
                    continue;
                }

                if ($recipients->isEmpty()) {
                    $this->warn("No recipients found for {$reportOwner->name}.");
                    $skipped++;
                    continue;
                }

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
                        $deliveryFailures++;

                        $delivery->update([
                            'status' => 'failed',
                            'last_error' => mb_substr(
                                $exception->getMessage(),
                                0,
                                60000
                            ),
                        ]);

                        report($exception);
                        $this->error("Delivery dispatch failed for {$delivery->recipient_email}: {$exception->getMessage()}");
                    }
                }

                $this->refreshLogStatus(
                    $log->fresh(),
                    $timezone
                );

                $generated++;

                $this->info("Generated consolidated report for {$reportOwner->name} ({$subordinates->count()} employees).");
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->error("Failed for {$reportOwner->name}: {$exception->getMessage()}");
            }
        }

        /* Processing Summary */
        $this->newLine();

        $this->info('Daily work report processing completed.');

        $this->table(
            ['Result', 'Count'],
            [
                ['Generated', $generated],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Delivery Dispatch Failures', $deliveryFailures],
            ]
        );

        return ($failed > 0 || $deliveryFailures > 0)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /* Get All Direct and Indirect Subordinates */

    private function getAllSubordinateUserIds(int $userId, array $employeeIds): array
    {
        $subordinateIds = [];
        $visitedIds = [$userId];
        $currentLevelIds = [$userId];

        while (! empty($currentLevelIds)) {
            $nextLevelIds = User::query()
                ->whereIn('id', $employeeIds)
                ->whereHas('details', function ($query) use ($currentLevelIds) {
                    $query->whereIn('reporter_id', $currentLevelIds)
                        ->orWhereIn('manager_id', $currentLevelIds);
                })
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->reject(fn($id) => in_array($id, $visitedIds, true))
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

    /* Delivery Schedule */
    private function isDeliveryDue($settings, Carbon $now): bool
    {
        if ($settings->frequency === 'weekly' && (int) $now->dayOfWeekIso !== (int) $settings->weekly_day) {
            return false;
        }

        $deliveryTime = substr(
            (string) $settings->delivery_time,
            0,
            5
        );

        return $now->format('H:i') >= $deliveryTime;
    }

    /* Reporting Period */

    private function getReportingPeriod(string $reportingPeriod, string $frequency, Carbon $now): array
    {
        if ($frequency === 'weekly') {
            $start = $now->copy()
                ->subWeek()
                ->startOfWeek(Carbon::MONDAY);

            $end = $start->copy()
                ->endOfWeek(Carbon::SUNDAY);

            return [
                $start,
                $end,
                $end->toDateString(),
            ];
        }

        // Always use the previous calendar day for daily reports.
        $date = $now->copy()->subDay();

        return [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
            $date->toDateString(),
        ];
    }

    /* Apply Selected Report Sections */
    private function applySections(array $report, array $sections, string $today): array
    {

        // An empty selection means all report sections are enabled.
        if (empty($sections)) {
            return $report;
        }

        // Calculate deadlines before removing the current workload section.
        if (in_array('deadlines', $sections, true)) {
            $tasks = $report['current_workload']['tasks'] ?? [];

            $report['deadlines'] = collect($tasks)
                ->filter(fn($task) => ! empty($task['due_date']))
                ->filter(fn($task) => empty($task['completed_at']))
                ->filter(fn($task) => Carbon::parse(
                    $task['due_date']
                )->toDateString() < $today)
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

    /* Check Individual Report Activity */
    private function hasActivity(array $report): bool
    {
        return ! empty($report['daily_activity']['status_changes'])
            || ! empty($report['daily_activity']['new_assignments'])
            || ! empty($report['time_logs']['entries'])
            || ! empty($report['comments'])
            || ! empty($report['developer_notes']);
    }

    /* Check Consolidated Report Activity */
    private function hasConsolidatedActivity(array $report): bool
    {
        foreach ($report['employees'] ?? [] as $employeeReport) {
            if ($this->hasActivity($employeeReport)) {
                return true;
            }
        }

        return false;
    }

    /* Refresh Report Log Status */
    private function refreshLogStatus(DailyWorkReportLog $log, string $timezone): void
    {
        $deliveries = $log->deliveries()->get();
        $total = $deliveries->count();
        $sent = $deliveries->where('status', 'sent')->count();
        $failed = $deliveries->where('status', 'failed')->count();
        $status = 'queued';
        $completedAt = null;
        $appTimezone = config('app.timezone', 'UTC');

        if ($total > 0 && $sent === $total) {
            $status = 'sent';
            $completedAt = Carbon::now($appTimezone);
        } elseif ($total > 0 && ($sent + $failed) === $total) {
            $status = $sent > 0
                ? 'partial'
                : 'failed';

            $completedAt = Carbon::now($appTimezone);
        }

        $log->update([
            'status' => $status,
            'completed_at' => $completedAt,
        ]);
    }
}
