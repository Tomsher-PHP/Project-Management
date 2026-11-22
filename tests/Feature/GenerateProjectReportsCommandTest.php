<?php

namespace Tests\Feature;

use App\Jobs\SendProjectReportDelivery;
use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\Reports\ProjectReportAutomationService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateProjectReportsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectReportAutomationService $service;
    protected ProjectStatus $status;
    protected User $recipientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProjectReportAutomationService::class);

        $this->status = ProjectStatus::firstOrCreate(
            ['name' => 'Command Test Status'],
            [
                'color' => '#10b981',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->recipientUser = User::factory()->create([
            'name' => 'Report Subscriber',
            'email' => 'subscriber@example.com',
            'is_active' => true,
        ]);
    }

    public function test_command_is_registered_in_scheduler_every_minute_without_overlapping(): void
    {
        $schedule = app(Schedule::class);

        $events = collect($schedule->events())->filter(function ($event) {
            return str_contains($event->command, 'app:generate-project-reports');
        });

        $this->assertNotEmpty($events, 'Command app:generate-project-reports must be registered in the scheduler.');

        $event = $events->first();
        $this->assertEquals('* * * * *', $event->expression, 'Command should run every minute.');
        $this->assertTrue($event->withoutOverlapping, 'Command should run without overlapping.');
    }

    public function test_command_executes_successfully_when_no_configurations_are_due(): void
    {
        // Configuration scheduled for 23:59:00
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_not_due',
            'report_name' => 'Future Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '23:59:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $referenceDate = '2026-11-22 08:00:00'; // 08:00 < 23:59

        Artisan::call('app:generate-project-reports', ['--reference-date' => $referenceDate]);
        $output = Artisan::output();

        $this->assertStringContainsString('Project Reports execution completed', $output);
        $this->assertStringContainsString('Processed: 0', $output);
        $this->assertStringContainsString('Skipped: 1', $output);
    }

    public function test_disabled_configurations_are_ignored(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_disabled',
            'report_name' => 'Disabled Report',
            'report_key' => 'project_report',
            'enabled' => false,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $referenceDate = '2026-11-22 09:00:00';
        $this->assertFalse($this->service->isConfigurationDue($setting, Carbon::parse($referenceDate)));
    }

    public function test_weekly_frequency_schedule_evaluation(): void
    {
        // Weekly report on Wednesday (3) at 09:00
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_weekly',
            'report_name' => 'Weekly Wednesday Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'weekly',
            'weekly_day' => 3, // Wednesday
            'delivery_time' => '09:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'last_7_days',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        // Tuesday (dayOfWeekIso = 2) at 09:30 -> Not due
        $tuesday = Carbon::parse('2026-11-17 09:30:00', 'UTC'); // 2026-11-17 is Tuesday
        $this->assertEquals(2, $tuesday->dayOfWeekIso);
        $this->assertFalse($this->service->isConfigurationDue($setting, $tuesday));

        // Wednesday at 08:30 -> Not due yet (before delivery_time)
        $wednesdayEarly = Carbon::parse('2026-11-18 08:30:00', 'UTC'); // 2026-11-18 is Wednesday
        $this->assertEquals(3, $wednesdayEarly->dayOfWeekIso);
        $this->assertFalse($this->service->isConfigurationDue($setting, $wednesdayEarly));

        // Wednesday at 09:00 -> Due!
        $wednesdayDue = Carbon::parse('2026-11-18 09:00:00', 'UTC');
        $this->assertTrue($this->service->isConfigurationDue($setting, $wednesdayDue));
    }

    public function test_monthly_frequency_with_specific_day_and_last_day(): void
    {
        // 1. Monthly on 15th at 10:00
        $settingDay15 = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_monthly_15',
            'report_name' => 'Mid-Month Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'monthly',
            'delivery_time' => '10:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'last_30_days',
            'settings' => [
                'schedule' => ['monthly_day' => 15],
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $day14 = Carbon::parse('2026-11-14 10:30:00', 'UTC');
        $day15 = Carbon::parse('2026-11-15 10:00:00', 'UTC');

        $this->assertFalse($this->service->isConfigurationDue($settingDay15, $day14));
        $this->assertTrue($this->service->isConfigurationDue($settingDay15, $day15));

        // 2. Monthly on "last" day at 18:00
        $settingLastDay = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_monthly_last',
            'report_name' => 'Month End Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'monthly',
            'delivery_time' => '18:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'last_30_days',
            'settings' => [
                'schedule' => ['monthly_day' => 'last'],
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        // November has 30 days
        $nov29 = Carbon::parse('2026-11-29 18:30:00', 'UTC');
        $nov30Early = Carbon::parse('2026-11-30 17:30:00', 'UTC');
        $nov30Due = Carbon::parse('2026-11-30 18:00:00', 'UTC');

        $this->assertFalse($this->service->isConfigurationDue($settingLastDay, $nov29));
        $this->assertFalse($this->service->isConfigurationDue($settingLastDay, $nov30Early));
        $this->assertTrue($this->service->isConfigurationDue($settingLastDay, $nov30Due));
    }

    public function test_timezone_aware_due_evaluation(): void
    {
        // 08:00 delivery time in Asia/Dubai (UTC+4)
        $settingDubai = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_tz_dubai',
            'report_name' => 'Dubai Morning Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'previous_day',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        // At 03:59 UTC, it is 07:59 in Dubai -> Not due
        $utc0359 = Carbon::parse('2026-11-22 03:59:00', 'UTC');
        $this->assertFalse($this->service->isConfigurationDue($settingDubai, $utc0359));

        // At 04:00 UTC, it is exactly 08:00 in Dubai -> Due!
        $utc0400 = Carbon::parse('2026-11-22 04:00:00', 'UTC');
        $this->assertTrue($this->service->isConfigurationDue($settingDubai, $utc0400));
    }

    public function test_duplicate_prevention_on_subsequent_runs(): void
    {
        Queue::fake();

        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_duplicate_test',
            'report_name' => 'Daily Duplicate Prevention Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $setting->id,
            'user_id' => $this->recipientUser->id,
            'name' => $this->recipientUser->name,
            'email' => $this->recipientUser->email,
            'is_active' => true,
        ]);

        $referenceDate = '2026-11-22 08:05:00';

        // 1. First execution: should process and queue delivery
        Artisan::call('app:generate-project-reports', ['--reference-date' => $referenceDate]);
        $firstOutput = Artisan::output();

        $this->assertStringContainsString('Processed: 1', $firstOutput);
        Queue::assertPushed(SendProjectReportDelivery::class, 1);

        // 2. Second execution (e.g. next minute): should be recognized as already executed and skipped
        Artisan::call('app:generate-project-reports', ['--reference-date' => '2026-11-22 08:06:00']);
        $secondOutput = Artisan::output();

        $this->assertStringContainsString('Processed: 0', $secondOutput);
        $this->assertStringContainsString('Skipped: 1', $secondOutput);

        // Queue count should not have increased
        Queue::assertPushed(SendProjectReportDelivery::class, 1);

        // Exactly one report log exists for this occurrence
        $logCount = DailyWorkReportLog::where('report_setting_id', $setting->id)
            ->where('report_date', '2026-11-22')
            ->count();
        $this->assertEquals(1, $logCount);
    }

    public function test_independent_processing_when_one_configuration_fails(): void
    {
        Queue::fake();

        // Valid configuration
        $validSetting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_valid',
            'report_name' => 'Valid Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $validSetting->id,
            'user_id' => $this->recipientUser->id,
            'name' => $this->recipientUser->name,
            'email' => $this->recipientUser->email,
            'is_active' => true,
        ]);

        // Configuration with invalid/malformed settings that could trigger an exception
        $faultySetting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_cmd_faulty',
            'report_name' => 'Faulty Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Invalid/Timezone_That_DoesNotExist',
            'reporting_period' => 'all_time',
            'settings' => [],
        ]);

        $referenceDate = '2026-11-22 08:30:00';

        Artisan::call('app:generate-project-reports', ['--reference-date' => $referenceDate]);
        $output = Artisan::output();

        // Valid report must have processed despite the faulty one
        $this->assertStringContainsString('Processed: 1', $output);
        $this->assertStringContainsString('Failures: 1', $output);

        $validLog = DailyWorkReportLog::where('report_setting_id', $validSetting->id)
            ->where('report_date', '2026-11-22')
            ->first();

        $this->assertNotNull($validLog);
        $this->assertEquals('queued', $validLog->status);
    }
}

