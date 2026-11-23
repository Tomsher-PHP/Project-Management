<?php

namespace Tests\Feature;

use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportSetting;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\Reports\ProjectReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectReportSettingsCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected int $statusId;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permission exists in test DB
        Permission::findOrCreate('report_settings.manage', 'web');
        Permission::findOrCreate('project.customer_end_date', 'web');

        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo('report_settings.manage');

        $this->unauthorizedUser = User::factory()->create();

        $status = ProjectStatus::first();
        if (!$status) {
            $status = ProjectStatus::create([
                'name' => 'Active Status',
                'color' => '#10b981',
                'type' => ProjectStatus::TYPE_OPEN,
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }
        $this->statusId = $status->id;
    }

    public function test_unauthorized_user_cannot_access_project_report_settings(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson(route('settings.project-reports.index'));

        $response->assertForbidden();

        $storeResponse = $this->actingAs($this->unauthorizedUser)
            ->postJson(route('settings.project-reports.store'), [
                'report_name' => 'Unauthorized Report',
            ]);

        $storeResponse->assertForbidden();
    }

    public function test_authorized_user_can_list_configurations_scoped_to_project_report(): void
    {
        $dwrSetting = DailyWorkReportSetting::forReport('daily_work');

        $projectSetting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_list_test_' . uniqid(),
            'report_name' => 'List Test Config',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('settings.project-reports.index'));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);

        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->all();

        $this->assertContains($projectSetting->id, $ids);
        $this->assertNotContains($dwrSetting->id, $ids);
    }

    public function test_authorized_user_can_create_project_report_configuration(): void
    {
        $payload = [
            'report_name' => 'Executive Weekly Summary',
            'enabled' => true,
            'frequency' => 'weekly',
            'weekly_day' => 2,
            'delivery_time' => '09:30',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'last_30_days',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                    'date_filter' => 'start_date',
                ],
                'columns' => ['project_name', 'status', 'progress', 'estimated_hours'],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.report_name', 'Executive Weekly Summary');
        $response->assertJsonPath('data.report_key', 'project_report');

        $createdId = $response->json('data.id');
        $setting = DailyWorkReportSetting::findOrFail($createdId);

        $this->assertSame('project_report', $setting->report_key);
        $this->assertStringStartsWith('project_report_executive_weekly_summary', $setting->setting_key);
        $this->assertSame('weekly', $setting->frequency);
        $this->assertSame(2, $setting->weekly_day);
        $this->assertSame('09:30:00', $setting->delivery_time);
        $this->assertSame('last_30_days', $setting->reporting_period);
        $this->assertSame([$this->statusId], $setting->settings['filters']['project_status_ids']);
        $this->assertSame('start_date', $setting->settings['filters']['date_filter']);
        $this->assertSame(['project_name', 'status', 'progress', 'estimated_hours'], $setting->settings['columns']);
    }

    public function test_each_configuration_receives_unique_setting_key(): void
    {
        $payload = [
            'report_name' => 'Same Report Name',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '10:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ];

        $res1 = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payload);
        $res1->assertCreated();

        $res2 = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payload);
        $res2->assertCreated();

        $key1 = $res1->json('data.setting_key');
        $key2 = $res2->json('data.setting_key');

        $this->assertNotEquals($key1, $key2);
        $this->assertDatabaseHas('daily_work_report_settings', ['setting_key' => $key1]);
        $this->assertDatabaseHas('daily_work_report_settings', ['setting_key' => $key2]);
    }

    public function test_authorized_user_can_retrieve_single_configuration(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'retrieve_test_' . uniqid(),
            'report_name' => 'Retrieve Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '12:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'previous_day',
            'settings' => [
                'filters' => ['project_status_ids' => [$this->statusId], 'date_filter' => 'created_at'],
                'columns' => ['project_name'],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('settings.project-reports.show', $setting->id));

        $response->assertOk();
        $response->assertJsonPath('data.id', $setting->id);
        $response->assertJsonPath('data.report_name', 'Retrieve Test');
    }

    public function test_cannot_retrieve_daily_work_report_configuration_through_project_report_endpoints(): void
    {
        $dwrSetting = DailyWorkReportSetting::forReport('daily_work');

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('settings.project-reports.show', $dwrSetting->id));

        $response->assertNotFound();
    }

    public function test_authorized_user_can_update_configuration_without_changing_setting_key(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'immutable_key_' . uniqid(),
            'report_name' => 'Initial Name',
            'report_key' => 'project_report',
            'enabled' => false,
            'frequency' => 'daily',
            'delivery_time' => '07:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'last_7_days',
            'settings' => [
                'filters' => ['project_status_ids' => [$this->statusId], 'date_filter' => 'created_at'],
                'columns' => ['project_name'],
            ],
        ]);

        $originalKey = $setting->setting_key;

        $updatePayload = [
            'report_name' => 'Updated Name',
            'enabled' => true,
            'frequency' => 'monthly',
            'delivery_time' => '14:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'last_90_days',
            'settings' => [
                'schedule' => ['monthly_day' => 'last'],
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                    'date_filter' => 'end_date',
                ],
                'columns' => ['project_name', 'priority', 'status'],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('settings.project-reports.update', $setting->id), $updatePayload);

        $response->assertOk();
        $response->assertJsonPath('data.report_name', 'Updated Name');

        $setting->refresh();
        $this->assertSame($originalKey, $setting->setting_key);
        $this->assertSame('Updated Name', $setting->report_name);
        $this->assertTrue($setting->enabled);
        $this->assertSame('monthly', $setting->frequency);
        $this->assertSame('last', $setting->settings['schedule']['monthly_day']);
        $this->assertSame('end_date', $setting->settings['filters']['date_filter']);
        $this->assertSame(['project_name', 'priority', 'status'], $setting->settings['columns']);
    }

    public function test_cannot_update_daily_work_report_configuration_through_project_report_endpoints(): void
    {
        $dwrSetting = DailyWorkReportSetting::forReport('daily_work');

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('settings.project-reports.update', $dwrSetting->id), [
                'report_name' => 'Should Not Work',
                'frequency' => 'daily',
                'delivery_time' => '10:00',
                'timezone' => 'Asia/Dubai',
                'reporting_period' => 'all_time',
                'settings' => [
                    'filters' => ['project_status_ids' => [$this->statusId], 'date_filter' => 'created_at'],
                    'columns' => ['project_name'],
                ],
            ]);

        $response->assertNotFound();
    }

    public function test_authorized_user_can_delete_configuration(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'delete_me_' . uniqid(),
            'report_name' => 'To Delete',
            'report_key' => 'project_report',
            'enabled' => false,
            'frequency' => 'daily',
            'delivery_time' => '10:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('settings.project-reports.destroy', $setting->id));

        $response->assertOk();
        $this->assertDatabaseMissing('daily_work_report_settings', ['id' => $setting->id]);
    }

    public function test_cannot_delete_daily_work_report_configuration(): void
    {
        $dwrSetting = DailyWorkReportSetting::forReport('daily_work');

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('settings.project-reports.destroy', $dwrSetting->id));

        $response->assertNotFound();
        $this->assertDatabaseHas('daily_work_report_settings', ['id' => $dwrSetting->id]);
    }

    public function test_authorized_user_can_toggle_enabled_status(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'toggle_test_' . uniqid(),
            'report_name' => 'Toggle Test',
            'report_key' => 'project_report',
            'enabled' => false,
            'frequency' => 'daily',
            'delivery_time' => '10:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
        ]);

        // Toggle to true
        $res1 = $this->actingAs($this->adminUser)
            ->patchJson(route('settings.project-reports.toggle', $setting->id));

        $res1->assertOk();
        $res1->assertJsonPath('enabled', true);
        $this->assertTrue($setting->fresh()->enabled);

        // Toggle back to false with explicit param
        $res2 = $this->actingAs($this->adminUser)
            ->patchJson(route('settings.project-reports.toggle', $setting->id), ['enabled' => false]);

        $res2->assertOk();
        $res2->assertJsonPath('enabled', false);
        $this->assertFalse($setting->fresh()->enabled);
    }

    public function test_project_statuses_validation(): void
    {
        $basePayload = [
            'report_name' => 'Validation Test',
            'frequency' => 'daily',
            'delivery_time' => '10:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name'],
            ],
        ];

        // Empty project statuses fails
        $payloadNoStatus = $basePayload;
        $payloadNoStatus['settings']['filters']['project_status_ids'] = [];
        $resEmpty = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadNoStatus);
        $resEmpty->assertJsonValidationErrors(['settings.filters.project_status_ids']);

        // Non-existent project status ID fails
        $payloadInvalidStatus = $basePayload;
        $payloadInvalidStatus['settings']['filters']['project_status_ids'] = [999999];
        $resInvalid = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadInvalidStatus);
        $resInvalid->assertJsonValidationErrors(['settings.filters.project_status_ids.0']);
    }

    public function test_reporting_period_and_date_filter_validation(): void
    {
        $basePayload = [
            'report_name' => 'Period Filter Test',
            'frequency' => 'daily',
            'delivery_time' => '10:00',
            'timezone' => 'Asia/Dubai',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                ],
                'columns' => ['project_name'],
            ],
        ];

        // Invalid reporting period
        $payloadInvalidPeriod = $basePayload;
        $payloadInvalidPeriod['reporting_period'] = 'invalid_period';
        $payloadInvalidPeriod['settings']['filters']['date_filter'] = 'created_at';

        $resPeriod = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadInvalidPeriod);
        $resPeriod->assertJsonValidationErrors(['reporting_period']);

        // Invalid date filter
        $payloadInvalidDateFilter = $basePayload;
        $payloadInvalidDateFilter['reporting_period'] = 'last_30_days';
        $payloadInvalidDateFilter['settings']['filters']['date_filter'] = 'invalid_date_filter';

        $resDate = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadInvalidDateFilter);
        $resDate->assertJsonValidationErrors(['settings.filters.date_filter']);

        // Rejected date filters: 'none' and 'updated_at'
        $payloadNone = $basePayload;
        $payloadNone['reporting_period'] = 'last_30_days';
        $payloadNone['settings']['filters']['date_filter'] = 'none';
        $resNone = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadNone);
        $resNone->assertJsonValidationErrors(['settings.filters.date_filter']);

        $payloadUpdatedAt = $basePayload;
        $payloadUpdatedAt['reporting_period'] = 'last_30_days';
        $payloadUpdatedAt['settings']['filters']['date_filter'] = 'updated_at';
        $resUpdatedAt = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadUpdatedAt);
        $resUpdatedAt->assertJsonValidationErrors(['settings.filters.date_filter']);

        // Valid created_at date filter
        $payloadValidCreatedAt = $basePayload;
        $payloadValidCreatedAt['reporting_period'] = 'last_30_days';
        $payloadValidCreatedAt['settings']['filters']['date_filter'] = 'created_at';

        $resCreatedAt = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadValidCreatedAt);
        $resCreatedAt->assertCreated();

        // Valid status_changed_date date filter
        $payloadValidStatusDate = $basePayload;
        $payloadValidStatusDate['reporting_period'] = 'last_30_days';
        $payloadValidStatusDate['settings']['filters']['date_filter'] = 'status_changed_date';

        $resStatusDate = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadValidStatusDate);
        $resStatusDate->assertCreated();
    }

    public function test_columns_validation_and_order_preservation(): void
    {
        $basePayload = [
            'report_name' => 'Column Test',
            'frequency' => 'daily',
            'delivery_time' => '10:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                    'date_filter' => 'created_at',
                ],
            ],
        ];

        // Empty columns fails
        $payloadNoCols = $basePayload;
        $payloadNoCols['settings']['columns'] = [];
        $resEmpty = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadNoCols);
        $resEmpty->assertJsonValidationErrors(['settings.columns']);

        // Invalid column key fails
        $payloadInvalidCol = $basePayload;
        $payloadInvalidCol['settings']['columns'] = ['project_name', 'non_existent_column_xyz'];
        $resInvalid = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadInvalidCol);
        $resInvalid->assertJsonValidationErrors(['settings.columns.1']);

        // Column order preserved exactly
        $orderedCols = ['status', 'estimated_hours', 'customer', 'project_name'];
        $payloadOrder = $basePayload;
        $payloadOrder['settings']['columns'] = $orderedCols;

        $resOrder = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadOrder);
        $resOrder->assertCreated();
        $this->assertSame($orderedCols, $resOrder->json('data.settings.columns'));
    }

    public function test_customer_columns_permission_restriction(): void
    {
        $payloadWithCustomerCol = [
            'report_name' => 'Customer Column Test',
            'frequency' => 'daily',
            'delivery_time' => '10:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'customer_end_date'],
            ],
        ];

        // Admin user does NOT have project.customer_end_date -> validation fails
        $this->assertFalse($this->adminUser->can('project.customer_end_date'));
        $resForbiddenCol = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadWithCustomerCol);
        $resForbiddenCol->assertJsonValidationErrors(['settings.columns.1']);

        // Give permission -> validation succeeds
        $this->adminUser->givePermissionTo('project.customer_end_date');
        $this->assertTrue($this->adminUser->can('project.customer_end_date'));

        $resAllowed = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadWithCustomerCol);
        $resAllowed->assertCreated();
        $this->assertContains('customer_end_date', $resAllowed->json('data.settings.columns'));
    }

    public function test_schedule_settings_validation_for_weekly_and_monthly(): void
    {
        $basePayload = [
            'report_name' => 'Schedule Test',
            'delivery_time' => '10:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name'],
            ],
        ];

        // Weekly without weekly_day fails
        $payloadWeeklyNoDay = $basePayload;
        $payloadWeeklyNoDay['frequency'] = 'weekly';
        $resWeekly = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadWeeklyNoDay);
        $resWeekly->assertJsonValidationErrors(['weekly_day']);

        // Monthly without monthly_day fails
        $payloadMonthlyNoDay = $basePayload;
        $payloadMonthlyNoDay['frequency'] = 'monthly';
        $resMonthly = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadMonthlyNoDay);
        $resMonthly->assertJsonValidationErrors(['settings.schedule.monthly_day']);

        // Monthly with invalid monthly_day (e.g. 29) fails
        $payloadMonthlyInvalidDay = $basePayload;
        $payloadMonthlyInvalidDay['frequency'] = 'monthly';
        $payloadMonthlyInvalidDay['settings']['schedule']['monthly_day'] = 30;
        $resMonthlyInvalid = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadMonthlyInvalidDay);
        $resMonthlyInvalid->assertJsonValidationErrors(['settings.schedule.monthly_day']);

        // Monthly with valid day 1..28 succeeds
        $payloadMonthlyValid = $basePayload;
        $payloadMonthlyValid['frequency'] = 'monthly';
        $payloadMonthlyValid['settings']['schedule']['monthly_day'] = 15;
        $resMonthlyValid = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadMonthlyValid);
        $resMonthlyValid->assertCreated();
        $this->assertSame(15, $resMonthlyValid->json('data.settings.schedule.monthly_day'));

        // Monthly with 'last' succeeds
        $payloadMonthlyLast = $basePayload;
        $payloadMonthlyLast['frequency'] = 'monthly';
        $payloadMonthlyLast['settings']['schedule']['monthly_day'] = 'last';
        $resMonthlyLast = $this->actingAs($this->adminUser)
            ->postJson(route('settings.project-reports.store'), $payloadMonthlyLast);
        $resMonthlyLast->assertCreated();
        $this->assertSame('last', $resMonthlyLast->json('data.settings.schedule.monthly_day'));
    }

    public function test_html_index_page_renders_with_configurations_table_and_modal(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'html_render_test_' . uniqid(),
            'report_name' => 'HTML UI Render Config',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'weekly',
            'weekly_day' => 1,
            'delivery_time' => '10:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'last_7_days',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusId],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('settings.project-reports.index'));

        $response->assertOk();
        $response->assertViewIs('settings.report-settings.project-reports.index');
        $response->assertViewHas('configurations');
        $response->assertViewHas('availableColumns');
        $response->assertViewHas('projectStatuses');

        $response->assertSee('Project Report Configurations');
        $response->assertSee('HTML UI Render Config');
        $response->assertSee('Add Report');
        $response->assertSee('Reporting Period');
        $response->assertSee('Statuses');
        $response->assertSee('Last 7 Days');
        $response->assertSee('Status changed date');
        $response->assertDontSee('>Timezone</span>', false);
        $response->assertSee('data-locked="true"', false);
        $response->assertSee('project-report-config-modal');
        $response->assertSee('History');
        $response->assertSee('data-report-tab="history"', false);
        $response->assertSee('id="report-panel-history"', false);
        $response->assertViewHas('reports');
        $response->assertViewHas('deliveryCounts');
    }

    public function test_history_tab_displays_last_50_project_report_logs_and_delivery_counts(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'history_test_config_' . uniqid(),
            'report_name' => 'History Audit Project Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
        ]);

        $baseDate = Carbon::now()->subDays(60);

        // 1. Create 55 project report logs
        for ($i = 1; $i <= 55; $i++) {
            $log = DailyWorkReportLog::create([
                'report_setting_id' => $setting->id,
                'user_id' => null,
                'report_date' => $baseDate->copy()->addDays($i)->toDateString(),
                'period_start' => Carbon::now()->startOfDay(),
                'period_end' => Carbon::now()->endOfDay(),
                'report_type' => 'daily',
                'report_key' => 'project_report',
                'status' => $i % 2 === 0 ? 'sent' : 'queued',
                'report_data' => [
                    'metadata' => [
                        'report_name' => 'History Audit Project Report',
                        'total_projects' => 5,
                    ],
                ],
                'generated_at' => Carbon::now()->subMinutes(60 - $i),
                'completed_at' => Carbon::now()->subMinutes(60 - $i),
            ]);

            if ($i <= 10) {
                DailyWorkReportDelivery::create([
                    'daily_work_report_log_id' => $log->id,
                    'recipient_email' => "user{$i}@example.com",
                    'status' => 'sent',
                ]);
            }
        }

        // 2. Create unrelated daily_work report logs
        $dwrSetting = DailyWorkReportSetting::forReport('daily_work');
        $dwrLog = DailyWorkReportLog::create([
            'report_setting_id' => $dwrSetting->id,
            'user_id' => $this->adminUser->id,
            'report_date' => Carbon::now()->toDateString(),
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'report_type' => 'daily',
            'report_key' => 'daily_work',
            'status' => 'sent',
            'report_data' => ['dwr' => true],
        ]);

        DailyWorkReportDelivery::create([
            'daily_work_report_log_id' => $dwrLog->id,
            'recipient_email' => 'dwr.recipient@example.com',
            'status' => 'sent',
        ]);

        // Request index
        $response = $this->actingAs($this->adminUser)
            ->get(route('settings.project-reports.index'));

        $response->assertOk();
        $response->assertViewHas('reports');
        $response->assertViewHas('deliveryCounts');

        $reports = $response->viewData('reports');

        // Only list last 50 logs
        $this->assertCount(50, $reports);

        // All logs must be project_report, never daily_work
        foreach ($reports as $r) {
            $this->assertEquals('project_report', $r->report_key);
            $this->assertNotEquals('daily_work', $r->report_key);
        }

        // Delivery counts for project_report only
        $deliveryCounts = $response->viewData('deliveryCounts');
        $this->assertArrayHasKey('sent', $deliveryCounts);
        $this->assertEquals(10, $deliveryCounts['sent']);

        // Check HTML renders table headers and history tab contents
        $response->assertSee('History');
        $response->assertSee('Recent Project Report History');
        $response->assertSee('Configuration');
        $response->assertSee('Report date');
        $response->assertSee('Projects');
        $response->assertSee('History Audit Project Report');
        $response->assertSee('deliveries');
    }
}

