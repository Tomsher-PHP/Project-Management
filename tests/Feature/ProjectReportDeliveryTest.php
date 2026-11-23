<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateDailyWorkReports;
use App\Console\Commands\GenerateProjectReports;
use App\Jobs\SendDailyWorkReportDelivery;
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
use Illuminate\Support\Facades\Artisan;
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
    protected Project $matchingProject;

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
            'setting_key' => 'project_report_delivery_test_' . uniqid(),
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
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $this->activeUser = User::factory()->create([
            'name' => 'Active Employee',
            'email' => 'active.emp.' . uniqid() . '@example.com',
            'is_active' => true,
        ]);

        $this->inactiveUser = User::factory()->create([
            'name' => 'Inactive Employee',
            'email' => 'inactive.emp.' . uniqid() . '@example.com',
            'is_active' => false,
        ]);

        $this->matchingProject = Project::create([
            'name' => 'Delivery Test Project ' . uniqid(),
            'project_code' => 'PRJ-DELIV-' . rand(1000, 9999),
            'status_id' => $this->status->id,
            'project_flow' => 'agile',
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
            'email' => $this->activeUser->email, // same email
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
        $this->assertContains($this->activeUser->email, $emails);
        $this->assertContains('client@external.com', $emails);
        $this->assertNotContains($this->inactiveUser->email, $emails);
        $this->assertNotContains('inactive.external@example.com', $emails);
        $this->assertNotContains('not-an-email', $emails);
        $this->assertNotContains('expired@example.com', $emails);
        $this->assertNotContains('future@example.com', $emails);

        // Verify current user details used
        $internal = $resolved->firstWhere('user_id', $this->activeUser->id);
        $this->assertEquals('Active Employee', $internal['name']);
        $this->assertEquals($this->activeUser->email, $internal['email']);
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

        // Exactly one delivery record created per report
        $this->assertCount(1, $deliveries);
        $this->assertDatabaseHas('daily_work_report_deliveries', [
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'user1@example.com',
            'status' => 'pending',
        ]);

        $dispatchedCount = $this->service->dispatchDeliveries($log);

        // Exactly one job dispatched per report
        $this->assertEquals(1, $dispatchedCount);
        $this->assertEquals('queued', $log->fresh()->status);

        Queue::assertPushed(SendProjectReportDelivery::class, 1);
    }

    public function test_scenario_1_five_internal_recipients_sends_one_email_with_five_to_addresses(): void
    {
        Mail::fake();

        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = User::factory()->create([
                'name' => "Internal User $i",
                'email' => "internal{$i}." . uniqid() . "@example.com",
                'is_active' => true,
            ]);
            DailyWorkReportRecipient::create([
                'report_key' => 'project_report',
                'report_setting_id' => $this->setting->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => true,
            ]);
            $users[] = $user;
        }

        $log = $this->service->generateAndDeliver($this->setting);

        $this->assertNotNull($log);
        $this->assertEquals('queued', $log->status);
        $this->assertCount(1, $log->deliveries);

        // Execute the single queued delivery job
        $job = new SendProjectReportDelivery($log->deliveries->first()->id);
        $job->handle();

        // Exactly one email sent
        Mail::assertSent(ProjectReportMail::class, 1);

        Mail::assertSent(ProjectReportMail::class, function (ProjectReportMail $mail) use ($users) {
            // All 5 internal recipients must be in TO
            $this->assertCount(5, $mail->to);
            foreach ($users as $user) {
                $this->assertTrue($mail->hasTo($user->email));
            }
            // CC must be empty
            $this->assertEmpty($mail->cc);

            return true;
        });

        $this->assertEquals('sent', $log->deliveries->first()->fresh()->status);
        $this->assertEquals('sent', $log->fresh()->status);
    }

    public function test_scenario_2_three_external_recipients_sends_one_email_with_mail_from_address_in_to_and_three_cc_addresses(): void
    {
        Mail::fake();
        config(['mail.from.address' => 'noreply@projectmgmt.com']);

        $externalEmails = [
            'ext1.' . uniqid() . '@partner.com',
            'ext2.' . uniqid() . '@partner.com',
            'ext3.' . uniqid() . '@partner.com',
        ];

        foreach ($externalEmails as $email) {
            DailyWorkReportRecipient::create([
                'report_key' => 'project_report',
                'report_setting_id' => $this->setting->id,
                'user_id' => null,
                'name' => 'External Partner',
                'email' => $email,
                'is_active' => true,
            ]);
        }

        $log = $this->service->generateAndDeliver($this->setting);

        $this->assertNotNull($log);
        $this->assertEquals('queued', $log->status);
        $this->assertCount(1, $log->deliveries);

        $job = new SendProjectReportDelivery($log->deliveries->first()->id);
        $job->handle();

        Mail::assertSent(ProjectReportMail::class, 1);

        Mail::assertSent(ProjectReportMail::class, function (ProjectReportMail $mail) use ($externalEmails) {
            // Fallback MAIL_FROM_ADDRESS must be in TO
            $this->assertTrue($mail->hasTo('noreply@projectmgmt.com'));
            $this->assertCount(1, $mail->to);

            // All 3 external recipients must be in CC
            $this->assertCount(3, $mail->cc);
            foreach ($externalEmails as $email) {
                $this->assertTrue($mail->hasCc($email));
            }
            // Fallback TO must not be in CC
            $this->assertFalse($mail->hasCc('noreply@projectmgmt.com'));

            return true;
        });
    }

    public function test_scenario_3_internal_and_external_recipients_sends_one_email_with_correct_to_and_cc_addresses(): void
    {
        Mail::fake();

        $internalUser = User::factory()->create([
            'name' => 'Internal Manager',
            'email' => 'manager.' . uniqid() . '@company.com',
            'is_active' => true,
        ]);
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => $internalUser->id,
            'name' => $internalUser->name,
            'email' => $internalUser->email,
            'is_active' => true,
        ]);

        $externalEmail = 'client.' . uniqid() . '@client.org';
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'External Client',
            'email' => $externalEmail,
            'is_active' => true,
        ]);

        $log = $this->service->generateAndDeliver($this->setting);

        $job = new SendProjectReportDelivery($log->deliveries->first()->id);
        $job->handle();

        Mail::assertSent(ProjectReportMail::class, 1);

        Mail::assertSent(ProjectReportMail::class, function (ProjectReportMail $mail) use ($internalUser, $externalEmail) {
            // Internal in TO
            $this->assertTrue($mail->hasTo($internalUser->email));
            $this->assertCount(1, $mail->to);

            // External in CC
            $this->assertTrue($mail->hasCc($externalEmail));
            $this->assertCount(1, $mail->cc);

            return true;
        });
    }

    public function test_scenario_4_no_eligible_recipients_skips_email_generation_and_dispatch(): void
    {
        Mail::fake();
        Queue::fake();

        // No recipients added to $this->setting
        $log = $this->service->generateAndDeliver($this->setting);

        $this->assertNotNull($log);
        $this->assertEquals('skipped', $log->status);
        $this->assertNotNull($log->completed_at);
        $this->assertEmpty($log->deliveries);

        Mail::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_scenario_5_no_matching_projects_skips_email_generation_and_dispatch(): void
    {
        Mail::fake();
        Queue::fake();

        // Create an empty status with 0 projects
        $emptyStatus = ProjectStatus::create([
            'name' => 'Empty Status ' . uniqid(),
            'color' => '#6b7280',
            'type' => ProjectStatus::TYPE_IN_PROGRESS,
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $emptySetting = DailyWorkReportSetting::create([
            'setting_key' => 'empty_project_test_' . uniqid(),
            'report_name' => 'Empty Project Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$emptyStatus->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $emptySetting->id,
            'user_id' => $this->activeUser->id,
            'name' => $this->activeUser->name,
            'email' => $this->activeUser->email,
            'is_active' => true,
        ]);

        $log = $this->service->generateAndDeliver($emptySetting);

        $this->assertNotNull($log);
        $this->assertEquals('skipped', $log->status);
        $this->assertTrue($log->report_data['metadata']['is_empty']);
        $this->assertEquals(0, $log->report_data['metadata']['total_projects']);
        $this->assertNotNull($log->completed_at);
        $this->assertEmpty($log->deliveries);

        Mail::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_scenario_6_duplicate_addresses_deduplicated_correctly_prioritizing_to(): void
    {
        Mail::fake();

        $sharedEmail = 'shared.' . uniqid() . '@example.com';

        // Internal user with shared email
        $user = User::factory()->create([
            'name' => 'Internal Duplicate User',
            'email' => $sharedEmail,
            'is_active' => true,
        ]);
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => true,
        ]);

        // External recipient with SAME email (uppercase)
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'External Duplicate',
            'email' => strtoupper($sharedEmail),
            'is_active' => true,
        ]);

        // Distinct external recipient
        $uniqueExternal = 'unique.' . uniqid() . '@partner.com';
        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => null,
            'name' => 'Unique External',
            'email' => $uniqueExternal,
            'is_active' => true,
        ]);

        $log = $this->service->generateAndDeliver($this->setting);

        $job = new SendProjectReportDelivery($log->deliveries->first()->id);
        $job->handle();

        Mail::assertSent(ProjectReportMail::class, 1);

        Mail::assertSent(ProjectReportMail::class, function (ProjectReportMail $mail) use ($sharedEmail, $uniqueExternal) {
            // Shared email must appear in TO
            $this->assertTrue($mail->hasTo($sharedEmail));
            $this->assertCount(1, $mail->to);

            // Shared email must NOT appear in CC (TO prioritized)
            $this->assertFalse($mail->hasCc($sharedEmail));

            // Unique external is in CC
            $this->assertTrue($mail->hasCc($uniqueExternal));
            $this->assertCount(1, $mail->cc);

            return true;
        });
    }

    public function test_scenario_7_repeated_scheduler_execution_does_not_send_duplicate_email(): void
    {
        // Set delivery time to current time in Asia/Dubai
        $now = Carbon::now('Asia/Dubai');
        $this->setting->update([
            'delivery_time' => $now->format('H:i:s'),
        ]);

        DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->setting->id,
            'user_id' => $this->activeUser->id,
            'name' => $this->activeUser->name,
            'email' => $this->activeUser->email,
            'is_active' => true,
        ]);

        Queue::fake();

        // 1. First execution
        Artisan::call('app:generate-project-reports');
        Queue::assertPushed(SendProjectReportDelivery::class, 1);

        $logCount = DailyWorkReportLog::where('report_setting_id', $this->setting->id)
            ->where('report_date', $now->toDateString())
            ->count();
        $this->assertEquals(1, $logCount);

        // 2. Second immediate execution
        Artisan::call('app:generate-project-reports');

        // Still exactly 1 job pushed and 1 log created
        Queue::assertPushed(SendProjectReportDelivery::class, 1);
        $this->assertEquals(1, DailyWorkReportLog::where('report_setting_id', $this->setting->id)
            ->where('report_date', $now->toDateString())
            ->count());
    }

    public function test_scenario_8_email_failure_and_retry_behavior(): void
    {
        $log = DailyWorkReportLog::create([
            'report_setting_id' => $this->setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'queued',
            'report_data' => ['metadata' => ['report_name' => 'Retry Test']],
        ]);

        $delivery = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $log->id,
            'recipient_email' => 'retry.user@example.com',
            'status' => 'queued',
        ]);

        // 1. Force failure on first attempt
        Mail::shouldReceive('to->send')->andThrow(new Exception('Temporary SMTP transmission failure.'));

        $job = new SendProjectReportDelivery($delivery->id);

        try {
            $job->handle();
            $this->fail('Exception was expected but not thrown.');
        } catch (Exception $e) {
            $this->assertStringContainsString('Temporary SMTP transmission failure', $e->getMessage());
        }

        $delivery->refresh();
        $this->assertEquals('queued', $delivery->status);
        $this->assertEquals(1, $delivery->attempt_count);
        $this->assertStringContainsString('Temporary SMTP transmission failure', $delivery->last_error);
        $this->assertEquals('queued', $log->fresh()->status);

        // 2. Simulate exhausted attempts calling failed()
        $job->failed(new Exception('Maximum retry attempts exhausted.'));

        $delivery->refresh();
        $this->assertEquals('failed', $delivery->status);
        $this->assertStringContainsString('Maximum retry attempts exhausted', $delivery->last_error);

        $log->refresh();
        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->completed_at);
    }

    public function test_scenario_9_daily_work_report_functionality_remains_unchanged(): void
    {
        // 1. DailyWorkReportSetting can be created and queried
        $dwrSetting = DailyWorkReportSetting::create([
            'setting_key' => 'dwr_regression_check_' . uniqid(),
            'report_name' => 'Original DWR',
            'report_key' => 'daily_work_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '17:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'previous_day',
            'settings' => [],
        ]);

        $this->assertNotNull($dwrSetting->id);
        $this->assertEquals('daily_work_report', $dwrSetting->report_key);

        // 2. DailyWorkReportLog and Delivery models operate correctly
        $dwrLog = DailyWorkReportLog::create([
            'report_setting_id' => $dwrSetting->id,
            'report_key' => 'daily_work_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
            'report_data' => ['dwr' => true],
        ]);

        $dwrDelivery = DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $dwrLog->id,
            'recipient_email' => 'employee@example.com',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(DailyWorkReportLog::class, $dwrDelivery->reportLog);
        $this->assertCount(1, $dwrLog->deliveries);

        // 3. Original SendDailyWorkReportDelivery class exists and implements ShouldQueue
        $this->assertTrue(is_subclass_of(SendDailyWorkReportDelivery::class, \Illuminate\Contracts\Queue\ShouldQueue::class));

        // 4. GenerateDailyWorkReports command is registered and executable
        $this->assertTrue(class_exists(GenerateDailyWorkReports::class));
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

    public function test_daily_work_report_handles_duplicate_recipient_safely(): void
    {
        $log = DailyWorkReportLog::create([
            'user_id' => null,
            'report_key' => 'daily_work',
            'report_type' => 'daily',
            'report_date' => '2026-11-23',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
            'report_data' => ['dwr' => true],
        ]);

        $recipientService = app(\App\Services\DailyWorkReportRecipientService::class);
        $user = User::factory()->create([
            'email' => 'swethavprasannan.tomsher@gmail.com',
        ]);

        // Create global recipient with same email in different case
        DailyWorkReportRecipient::create([
            'report_key' => 'daily_work',
            'scope' => 'global',
            'name' => 'Swetha',
            'email' => 'Swethavprasannan.tomsher@gmail.com',
            'is_active' => true,
        ]);

        $recipients = $recipientService->resolveForHierarchy(
            $user,
            collect(),
            '2026-11-23',
            'daily_work'
        );

        $this->assertCount(1, $recipients);

        $uniqueRecipients = collect($recipients)
            ->map(function ($recipient) {
                $recipient = (array) $recipient;
                $recipient['email'] = mb_strtolower(trim((string) ($recipient['email'] ?? '')));

                return $recipient;
            })
            ->filter(fn ($r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
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

        // Running insertion again with duplicates does not throw unique constraint violation
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

        $this->assertEquals(1, DailyWorkReportDelivery::where('daily_work_report_log_id', $log->id)->count());
    }

    public function test_daily_work_report_log_stores_generated_at_in_app_timezone(): void
    {
        // 1. Setting generated_at using a non-UTC Carbon instance
        $dubaiTime = Carbon::parse('2026-11-23 15:30:00', 'Asia/Dubai'); // 11:30:00 UTC
        $log = DailyWorkReportLog::create([
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-23',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
            'generated_at' => $dubaiTime,
            'completed_at' => $dubaiTime,
        ]);

        $this->assertEquals('2026-11-23 11:30:00', $log->getRawOriginal('generated_at'));
        $this->assertEquals('2026-11-23 11:30:00', $log->getRawOriginal('completed_at'));

        // 2. ProjectReportAutomationService generateAndDeliver stores in app.timezone
        $automationService = app(ProjectReportAutomationService::class);
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'tz_test_' . uniqid(),
            'report_name' => 'TZ Test Report',
            'report_key' => 'project_report',
            'timezone' => 'Asia/Dubai',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'reporting_period' => 'all_time',
        ]);

        $referenceDate = Carbon::parse('2026-11-23 14:00:00', 'Asia/Dubai'); // 10:00:00 UTC
        $generatedLog = $automationService->generateAndDeliver($setting, $referenceDate);

        $this->assertNotNull($generatedLog);
        $this->assertEquals('2026-11-23 10:00:00', $generatedLog->getRawOriginal('generated_at'));
    }
}
