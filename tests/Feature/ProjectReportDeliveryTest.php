<?php

namespace Tests\Feature;

use App\Jobs\SendProjectReportDelivery;
use App\Mail\ProjectReportMail;
use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\Reports\ProjectReportAutomationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProjectReportDeliveryTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectReportAutomationService $service;
    protected DailyWorkReportSetting $setting;
    protected ProjectStatus $status;
    protected User $activeUser;
    protected User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProjectReportAutomationService::class);

        $this->status = ProjectStatus::firstOrCreate(
            ['name' => 'Active Status Delivery Test'],
            [
                'color' => '#10b981',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_delivery_test',
            'report_name' => 'Delivery Test Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->status->id],
                    'date_filter' => 'none',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $this->activeUser = User::factory()->create([
            'name' => 'Active Employee',
            'email' => 'active.emp@example.com',
            'is_active' => true,
        ]);

        $this->inactiveUser = User::factory()->create([
            'name' => 'Inactive Employee',
            'email' => 'inactive.emp@example.com',
            'is_active' => false,
        ]);
    }

    public function test_resolve_recipients_filters_active_users_and_deduplicates(): void
    {
        // 1. Valid active internal recipient
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => $this->activeUser->id,
            'name' => 'Old Name In Recipient Table',
            'email' => 'old.email.in.recipient@example.com',
            'is_active' => true,
        ]);

        // 2. Inactive internal user
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => $this->inactiveUser->id,
            'name' => $this->inactiveUser->name,
            'email' => $this->inactiveUser->email,
            'is_active' => true,
        ]);

        // 3. Inactive recipient record
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'Inactive External',
            'email' => 'inactive.external@example.com',
            'is_active' => false,
        ]);

        // 4. Valid external recipient
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'External Client',
            'email' => 'client@external.com',
            'is_active' => true,
        ]);

        // 5. Duplicate of active internal user's current email
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'Duplicate External Entry',
            'email' => 'active.emp@example.com', // same email
            'is_active' => true,
        ]);

        // 6. Invalid email format
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'Invalid Format',
            'email' => 'not-an-email',
            'is_active' => true,
        ]);

        // 7. Expired effective dates
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'Expired Recipient',
            'email' => 'expired@example.com',
            'is_active' => true,
            'effective_until' => Carbon::now()->subDays(5)->toDateString(),
        ]);

        // 8. Future effective date
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'Future Recipient',
            'email' => 'future@example.com',
            'is_active' => true,
            'effective_from' => Carbon::now()->addDays(5)->toDateString(),
        ]);

        $resolved = $this->service->resolveRecipients($this->setting);

        // Only activeUser (with current email) and external client should be resolved
        $this->assertCount(2, $resolved);

        $emails = $resolved->pluck('email')->all();
        $this->assertContains('active.emp@example.com', $emails);
        $this->assertContains('client@external.com', $emails);
        $this->assertNotContains('inactive.emp@example.com', $emails);
        $this->assertNotContains('inactive.external@example.com', $emails);
        $this->assertNotContains('not-an-email', $emails);
        $this->assertNotContains('expired@example.com', $emails);
        $this->assertNotContains('future@example.com', $emails);

        // Verify current user details used
        $internal = $resolved->firstWhere('user_id', $this->activeUser->id);
        $this->assertEquals('Active Employee', $internal['name']);
        $this->assertEquals('active.emp@example.com', $internal['email']);
        $this->assertEquals('internal', $internal['type']);
    }

    public function test_create_deliveries_and_dispatch(): void
    {
        Queue::fake();

        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
            'report_data' => ['metadata' => ['report_name' => 'Test']],
        ]);

        $recipients = collect([
            [
                'email' => 'user1@example.com',
                'name' => 'User 1',
                'type' => 'internal',
                'sources' => ['internal_user'],
            ],
            [
                'email' => 'user2@example.com',
                'name' => 'User 2',
                'type' => 'external',
                'sources' => ['external_email'],
            ],
        ]);

        $deliveries = $this->service->createDeliveriesForLog($log, $recipients);

        $this->assertCount(2, $deliveries);
        $this->assertDatabaseHas('daily_work_report_deliveries', [
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'user1@example.com',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('daily_work_report_deliveries', [
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'user2@example.com',
            'status' => 'pending',
        ]);

        $dispatchedCount = $this->service->dispatchDeliveries($log);

        $this->assertEquals(2, $dispatchedCount);
        $this->assertEquals('queued', $log->fresh()->status);

        Queue::assertPushed(SendProjectReportDelivery::class, 2);
    }

    public function test_send_project_report_delivery_job_sends_email_and_updates_status(): void
    {
        Mail::fake();

        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'queued',
            'report_data' => [
                'metadata' => [
                    'report_name' => 'Delivery Test Report',
                    'generated_at' => Carbon::now()->toDateTimeString(),
                    'is_empty' => false,
                ],
                'columns' => ['project_name' => 'Project Name'],
                'column_keys' => ['project_name'],
                'flat_rows' => [
                    ['values' => ['project_name' => 'Test Project']],
                ],
            ],
        ]);

        $delivery = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'stakeholder@example.com',
            'recipient_name' => 'Key Stakeholder',
            'recipient_type' => 'external',
            'status' => 'queued',
        ]);

        $job = new SendProjectReportDelivery($delivery->id);
        $job->handle();

        // 1. Mail was sent
        Mail::assertSent(ProjectReportMail::class, function ($mail) use ($delivery) {
            return $mail->hasTo($delivery->recipient_email);
        });

        // 2. Delivery status is sent
        $delivery->refresh();
        $this->assertEquals('sent', $delivery->status);
        $this->assertEquals(1, $delivery->attempt_count);
        $this->assertNotNull($delivery->sent_at);
        $this->assertNull($delivery->last_error);

        // 3. Parent log status is sent when all deliveries succeed
        $log->refresh();
        $this->assertEquals('sent', $log->status);
        $this->assertNotNull($log->completed_at);
    }

    public function test_already_sent_delivery_is_not_sent_again(): void
    {
        Mail::fake();

        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'sent',
            'report_data' => ['metadata' => ['report_name' => 'Test']],
        ]);

        $delivery = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'already.sent@example.com',
            'recipient_name' => 'Already Sent User',
            'status' => 'sent',
            'attempt_count' => 1,
            'sent_at' => Carbon::now()->subMinutes(10),
        ]);

        $job = new SendProjectReportDelivery($delivery->id);
        $job->handle();

        // No email sent
        Mail::assertNothingSent();

        // Attempt count unchanged
        $this->assertEquals(1, $delivery->fresh()->attempt_count);
    }

    public function test_delivery_failure_keeps_queued_for_retry(): void
    {
        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'queued',
            'report_data' => ['metadata' => ['report_name' => 'Test']],
        ]);

        $delivery = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'retry.user@example.com',
            'status' => 'queued',
        ]);

        // Force Mail to throw an exception
        Mail::shouldReceive('to->send')->andThrow(new Exception('SMTP Connection timed out.'));

        $job = new SendProjectReportDelivery($delivery->id);

        try {
            $job->handle();
            $this->fail('Exception was expected but not thrown.');
        } catch (Exception $e) {
            $this->assertStringContainsString('SMTP Connection timed out', $e->getMessage());
        }

        $delivery->refresh();
        $this->assertEquals('queued', $delivery->status);
        $this->assertEquals(1, $delivery->attempt_count);
        $this->assertStringContainsString('SMTP Connection timed out', $delivery->last_error);
    }

    public function test_delivery_exhaustion_marks_failed_and_updates_parent_status(): void
    {
        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'queued',
            'report_data' => ['metadata' => ['report_name' => 'Test']],
        ]);

        $delivery1 = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'sent.user@example.com',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $delivery2 = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'failed.user@example.com',
            'status' => 'queued',
            'attempt_count' => 3,
        ]);

        $job = new SendProjectReportDelivery($delivery2->id);
        $job->failed(new Exception('Max retries exceeded.'));

        $delivery2->refresh();
        $this->assertEquals('failed', $delivery2->status);
        $this->assertStringContainsString('Max retries exceeded', $delivery2->last_error);

        // One sent, one failed -> Parent log should be 'partial'
        $log->refresh();
        $this->assertEquals('partial', $log->status);
        $this->assertNotNull($log->completed_at);
    }

    public function test_all_deliveries_failed_sets_parent_log_status_failed(): void
    {
        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'queued',
            'report_data' => ['metadata' => ['report_name' => 'Test']],
        ]);

        $delivery = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'failed.all@example.com',
            'status' => 'queued',
            'attempt_count' => 3,
        ]);

        $job = new SendProjectReportDelivery($delivery->id);
        $job->failed(new Exception('Fatal transmission failure.'));

        $delivery->refresh();
        $this->assertEquals('failed', $delivery->status);

        $log->refresh();
        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->completed_at);
    }

    public function test_generate_and_deliver_with_no_recipients_marks_skipped(): void
    {
        // Setting has no recipients
        $log = $this->service->generateAndDeliver($this->setting);

        $this->assertNotNull($log);
        $this->assertEquals('skipped', $log->status);
        $this->assertNotNull($log->completed_at);
        $this->assertEmpty($log->deliveries);
    }
}

