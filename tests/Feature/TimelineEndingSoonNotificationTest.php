<?php

namespace Tests\Feature;

use App\Console\Commands\SendTimelineEndingSoonNotifications;
use App\Models\HostingProvider;
use App\Models\HostingService;
use App\Models\HostingServiceStatus;
use App\Models\Project;
use App\Models\ProjectHostingRecord;
use App\Models\ProjectNotificationDefault;
use App\Models\ProjectNotificationLog;
use App\Models\ProjectNotificationPreference;
use App\Models\ProjectNotificationSetting;
use App\Models\ProjectNotificationSettingDay;
use App\Models\ProjectNotificationSettingUser;
use App\Models\ProjectTimeline;
use App\Models\User;
use App\Notifications\ProjectNotification;
use App\Services\ProjectNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TimelineEndingSoonNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user1;
    protected User $user2;
    protected User $inactiveUser;
    protected User $deletedUser;
    protected Project $linearProject;
    protected Project $agileProject;
    protected ProjectNotificationSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Create test users
        $this->user1 = User::create([
            'name' => 'Active User One',
            'email' => 'active1@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
            'delete_status' => false,
        ]);

        $this->user2 = User::create([
            'name' => 'Active User Two',
            'email' => 'active2@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
            'delete_status' => false,
        ]);

        $this->inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => bcrypt('secret'),
            'is_active' => false,
            'delete_status' => false,
        ]);

        $this->deletedUser = User::create([
            'name' => 'Deleted User',
            'email' => 'deleted@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
            'delete_status' => true,
        ]);

        $status = \App\Models\ProjectStatus::firstOrCreate(
            ['code' => 'active'],
            ['name' => 'Active', 'type' => \App\Models\ProjectStatus::TYPE_IN_PROGRESS]
        );

        // Create projects
        $this->linearProject = Project::create([
            'name' => 'Linear Flow Project',
            'project_code' => 'PRJ-LIN',
            'project_flow' => 'linear',
            'status_id' => $status->id,
        ]);

        $this->agileProject = Project::create([
            'name' => 'Agile Flow Project',
            'project_code' => 'PRJ-AGL',
            'project_flow' => 'agile',
            'status_id' => $status->id,
        ]);

        // Create global setting for timeline_ending_soon
        $this->setting = ProjectNotificationSetting::create([
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON,
            'is_enabled' => true,
            'created_by' => $this->user1->id,
        ]);

        // Setup ProjectNotificationDefault for timeline_ending_soon
        ProjectNotificationDefault::updateOrCreate(
            ['setting_key' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON, 'conditions' => ['project_flow' => 'linear']],
            ['setting_value' => ['is_enabled' => true]]
        );
        ProjectNotificationDefault::updateOrCreate(
            ['setting_key' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON, 'conditions' => ['project_flow' => 'agile']],
            ['setting_value' => ['is_enabled' => false]]
        );

        // Setup ProjectNotificationDefault for hosting_ending_soon (general default = enabled)
        ProjectNotificationDefault::updateOrCreate(
            ['setting_key' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON, 'conditions' => []],
            ['setting_value' => ['is_enabled' => true]]
        );
    }

    /**
     * Test A: Global setting disabled does not dispatch notifications.
     */
    public function test_a_global_setting_disabled(): void
    {
        $this->setting->update(['is_enabled' => false]);
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline A',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('project_notification_logs', [
            'project_notification_setting_id' => $this->setting->id,
        ]);
    }

    /**
     * Test B: Global setting missing stops processing.
     */
    public function test_b_global_setting_missing(): void
    {
        $this->setting->delete();

        ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline B',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        Notification::assertNothingSent();
    }

    /**
     * Test C: Project preference disabled prevents notification dispatch.
     */
    public function test_c_project_preference_disabled(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        // Explicitly disable for linearProject
        ProjectNotificationPreference::create([
            'project_id' => $this->linearProject->id,
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON,
            'is_enabled' => false,
        ]);

        ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline C',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        Notification::assertNothingSent();
    }

    /**
     * Test D: Project preference enabled allows notification dispatch.
     */
    public function test_d_project_preference_enabled(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        // Explicitly enable for agileProject (overriding default false)
        ProjectNotificationPreference::create([
            'project_id' => $this->agileProject->id,
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON,
            'is_enabled' => true,
        ]);

        $timeline = ProjectTimeline::create([
            'project_id' => $this->agileProject->id,
            'name' => 'Timeline D',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        Notification::assertSentTo($this->user1, ProjectNotification::class);
        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timeline->id,
            'reminder_occasion' => 'days_before_5',
        ]);
    }

    /**
     * Test E: Missing project preference resolves via defaults (linear=enabled, agile=disabled).
     */
    public function test_e_missing_project_preference_resolves_via_defaults(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        // Timeline in linear project (no preference record => default is enabled)
        $timelineLinear = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline Linear Default',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        // Timeline in agile project (no preference record => default is disabled)
        $timelineAgile = ProjectTimeline::create([
            'project_id' => $this->agileProject->id,
            'name' => 'Timeline Agile Default',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timelineLinear->id,
            'reminder_occasion' => 'days_before_5',
        ]);

        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timelineAgile->id,
        ]);
    }

    /**
     * Test F, H, I: Multiple configured intervals evaluated with exact date matching.
     */
    public function test_f_h_i_multiple_configured_intervals_and_missed_interval(): void
    {
        // Configure intervals: 30, 15, 7, 5, 3
        foreach ([30, 15, 7, 5, 3] as $d) {
            ProjectNotificationSettingDay::create([
                'project_notification_setting_id' => $this->setting->id,
                'days_before' => $d,
            ]);
        }
        $this->setting->users()->sync([$this->user1->id]);

        // Due today (exact 15 days before)
        $timeline15 = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline 15 Days Before',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(15),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        // Missed interval date (14 days before: interval 15 passed yesterday, not due today)
        $timeline14 = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline 14 Days (Missed 15)',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(14),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        // Exact interval 15 matches
        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timeline15->id,
            'reminder_occasion' => 'days_before_15',
        ]);

        // Missed interval is never sent
        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timeline14->id,
        ]);
    }

    /**
     * Test G, J, K, L: Expiry today, 1 day after, and 2+ days after when no intervals are configured.
     */
    public function test_g_j_k_l_expiry_and_one_day_after_without_intervals(): void
    {
        // No intervals configured
        $this->setting->days()->delete();
        $this->setting->users()->sync([$this->user1->id]);

        // J: Expiry today
        $timelineExpiryToday = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline Expiry Today',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today(),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        // K: Expired 1 day ago
        $timelineExpiredYesterday = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline Expired Yesterday',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today()->subDays(11),
            'end_date' => Carbon::today()->subDays(1),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        // L: Expired 2 days ago (not due)
        $timelineExpired2DaysAgo = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline Expired 2 Days Ago',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today()->subDays(12),
            'end_date' => Carbon::today()->subDays(2),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timelineExpiryToday->id,
            'reminder_occasion' => 'on_expiry_date',
        ]);

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timelineExpiredYesterday->id,
            'reminder_occasion' => '1_day_after',
        ]);

        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $timelineExpired2DaysAgo->id,
        ]);
    }

    /**
     * Test M: Multiple timelines in the same project are dispatched independently.
     */
    public function test_m_multiple_timelines_in_same_project(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 7,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        $tl1 = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline M1',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(7),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $tl2 = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline M2',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(7),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processTimelineEndingSoon();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $tl1->id,
            'reminder_occasion' => 'days_before_7',
        ]);

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectTimeline::class,
            'link_id' => $tl2->id,
            'reminder_occasion' => 'days_before_7',
        ]);
    }

    /**
     * Test N, O, P, Q, R, S: Recipient resolution with active, inactive, deleted, and deduplicated external emails.
     */
    public function test_n_o_p_q_r_s_recipient_resolution(): void
    {
        // Setting with: user1, user2, inactiveUser, deletedUser, and external emails
        $this->setting->users()->sync([
            $this->user1->id,
            $this->user2->id,
            $this->inactiveUser->id,
            $this->deletedUser->id,
        ]);

        // Add external emails: one duplicate of user1, and one new external email
        ProjectNotificationSettingUser::create([
            'project_notification_setting_id' => $this->setting->id,
            'email' => 'active1@example.com', // Duplicate of user1
        ]);
        ProjectNotificationSettingUser::create([
            'project_notification_setting_id' => $this->setting->id,
            'email' => 'client@external.com',
        ]);

        $service = app(ProjectNotificationService::class);
        $recipients = $service->getRecipients($this->setting);

        // Valid users: user1 and user2 only (inactive and deleted excluded)
        $userIds = $recipients['users']->pluck('id')->all();
        $this->assertContains($this->user1->id, $userIds);
        $this->assertContains($this->user2->id, $userIds);
        $this->assertNotContains($this->inactiveUser->id, $userIds);
        $this->assertNotContains($this->deletedUser->id, $userIds);

        // Additional emails: client@external.com only (active1@example.com deduplicated)
        $this->assertEquals(['client@external.com'], $recipients['additional_emails']);

        // S: No valid recipients scenario
        $emptySetting = ProjectNotificationSetting::create([
            'notification_type' => 'test_empty',
            'is_enabled' => true,
        ]);
        $emptyRecipients = $service->getRecipients($emptySetting);
        $this->assertTrue($emptyRecipients['users']->isEmpty());
        $this->assertEmpty($emptyRecipients['additional_emails']);
    }

    /**
     * Test T, U: Repeated command execution and concurrent duplicate prevention.
     */
    public function test_t_u_repeated_command_execution_prevents_duplicate(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 3,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        $tl = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline Duplicate Check',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(3),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        $service = app(ProjectNotificationService::class);

        // Run 1
        $service->processTimelineEndingSoon();

        // Run 2 (repeated on same day)
        $service->processTimelineEndingSoon();

        // Exactly one log entry must exist
        $count = ProjectNotificationLog::where('link_type', ProjectTimeline::class)
            ->where('link_id', $tl->id)
            ->where('scheduled_for', Carbon::today(config('app.timezone'))->toDateString())
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test V: Stale queued timeline notification is rejected by shouldSend guard and marked failed.
     */
    public function test_v_stale_queued_timeline_notification_rejected(): void
    {
        $tl = ProjectTimeline::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Timeline Stale Queue Test',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today(),
            'type' => 'new',
            'created_by' => $this->user1->id,
        ]);

        // Create log scheduled yesterday (stale)
        $yesterdayStr = Carbon::yesterday(config('app.timezone'))->toDateString();
        $staleLog = ProjectNotificationLog::create([
            'project_notification_setting_id' => $this->setting->id,
            'link_type' => ProjectTimeline::class,
            'link_id' => $tl->id,
            'scheduled_for' => $yesterdayStr,
            'reminder_occasion' => 'days_before_5',
            'status' => ProjectNotificationLog::STATUS_QUEUED,
        ]);

        $notification = new ProjectNotification(
            'Title',
            'Message',
            null,
            ['mail'],
            $this->linearProject->id,
            [],
            $staleLog->id
        );

        // Call shouldSend today
        $shouldSend = $notification->shouldSend($this->user1, 'mail');

        $this->assertFalse($shouldSend);
        $staleLog->refresh();
        $this->assertEquals(ProjectNotificationLog::STATUS_FAILED, $staleLog->status);

        // Fresh log scheduled today must return true
        $todayStr = Carbon::today(config('app.timezone'))->toDateString();
        $freshLog = ProjectNotificationLog::create([
            'project_notification_setting_id' => $this->setting->id,
            'link_type' => ProjectTimeline::class,
            'link_id' => $tl->id,
            'scheduled_for' => $todayStr,
            'reminder_occasion' => 'days_before_3',
            'status' => ProjectNotificationLog::STATUS_QUEUED,
        ]);

        $freshNotification = new ProjectNotification(
            'Title',
            'Message',
            null,
            ['mail'],
            $this->linearProject->id,
            [],
            $freshLog->id
        );

        $this->assertTrue($freshNotification->shouldSend($this->user1, 'mail'));
    }

    /**
     * Test W: Hosting notification behavior remains unchanged and shouldSend does not cancel hosting.
     */
    public function test_w_hosting_notifications_behavior_unchanged(): void
    {
        // Create hosting setup
        $provider = HostingProvider::firstOrCreate(
            ['code' => 'prov_test'],
            ['name' => 'Provider Test']
        );
        $serviceType = HostingService::firstOrCreate(
            ['code' => 'serv_test'],
            ['name' => 'Service Test']
        );
        $statusActive = HostingServiceStatus::firstOrCreate(['code' => 'active'], ['name' => 'Active']);

        $hostingRecord = ProjectHostingRecord::create([
            'project_id' => $this->linearProject->id,
            'name' => 'Test Hosting Record',
            'hosting_provider_id' => $provider->id,
            'hosting_service_id' => $serviceType->id,
            'hosting_service_status_id' => $statusActive->id,
            'expiry_date' => Carbon::today(),
        ]);

        $hostingSetting = ProjectNotificationSetting::create([
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
            'is_enabled' => true,
        ]);
        $hostingSetting->users()->sync([$this->user1->id]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $hostingRecord->id,
            'reminder_occasion' => 'on_expiry_date',
        ]);

        // Also verify shouldSend allows hosting logs even if scheduled date differs
        $hostingLog = ProjectNotificationLog::where('link_type', ProjectHostingRecord::class)
            ->where('link_id', $hostingRecord->id)
            ->first();

        $hostingNotification = new ProjectNotification(
            'Hosting Title',
            'Hosting Message',
            null,
            ['mail'],
            $this->linearProject->id,
            [],
            $hostingLog->id
        );

        $this->assertTrue($hostingNotification->shouldSend($this->user1, 'mail'));
    }
}
