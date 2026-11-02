<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyWorkReportDelivery;
use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportSetting;
use App\Models\User;
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

        $employeesQuery = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if (! $settings->include_inactive_users) {
            $employeesQuery->where('is_active', true);
        }

        $employees = $employeesQuery->get();

        $this->info("Processing {$employees->count()} employees.");

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($employees as $employee) {
            try {
                // Avoid creating the same report more than once.
                $existing = DailyWorkReportLog::query()
                    ->where('user_id', $employee->id)
                    ->whereDate('report_date', $reportDate)
                    ->where('report_type', $reportType)
                    ->exists();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                $report = $reportService->generate(
                    $employee,
                    $start,
                    $end
                );

                $report = $this->applySections($report, $sections);

                if (
                    ! $settings->send_zero_activity
                    && ! $this->hasActivity($report)
                ) {
                    $this->line("Skipped {$employee->name}: no activity.");

                    $skipped++;
                    continue;
                }

                $recipients = $recipientService->resolve(
                    $employee,
                    $reportDate
                );

                $recipientSnapshot = $recipients->values()->all();

                $log = DB::transaction(function () use (
                    $employee,
                    $reportDate,
                    $start,
                    $end,
                    $reportType,
                    $report,
                    $recipientSnapshot,
                    $recipients
                ) {
                    // firstOrCreate also protects against a duplicate
                    // created by another scheduler process.
                    $log = DailyWorkReportLog::firstOrCreate(
                        [
                            'user_id' => $employee->id,
                            'report_date' => $reportDate,
                            'report_type' => $reportType,
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
                        "No recipients found for {$employee->name}."
                    );

                    $skipped++;
                    continue;
                }

                // Set the report status before dispatching jobs.
                // This is important when QUEUE_CONNECTION=sync.
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

                        SendDailyWorkReportDelivery::dispatch($delivery->id);

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

                // Recalculate after dispatch, including synchronous deliveries.
                $this->refreshLogStatus($log);

                $generated++;

                $this->info("Generated report for {$employee->name}.");

            } catch (Throwable $exception) {
                $failed++;

                report($exception);

                $this->error(
                    "Failed for {$employee->name}: {$exception->getMessage()}"
                );
            }
        }

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

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

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

        return $now->format('H:i') === $deliveryTime;
    }

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

    private function applySections(array $report, array $sections): array
    {
        // Calculate deadlines before removing the current workload section.
        if (in_array('deadlines', $sections, true)) {
            $tasks = $report['current_workload']['tasks'] ?? [];

            $report['deadlines'] = collect($tasks)
                ->filter(fn ($task) => ! empty($task['due_date']))
                ->filter(fn ($task) => empty($task['completed_at']))
                ->filter(fn ($task) => Carbon::parse($task['due_date'])->isPast())
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

    private function hasActivity(array $report): bool
    {
        return ! empty($report['daily_activity']['status_changes'])
            || ! empty($report['daily_activity']['new_assignments'])
            || ! empty($report['time_logs']['entries'])
            || ! empty($report['comments'])
            || ! empty($report['developer_notes']);
    }

    private function refreshLogStatus(DailyWorkReportLog $log): void
    {
        $deliveries = $log->deliveries()->get();

        $total = $deliveries->count();
        $sent = $deliveries->where('status', 'sent')->count();
        $failed = $deliveries->where('status', 'failed')->count();

        $status = 'queued';

        if ($total > 0 && $sent === $total) {
            $status = 'sent';
        } elseif ($failed > 0 && $sent > 0) {
            $status = 'partial';
        } elseif ($failed > 0 && $sent === 0) {
            $status = 'failed';
        }

        $log->update([
            'status' => $status,
            'completed_at' => in_array(
                $status,
                ['sent', 'partial', 'failed'],
                true
            ) ? now() : null,
        ]);
    }
}
