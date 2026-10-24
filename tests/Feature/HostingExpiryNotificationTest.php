<?php

namespace Tests\Feature;

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
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\User;
use App\Notifications\ProjectNotification;
use App\Services\ProjectNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HostingExpiryNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user1;
    protected User $user2;
    protected User $inactiveUser;
    protected User $deletedUser;
    protected Project $project;
    protected ProjectNotificationSetting $setting;
    protected HostingProvider $provider;
    protected HostingService $serviceType;
    protected HostingServiceStatus $statusActive;
    protected HostingServiceStatus $statusCancelled;
    protected HostingServiceStatus $statusTerminated;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Create test users
        $this->user1 = User::create([
            'name' => 'Active Hosting User One',
            'email' => 'hosting_active1@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
            'delete_status' => false,
        ]);

        $this->user2 = User::create([
            'name' => 'Active Hosting User Two',
            'email' => 'hosting_active2@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
            'delete_status' => false,
        ]);

        $this->inactiveUser = User::create([
            'name' => 'Inactive Hosting User',
            'email' => 'hosting_inactive@example.com',
            'password' => bcrypt('secret'),
            'is_active' => false,
            'delete_status' => false,
        ]);

        $this->deletedUser = User::create([
            'name' => 'Deleted Hosting User',
            'email' => 'hosting_deleted@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
            'delete_status' => true,
        ]);

        $projectStatus = ProjectStatus::firstOrCreate(
            ['code' => 'active'],
            ['name' => 'Active', 'type' => ProjectStatus::TYPE_IN_PROGRESS]
        );

        $this->project = Project::create([
            'name' => 'Hosting Test Project',
            'project_code' => 'PRJ-HOST-01',
            'project_flow' => 'linear',
            'status_id' => $projectStatus->id,
        ]);

        $this->provider = HostingProvider::firstOrCreate(
            ['code' => 'prov_host_test'],
            ['name' => 'Hosting Provider Test']
        );

        $this->serviceType = HostingService::firstOrCreate(
            ['code' => 'serv_host_test'],
            ['name' => 'Hosting Service Test']
        );

        $this->statusActive = HostingServiceStatus::firstOrCreate(
            ['code' => 'active'],
            ['name' => 'Active']
        );

        $this->statusCancelled = HostingServiceStatus::firstOrCreate(
            ['code' => 'cancelled'],
            ['name' => 'Cancelled']
        );

        $this->statusTerminated = HostingServiceStatus::firstOrCreate(
            ['code' => 'terminated'],
            ['name' => 'Terminated']
        );

        // Create global setting for hosting_ending_soon
        $this->setting = ProjectNotificationSetting::create([
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
            'is_enabled' => true,
            'created_by' => $this->user1->id,
        ]);

        // Setup ProjectNotificationDefault for hosting_ending_soon (general default: enabled)
        ProjectNotificationDefault::updateOrCreate(
            ['setting_key' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON, 'conditions' => []],
            ['setting_value' => ['is_enabled' => true]]
        );
    }

    /**
     * Test 1: Global setting disabled does not dispatch notifications or create logs.
     */
    public function test_1_global_setting_disabled(): void
    {
        $this->setting->update(['is_enabled' => false]);
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Global Disabled',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record->id,
        ]);
    }

    /**
     * Test 2: Global setting missing stops processing.
     */
    public function test_2_global_setting_missing(): void
    {
        $this->setting->delete();

        ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Missing Setting',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        Notification::assertNothingSent();
    }

    /**
     * Test 3: Project preference disabled skips that project's hosting records.
     */
    public function test_3_project_hosting_preference_disabled(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        ProjectNotificationPreference::create([
            'project_id' => $this->project->id,
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
            'is_enabled' => false,
        ]);

        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Preference Disabled',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record->id,
        ]);
    }

    /**
     * Test 4: Project preference enabled allows processing.
     */
    public function test_4_project_hosting_preference_enabled(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        ProjectNotificationPreference::create([
            'project_id' => $this->project->id,
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
            'is_enabled' => true,
        ]);

        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Preference Enabled',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        Notification::assertSentTo($this->user1, ProjectNotification::class);
        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record->id,
            'reminder_occasion' => 'days_before_5',
        ]);
    }

    /**
     * Test 5: Missing preference resolved through configured defaults.
     */
    public function test_5_missing_preference_resolved_through_defaults(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        // No preference created. Default is is_enabled = true.
        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Default Preference',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record->id,
            'reminder_occasion' => 'days_before_5',
        ]);

        // When default is disabled:
        ProjectNotificationDefault::where('setting_key', ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON)
            ->update(['setting_value' => ['is_enabled' => false]]);

        $record2 = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Default Disabled',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service->processHostingExpiryNotifications();

        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record2->id,
        ]);
    }

    /**
     * Test 6, 7, 8: Multiple configured intervals, exact-date matching, and missed intervals not sent late.
     */
    public function test_6_7_8_multiple_configured_intervals_and_missed_intervals(): void
    {
        foreach ([30, 15, 7, 5, 3] as $d) {
            ProjectNotificationSettingDay::create([
                'project_notification_setting_id' => $this->setting->id,
                'days_before' => $d,
            ]);
        }
        $this->setting->users()->sync([$this->user1->id]);

        // Record due today (exact 15 days before)
        $recordDue = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Due 15 Days',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(15),
        ]);

        // Record 14 days before (missed 15-day interval yesterday)
        $recordMissed = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Missed 14 Days',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(14),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        // Due record matches exact interval
        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $recordDue->id,
            'reminder_occasion' => 'days_before_15',
        ]);

        // Missed record is never sent late
        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $recordMissed->id,
        ]);
    }

    /**
     * Test 9, 10, 11, 12: Expiry-day, one-day-after, and no repeat on 2+ days overdue without intervals.
     */
    public function test_9_10_11_12_expiry_day_and_one_day_after_without_intervals(): void
    {
        $this->setting->days()->delete();
        $this->setting->users()->sync([$this->user1->id]);

        // Expiry today
        $recordExpiryToday = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Expiry Today',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today(),
        ]);

        // Expired yesterday (1 day after)
        $recordExpiredYesterday = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Expired Yesterday',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->subDays(1),
        ]);

        // Expired 2 days ago (should not notify)
        $recordExpired2DaysAgo = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Record Expired 2 Days Ago',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->subDays(2),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $recordExpiryToday->id,
            'reminder_occasion' => 'on_expiry_date',
        ]);

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $recordExpiredYesterday->id,
            'reminder_occasion' => '1_day_after',
        ]);

        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $recordExpired2DaysAgo->id,
        ]);
    }

    /**
     * Test 13: Multiple hosting records in the same project are dispatched independently.
     */
    public function test_13_multiple_hosting_records_in_same_project(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 7,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        $rec1 = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Hosting Record 1',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(7),
        ]);

        $rec2 = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Hosting Record 2',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(7),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $rec1->id,
            'reminder_occasion' => 'days_before_7',
        ]);

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $rec2->id,
            'reminder_occasion' => 'days_before_7',
        ]);
    }

    /**
     * Test 14, 15, 16, 17, 18: Recipients handling with internal, external, deduplicated, inactive, deleted, and empty.
     */
    public function test_14_15_16_17_18_recipient_handling(): void
    {
        $this->setting->users()->sync([
            $this->user1->id,
            $this->user2->id,
            $this->inactiveUser->id,
            $this->deletedUser->id,
        ]);

        // Duplicate external email matching user1 with mixed case
        ProjectNotificationSettingUser::create([
            'project_notification_setting_id' => $this->setting->id,
            'email' => 'HOSTING_ACTIVE1@EXAMPLE.COM',
        ]);

        // Valid external email
        ProjectNotificationSettingUser::create([
            'project_notification_setting_id' => $this->setting->id,
            'email' => 'ops@external-hosting.com',
        ]);

        $service = app(ProjectNotificationService::class);
        $recipients = $service->getRecipients($this->setting);

        // Internal active users only
        $userIds = $recipients['users']->pluck('id')->all();
        $this->assertContains($this->user1->id, $userIds);
        $this->assertContains($this->user2->id, $userIds);
        $this->assertNotContains($this->inactiveUser->id, $userIds);
        $this->assertNotContains($this->deletedUser->id, $userIds);

        // External email deduplicated case-insensitively
        $this->assertEquals(['ops@external-hosting.com'], $recipients['additional_emails']);

        // Empty recipients scenario
        $emptySetting = ProjectNotificationSetting::create([
            'notification_type' => 'hosting_test_empty',
            'is_enabled' => true,
        ]);
        $emptyRecipients = $service->getRecipients($emptySetting);
        $this->assertTrue($emptyRecipients['users']->isEmpty());
        $this->assertEmpty($emptyRecipients['additional_emails']);
    }

    /**
     * Test 19, 20: Repeated command executions and duplicate prevention.
     */
    public function test_19_20_repeated_command_execution_prevents_duplicates(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 3,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Hosting Duplicate Test',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(3),
        ]);

        $service = app(ProjectNotificationService::class);

        // Run 1
        $service->processHostingExpiryNotifications();

        // Run 2 (repeated on same day)
        $service->processHostingExpiryNotifications();

        $count = ProjectNotificationLog::where('link_type', ProjectHostingRecord::class)
            ->where('link_id', $record->id)
            ->where('scheduled_for', Carbon::today(config('app.timezone'))->toDateString())
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test 21: Stale queued hosting notification is cancelled by shouldSend and marked failed.
     */
    public function test_21_stale_queued_hosting_notifications_not_delivered(): void
    {
        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Hosting Stale Queue Test',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today(),
        ]);

        $yesterdayStr = Carbon::yesterday(config('app.timezone'))->toDateString();
        $staleLog = ProjectNotificationLog::create([
            'project_notification_setting_id' => $this->setting->id,
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record->id,
            'scheduled_for' => $yesterdayStr,
            'reminder_occasion' => 'on_expiry_date',
            'status' => ProjectNotificationLog::STATUS_QUEUED,
        ]);

        $notification = new ProjectNotification(
            'Hosting Title',
            'Hosting Message',
            null,
            ['mail'],
            $this->project->id,
            [],
            $staleLog->id
        );

        $shouldSend = $notification->shouldSend($this->user1, 'mail');

        $this->assertFalse($shouldSend);
        $staleLog->refresh();
        $this->assertEquals(ProjectNotificationLog::STATUS_FAILED, $staleLog->status);
    }

    /**
     * Test 22: Timeline notifications still work after shared-code changes.
     */
    public function test_22_timeline_notifications_still_work(): void
    {
        $timelineSetting = ProjectNotificationSetting::create([
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON,
            'is_enabled' => true,
        ]);
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $timelineSetting->id,
            'days_before' => 5,
        ]);
        $timelineSetting->users()->sync([$this->user1->id]);

        ProjectNotificationDefault::updateOrCreate(
            ['setting_key' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON, 'conditions' => ['project_flow' => 'linear']],
            ['setting_value' => ['is_enabled' => true]]
        );

        $timeline = ProjectTimeline::create([
            'project_id' => $this->project->id,
            'name' => 'Timeline Regression Check',
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
            'link_id' => $timeline->id,
            'reminder_occasion' => 'days_before_5',
        ]);
    }

    /**
     * Test 23: Hosting and timeline preferences remain independent.
     */
    public function test_23_hosting_and_timeline_preferences_independent(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        // Disable timeline preference, enable hosting preference
        ProjectNotificationPreference::create([
            'project_id' => $this->project->id,
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON,
            'is_enabled' => false,
        ]);
        ProjectNotificationPreference::create([
            'project_id' => $this->project->id,
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
            'is_enabled' => true,
        ]);

        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Hosting Preference Independent Record',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        // Hosting notification is dispatched despite timeline being disabled
        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record->id,
            'reminder_occasion' => 'days_before_5',
        ]);
    }

    /**
     * Test 24: Hosting renewal or expiry-date changes cancel obsolete queued notifications.
     */
    public function test_24_hosting_renewal_cancels_obsolete_notification(): void
    {
        $todayStr = Carbon::today(config('app.timezone'))->toDateString();

        $record = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Hosting Renewed Record',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today(), // Was expiring today
        ]);

        $log = ProjectNotificationLog::create([
            'project_notification_setting_id' => $this->setting->id,
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $record->id,
            'scheduled_for' => $todayStr,
            'reminder_occasion' => 'on_expiry_date',
            'status' => ProjectNotificationLog::STATUS_QUEUED,
        ]);

        // Before queue worker runs, user renews the hosting service (expiry date pushed by 1 year)
        $record->update([
            'expiry_date' => Carbon::today()->addYear(),
        ]);

        $notification = new ProjectNotification(
            'Hosting Expiring Today',
            'Hosting message',
            null,
            ['mail'],
            $this->project->id,
            [],
            $log->id
        );

        // shouldSend must detect that occasion 'on_expiry_date' is no longer valid for the renewed date
        $shouldSend = $notification->shouldSend($this->user1, 'mail');

        $this->assertFalse($shouldSend);
        $log->refresh();
        $this->assertEquals(ProjectNotificationLog::STATUS_FAILED, $log->status);
    }

    /**
     * Test 25: Existing hosting statuses are respected (cancelled and terminated are excluded).
     */
    public function test_25_hosting_statuses_respected(): void
    {
        ProjectNotificationSettingDay::create([
            'project_notification_setting_id' => $this->setting->id,
            'days_before' => 5,
        ]);
        $this->setting->users()->sync([$this->user1->id]);

        $activeRec = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Active Status Record',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusActive->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $cancelledRec = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Cancelled Status Record',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusCancelled->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $terminatedRec = ProjectHostingRecord::create([
            'project_id' => $this->project->id,
            'name' => 'Terminated Status Record',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->statusTerminated->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $service = app(ProjectNotificationService::class);
        $service->processHostingExpiryNotifications();

        $this->assertDatabaseHas('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $activeRec->id,
            'reminder_occasion' => 'days_before_5',
        ]);

        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $cancelledRec->id,
        ]);

        $this->assertDatabaseMissing('project_notification_logs', [
            'link_type' => ProjectHostingRecord::class,
            'link_id' => $terminatedRec->id,
        ]);
    }
}
