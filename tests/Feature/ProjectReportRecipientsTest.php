<?php

namespace Tests\Feature;

use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectReportRecipientsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected DailyWorkReportSetting $reportSetting;
    protected DailyWorkReportSetting $anotherReportSetting;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('report_settings.manage', 'web');

        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo('report_settings.manage');

        $this->unauthorizedUser = User::factory()->create();

        $this->reportSetting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_test_config_' . uniqid(),
            'report_name' => 'Project Report Alpha',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '09:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'schedule' => ['monthly_day' => null],
                'filters' => ['project_status_ids' => [], 'date_filter' => 'created_at'],
                'columns' => ['project_name', 'progress'],
            ],
        ]);

        $this->anotherReportSetting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_test_config_' . uniqid(),
            'report_name' => 'Project Report Beta',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'weekly',
            'weekly_day' => 1,
            'delivery_time' => '10:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
        ]);
    }

    public function test_unauthorized_user_cannot_manage_recipients(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson(route('settings.project-reports.recipients.index', $this->reportSetting->id));

        $response->assertForbidden();

        $syncResponse = $this->actingAs($this->unauthorizedUser)
            ->postJson(route('settings.project-reports.recipients.sync', $this->reportSetting->id), [
                'user_ids' => [],
            ]);

        $syncResponse->assertForbidden();
    }

    public function test_can_retrieve_recipients_for_a_specific_project_report_configuration(): void
    {
        $employee = User::factory()->create(['name' => 'John Doe', 'email' => 'john.doe@example.com']);

        $internalRecipient = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'is_active' => true,
        ]);

        $externalRecipient = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => null,
            'name' => 'External Client',
            'email' => 'client@external.com',
            'is_active' => true,
        ]);

        // Belongs to another configuration
        $otherRecipient = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->anotherReportSetting->id,
            'user_id' => null,
            'name' => 'Other Client',
            'email' => 'other@external.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('settings.project-reports.recipients.index', $this->reportSetting->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('setting.id', $this->reportSetting->id)
            ->assertJsonCount(2, 'recipients')
            ->assertJsonPath('internal_user_ids.0', $employee->id);

        $recipientIds = collect($response->json('recipients'))->pluck('id')->all();
        $this->assertContains($internalRecipient->id, $recipientIds);
        $this->assertContains($externalRecipient->id, $recipientIds);
        $this->assertNotContains($otherRecipient->id, $recipientIds);
    }

    public function test_can_sync_internal_users_as_recipients(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice Smith', 'email' => 'alice@example.com', 'is_active' => true]);
        $user2 = User::factory()->create(['name' => 'Bob Jones', 'email' => 'bob@example.com', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.recipients.sync', $this->reportSetting->id), [
                'user_ids' => [$user1->id, $user2->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('daily_work_report_recipients', [
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => $user1->id,
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'scope' => null,
            'scope_id' => null,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('daily_work_report_recipients', [
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => $user2->id,
            'name' => 'Bob Jones',
            'email' => 'bob@example.com',
            'scope' => null,
            'scope_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_can_sync_external_recipients(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.recipients.sync', $this->reportSetting->id), [
                'user_ids' => [],
                'external_recipients' => [
                    ['name' => 'Client One', 'email' => 'client1@test.com'],
                    ['name' => 'Client Two', 'email' => 'client2@test.com'],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('daily_work_report_recipients', [
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => null,
            'name' => 'Client One',
            'email' => 'client1@test.com',
            'scope' => null,
            'scope_id' => null,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('daily_work_report_recipients', [
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => null,
            'name' => 'Client Two',
            'email' => 'client2@test.com',
            'is_active' => true,
        ]);
    }

    public function test_sync_preserves_existing_unchanged_recipient_ids(): void
    {
        $employee = User::factory()->create(['name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'is_active' => true]);

        $existingInternal = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'is_active' => true,
        ]);

        $existingExternal = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => null,
            'name' => 'Partner Org',
            'email' => 'partner@external.com',
            'is_active' => true,
        ]);

        $initialInternalId = $existingInternal->id;
        $initialExternalId = $existingExternal->id;

        $newEmployee = User::factory()->create(['name' => 'Dave Miller', 'email' => 'dave@example.com', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.recipients.sync', $this->reportSetting->id), [
                'user_ids' => [$employee->id, $newEmployee->id],
                'external_recipients' => [
                    ['name' => 'Partner Org Updated', 'email' => 'partner@external.com'],
                ],
            ]);

        $response->assertOk();

        // Check that initial IDs are preserved
        $this->assertDatabaseHas('daily_work_report_recipients', [
            'id' => $initialInternalId,
            'user_id' => $employee->id,
        ]);

        $this->assertDatabaseHas('daily_work_report_recipients', [
            'id' => $initialExternalId,
            'email' => 'partner@external.com',
            'name' => 'Partner Org Updated',
        ]);
    }

    public function test_sync_removes_omitted_recipients_for_that_configuration_only(): void
    {
        $employee1 = User::factory()->create(['is_active' => true]);
        $employee2 = User::factory()->create(['is_active' => true]);

        $rec1 = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => $employee1->id,
            'email' => $employee1->email,
            'name' => $employee1->name,
            'is_active' => true,
        ]);

        $rec2 = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => $employee2->id,
            'email' => $employee2->email,
            'name' => $employee2->name,
            'is_active' => true,
        ]);

        // Another report configuration's recipient
        $anotherRec = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->anotherReportSetting->id,
            'user_id' => $employee1->id,
            'email' => $employee1->email,
            'name' => $employee1->name,
            'is_active' => true,
        ]);

        // Sync reportSetting with only employee1
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.recipients.sync', $this->reportSetting->id), [
                'user_ids' => [$employee1->id],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('daily_work_report_recipients', ['id' => $rec1->id]);
        $this->assertDatabaseMissing('daily_work_report_recipients', ['id' => $rec2->id]);
        // another configuration's recipient must remain untouched
        $this->assertDatabaseHas('daily_work_report_recipients', ['id' => $anotherRec->id]);
    }

    public function test_prevents_duplicate_external_emails(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.recipients.sync', $this->reportSetting->id), [
                'external_recipients' => [
                    ['name' => 'Dup 1', 'email' => 'same@test.com'],
                    ['name' => 'Dup 2', 'email' => 'SAME@TEST.COM'],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['external_recipients.1.email']);
    }

    public function test_prevents_external_email_that_matches_selected_internal_user(): void
    {
        $employee = User::factory()->create(['email' => 'internal.emp@company.com', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.recipients.sync', $this->reportSetting->id), [
                'user_ids' => [$employee->id],
                'external_recipients' => [
                    ['name' => 'External Clone', 'email' => 'INTERNAL.EMP@company.com'],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['external_recipients.0.email']);
    }

    public function test_recipients_from_one_report_cannot_be_modified_through_another_report(): void
    {
        $recipientInBeta = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->anotherReportSetting->id,
            'user_id' => null,
            'name' => 'Beta Recipient',
            'email' => 'beta@company.com',
            'is_active' => true,
        ]);

        // Attempting to delete Beta's recipient via Alpha's route
        $deleteResponse = $this->actingAs($this->adminUser)
            ->deleteJson(route('settings.project-reports.recipients.destroy.scoped', [
                $this->reportSetting->id,
                $recipientInBeta->id,
            ]));

        $deleteResponse->assertNotFound();
        $this->assertDatabaseHas('daily_work_report_recipients', ['id' => $recipientInBeta->id]);

        // Attempting to toggle Beta's recipient via Alpha's route
        $toggleResponse = $this->actingAs($this->adminUser)
            ->patchJson(route('settings.project-reports.recipients.toggle.scoped', [
                $this->reportSetting->id,
                $recipientInBeta->id,
            ]));

        $toggleResponse->assertNotFound();
    }

    public function test_daily_work_report_recipients_remain_unaffected(): void
    {
        $dwrSetting = DailyWorkReportSetting::forReport('daily_work');

        $dwrRecipient = DailyWorkReportRecipient::create([
            'report_key' => 'daily_work',
            'report_setting_id' => $dwrSetting->id,
            'scope' => 'global',
            'email' => 'dwr.global@company.com',
            'is_active' => true,
        ]);

        // Attempting to delete DWR recipient via project report scoped route
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('settings.project-reports.recipients.destroy.scoped', [
                $this->reportSetting->id,
                $dwrRecipient->id,
            ]));

        $response->assertNotFound();
        $this->assertDatabaseHas('daily_work_report_recipients', ['id' => $dwrRecipient->id]);
    }

    public function test_can_toggle_and_delete_recipient_successfully(): void
    {
        $recipient = DailyWorkReportRecipient::create([
            'report_key' => 'project_report',
            'report_setting_id' => $this->reportSetting->id,
            'user_id' => null,
            'name' => 'Active Recipient',
            'email' => 'toggle.test@company.com',
            'is_active' => true,
        ]);

        // Toggle to inactive
        $toggleResponse = $this->actingAs($this->adminUser)
            ->patchJson(route('settings.project-reports.recipients.toggle.scoped', [
                $this->reportSetting->id,
                $recipient->id,
            ]));

        $toggleResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('daily_work_report_recipients', [
            'id' => $recipient->id,
            'is_active' => false,
        ]);

        // Delete recipient
        $deleteResponse = $this->actingAs($this->adminUser)
            ->deleteJson(route('settings.project-reports.recipients.destroy.scoped', [
                $this->reportSetting->id,
                $recipient->id,
            ]));

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('daily_work_report_recipients', [
            'id' => $recipient->id,
        ]);
    }
}
