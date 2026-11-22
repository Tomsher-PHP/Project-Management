<?php

namespace Tests\Feature;

use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectReportLogsSchemaTest extends TestCase
{
    use DatabaseTransactions;

    protected DailyWorkReportSetting $settingA;
    protected DailyWorkReportSetting $settingB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingA = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_schema_test_a',
            'report_name' => 'Project Report Test A',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'previous_day',
            'settings' => [
                'project_statuses' => [1],
                'date_filter' => 'created_at',
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $this->settingB = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_schema_test_b',
            'report_name' => 'Project Report Test B',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '09:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'previous_day',
            'settings' => [
                'project_statuses' => [1],
                'date_filter' => 'created_at',
                'columns' => ['project_name', 'client_name'],
            ],
        ]);
    }

    public function test_schema_has_report_setting_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('daily_work_report_logs', 'report_setting_id'));
    }

    public function test_report_setting_relationship(): void
    {
        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->settingA->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(DailyWorkReportSetting::class, $log->reportSetting);
        $this->assertEquals($this->settingA->id, $log->reportSetting->id);

        $this->assertTrue($this->settingA->reportLogs->contains('id', $log->id));
    }

    public function test_deleting_setting_nullifies_report_setting_id_in_log(): void
    {
        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->settingA->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'completed',
        ]);

        $logId = $log->id;
        $this->settingA->delete();

        $freshLog = DailyWorkReportLog::find($logId);
        $this->assertNotNull($freshLog, 'Report log must NOT be cascade deleted when a setting is deleted.');
        $this->assertNull($freshLog->report_setting_id, 'report_setting_id must be nullOnDelete().');
    }

    public function test_duplicate_prevention_for_same_setting_and_scheduled_occurrence(): void
    {
        DailyWorkReportLog::create([
            'report_setting_id' => $this->settingA->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $this->expectException(QueryException::class);

        // Attempting to insert a duplicate log for the same setting, date, type, and key
        DailyWorkReportLog::create([
            'report_setting_id' => $this->settingA->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);
    }

    public function test_different_settings_can_have_logs_on_same_date_and_type(): void
    {
        $logA = DailyWorkReportLog::create([
            'report_setting_id' => $this->settingA->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $logB = DailyWorkReportLog::create([
            'report_setting_id' => $this->settingB->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $this->assertNotNull($logA->id);
        $this->assertNotNull($logB->id);
        $this->assertNotEquals($logA->id, $logB->id);
    }

    public function test_daily_work_report_uniqueness_and_coexistence(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Two different users can have daily_work logs on the same date (report_setting_id is null)
        $dwr1 = DailyWorkReportLog::create([
            'user_id' => $user1->id,
            'report_setting_id' => null,
            'report_key' => 'daily_work',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $dwr2 = DailyWorkReportLog::create([
            'user_id' => $user2->id,
            'report_setting_id' => null,
            'report_key' => 'daily_work',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $this->assertNotNull($dwr1->id);
        $this->assertNotNull($dwr2->id);

        // A project report log on the same date coexists without conflict
        $projectLog = DailyWorkReportLog::create([
            'report_setting_id' => $this->settingA->id,
            'user_id' => null,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $this->assertNotNull($projectLog->id);

        // Duplicate DWR for user1 throws QueryException due to dwr_logs_user_date_type_key_unique
        $this->expectException(QueryException::class);

        DailyWorkReportLog::create([
            'user_id' => $user1->id,
            'report_setting_id' => null,
            'report_key' => 'daily_work',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);
    }

    public function test_deliveries_relationship_with_project_report_log(): void
    {
        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->settingA->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
        ]);

        $delivery = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'client@example.com',
            'recipient_name' => 'Client User',
            'recipient_type' => 'external',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(DailyWorkReportLog::class, $delivery->reportLog);
        $this->assertEquals($log->id, $delivery->reportLog->id);
        $this->assertTrue($log->deliveries->contains('id', $delivery->id));
    }
}

