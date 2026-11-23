<?php

namespace Tests\Feature;

use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportSettingsAndRecipientsSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_multiple_configurations_can_share_same_report_key(): void
    {
        $setting1 = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_alpha_' . uniqid(),
            'report_name' => 'Project Alpha Weekly',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'weekly',
            'delivery_time' => '09:00:00',
            'timezone' => 'Asia/Dubai',
            'settings' => [
                'schedule' => ['monthly_day' => 1],
                'filters' => ['project_status_ids' => [1, 2]],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $setting2 = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_beta_' . uniqid(),
            'report_name' => 'Project Beta Daily',
            'report_key' => 'project_report',
            'enabled' => false,
            'frequency' => 'daily',
            'delivery_time' => '18:00:00',
            'timezone' => 'Asia/Dubai',
            'settings' => [
                'schedule' => ['monthly_day' => 15],
                'filters' => ['project_status_ids' => [3]],
                'columns' => ['project_name', 'progress'],
            ],
        ]);

        $this->assertDatabaseHas('daily_work_report_settings', [
            'id' => $setting1->id,
            'report_name' => 'Project Alpha Weekly',
            'report_key' => 'project_report',
        ]);

        $this->assertDatabaseHas('daily_work_report_settings', [
            'id' => $setting2->id,
            'report_name' => 'Project Beta Daily',
            'report_key' => 'project_report',
        ]);

        $this->assertIsArray($setting1->settings);
        $this->assertSame(['project_name', 'status'], $setting1->settings['columns']);
    }

    public function test_setting_key_remains_unique(): void
    {
        $settingKey = 'unique_setting_key_' . uniqid();

        DailyWorkReportSetting::create([
            'setting_key' => $settingKey,
            'report_name' => 'Test 1',
            'report_key' => 'project_report',
            'enabled' => true,
        ]);

        $this->expectException(QueryException::class);

        DailyWorkReportSetting::create([
            'setting_key' => $settingKey,
            'report_name' => 'Test 2',
            'report_key' => 'project_report',
            'enabled' => false,
        ]);
    }

    public function test_recipients_support_internal_and_external_with_null_scope(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_recipients_' . uniqid(),
            'report_name' => 'Recipient Test Report',
            'report_key' => 'project_report',
            'enabled' => true,
        ]);

        $user = User::first() ?? User::factory()->create();

        // Internal recipient
        $internal = DailyWorkReportRecipient::create([
            'report_setting_id' => $setting->id,
            'user_id' => $user->id,
            'report_key' => 'project_report',
            'scope' => null,
            'scope_id' => null,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => true,
        ]);

        // External recipient
        $external = DailyWorkReportRecipient::create([
            'report_setting_id' => $setting->id,
            'user_id' => null,
            'report_key' => 'project_report',
            'scope' => null,
            'scope_id' => null,
            'name' => 'External Partner',
            'email' => 'partner@example.com',
            'is_active' => true,
        ]);

        $this->assertNull($internal->scope);
        $this->assertNull($external->scope);
        $this->assertNull($external->user_id);
        $this->assertSame($user->id, $internal->user_id);
        $this->assertSame($setting->id, $internal->report_setting_id);

        // Test relationships
        $this->assertTrue($setting->recipients->contains($internal));
        $this->assertTrue($setting->recipients->contains($external));
        $this->assertSame($setting->id, $internal->reportSetting->id);
        $this->assertSame($user->id, $internal->user->id);
        $this->assertNull($external->user);
    }

    public function test_cascade_delete_on_report_setting(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'cascade_test_' . uniqid(),
            'report_name' => 'Cascade Test',
            'report_key' => 'project_report',
            'enabled' => true,
        ]);

        $recipient = DailyWorkReportRecipient::create([
            'report_setting_id' => $setting->id,
            'report_key' => 'project_report',
            'name' => 'To be cascaded',
            'email' => 'cascade@example.com',
            'is_active' => true,
        ]);

        $recipientId = $recipient->id;

        $setting->delete();

        $this->assertDatabaseMissing('daily_work_report_recipients', [
            'id' => $recipientId,
        ]);
    }

    public function test_existing_daily_work_report_functionality_preserved(): void
    {
        $dwrSetting = DailyWorkReportSetting::forReport('daily_work');
        $this->assertSame('daily_work', $dwrSetting->report_key);

        $recipient = DailyWorkReportRecipient::create([
            'report_key' => 'daily_work',
            'scope' => 'global',
            'scope_id' => null,
            'name' => 'DWR Recipient',
            'email' => 'dwr@example.com',
            'is_active' => true,
        ]);

        $this->assertSame('global', $recipient->scope);
        $this->assertTrue(DailyWorkReportRecipient::global()->where('id', $recipient->id)->exists());
        $this->assertTrue(DailyWorkReportRecipient::effective()->where('id', $recipient->id)->exists());
    }

    public function test_daily_work_report_settings_renders_history_tab_instead_of_advanced(): void
    {
        Permission::findOrCreate('report_settings.manage', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('report_settings.manage');

        $response = $this->actingAs($user)->get(route('settings.daily-work-reports.index'));

        $response->assertOk();
        $response->assertSee('History');
        $response->assertSee('data-report-tab="history"', false);
        $response->assertSee('id="report-panel-history"', false);
        $response->assertDontSee('data-report-tab="advanced"', false);
        $response->assertDontSee('id="report-panel-advanced"', false);
    }
}

