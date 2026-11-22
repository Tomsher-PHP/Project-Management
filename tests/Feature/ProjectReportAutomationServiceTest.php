<?php

namespace Tests\Feature;

use App\Models\DailyWorkReportSetting;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\User;
use App\Services\Reports\ProjectReportAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectReportAutomationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectReportAutomationService $service;
    protected ProjectStatus $statusActive;
    protected ProjectStatus $statusClosed;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProjectReportAutomationService::class);

        Permission::findOrCreate('project.customer_end_date', 'web');
        Permission::findOrCreate('project.view_all_projects', 'web');

        $this->statusActive = ProjectStatus::firstOrCreate(
            ['name' => 'In Progress Test Status'],
            [
                'color' => '#10b981',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->statusClosed = ProjectStatus::firstOrCreate(
            ['name' => 'Completed Test Status'],
            [
                'color' => '#6b7280',
                'type' => ProjectStatus::TYPE_COMPLETED,
                'sort_order' => 2,
                'is_active' => true,
                'is_completed' => true,
            ]
        );

        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo(['project.customer_end_date', 'project.view_all_projects']);
    }

    public function test_prepare_report_data_returns_expected_structure(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_structure_test',
            'report_name' => 'Structure Test Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'none',
                ],
                'columns' => ['project_name', 'status', 'priority'],
            ],
        ]);

        $project = Project::create([
            'name' => 'Alpha Project',
            'project_code' => 'PRJ-ALPHA',
            'status_id' => $this->statusActive->id,
            'priority' => 'high',
            'project_flow' => 'agile',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $report = $this->service->prepareReportData($setting);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('metadata', $report);
        $this->assertArrayHasKey('columns', $report);
        $this->assertArrayHasKey('headers', $report);
        $this->assertArrayHasKey('column_keys', $report);
        $this->assertArrayHasKey('projects', $report);
        $this->assertArrayHasKey('flat_rows', $report);
        $this->assertArrayHasKey('stats', $report);

        $this->assertEquals($setting->id, $report['metadata']['setting_id']);
        $this->assertEquals('Structure Test Report', $report['metadata']['report_name']);
        $this->assertFalse($report['metadata']['is_empty']);
        $this->assertCount(1, $report['projects']);
        $this->assertEquals('Alpha Project', $report['projects'][0]['name']);
        $this->assertEquals($this->statusActive->name, $report['projects'][0]['primary_values']['status']);
    }

    public function test_accepts_setting_id_as_well_as_instance(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_id_test',
            'report_name' => 'ID Test Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'none',
                ],
                'columns' => ['project_name'],
            ],
        ]);

        $report = $this->service->prepareReportData($setting->id);

        $this->assertEquals($setting->id, $report['metadata']['setting_id']);
    }

    public function test_filters_projects_by_configured_status_ids(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_status_filter_test',
            'report_name' => 'Status Filter Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusClosed->id],
                    'date_filter' => 'none',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        Project::create([
            'name' => 'Active Project X',
            'project_code' => 'PRJ-ACT-X',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);

        Project::create([
            'name' => 'Closed Project Y',
            'project_code' => 'PRJ-CLO-Y',
            'status_id' => $this->statusClosed->id,
            'project_flow' => 'agile',
        ]);

        $report = $this->service->prepareReportData($setting);

        $projectNames = collect($report['projects'])->pluck('name')->all();
        $this->assertContains('Closed Project Y', $projectNames);
        $this->assertNotContains('Active Project X', $projectNames);
    }

    public function test_mandatory_project_name_column_is_preserved_even_when_omitted(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_column_preserve_test',
            'report_name' => 'Column Preserve Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'none',
                ],
                // deliberately omit project_name
                'columns' => ['status', 'priority'],
            ],
        ]);

        $report = $this->service->prepareReportData($setting);

        $this->assertArrayHasKey('project_name', $report['columns']);
        $this->assertEquals('Project Name', $report['columns']['project_name']);
        $this->assertContains('project_name', $report['column_keys']);
        $this->assertEquals('project_name', $report['column_keys'][0]);
    }

    public function test_customer_sensitive_columns_are_protected_in_background_without_auth(): void
    {
        // Ensure no authenticated user in background
        auth()->logout();

        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_customer_protect_test',
            'report_name' => 'Customer Protect Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'none',
                ],
                'columns' => [
                    'project_name',
                    'customer_end_date',
                    'customer_estimated_hours',
                    'customer_progress',
                    'status',
                ],
            ],
        ]);

        // Background execution without user: customer columns must be stripped out
        $report = $this->service->prepareReportData($setting, null, null, null);

        $this->assertArrayNotHasKey('customer_end_date', $report['columns']);
        $this->assertArrayNotHasKey('customer_estimated_hours', $report['columns']);
        $this->assertArrayNotHasKey('customer_progress', $report['columns']);
        $this->assertFalse($report['metadata']['has_customer_columns']);

        // Explicitly authorized execution with adminUser: customer columns must be included
        $authReport = $this->service->prepareReportData($setting, null, $this->adminUser, null);

        $this->assertArrayHasKey('customer_end_date', $authReport['columns']);
        $this->assertArrayHasKey('customer_estimated_hours', $authReport['columns']);
        $this->assertArrayHasKey('customer_progress', $authReport['columns']);
        $this->assertTrue($authReport['metadata']['has_customer_columns']);
    }

    public function test_safe_access_policy_never_includes_system_projects(): void
    {
        auth()->logout();

        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_system_protect_test',
            'report_name' => 'System Protect Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'none',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $normalProject = Project::create([
            'name' => 'Regular Business Project',
            'project_code' => 'PRJ-REG-001',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);
        DB::table('projects')->where('id', $normalProject->id)->update(['is_system' => false]);

        $systemProject = Project::create([
            'name' => 'Internal System Project',
            'project_code' => 'PRJ-SYS-001',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);
        DB::table('projects')->where('id', $systemProject->id)->update(['is_system' => true]);

        $report = $this->service->prepareReportData($setting);

        $names = collect($report['projects'])->pluck('name')->all();
        $this->assertContains('Regular Business Project', $names);
        $this->assertNotContains('Internal System Project', $names);
    }

    public function test_multiple_project_timelines_and_calculations(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_timelines_test',
            'report_name' => 'Timelines Test Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'none',
                ],
                'columns' => [
                    'project_name',
                    'timeline',
                    'start_date',
                    'end_date',
                    'estimated_hours',
                    'actual_hours',
                    'progress',
                ],
            ],
        ]);

        $project = Project::create([
            'name' => 'Multi Timeline Project',
            'project_code' => 'PRJ-MT-001',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);

        ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Sprint Phase 1',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'estimated_time_seconds' => 72000, // 20 hours
            'actual_time_seconds' => 36000,    // 10 hours -> 50%
            'sort_order' => 1,
        ]);

        ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Sprint Phase 2',
            'status' => ProjectTimeline::STATUS_COMPLETED,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'estimated_time_seconds' => 36000, // 10 hours
            'actual_time_seconds' => 36000,    // 10 hours -> 100%
            'sort_order' => 2,
        ]);

        $report = $this->service->prepareReportData($setting);

        $this->assertCount(1, $report['projects']);
        $projectData = $report['projects'][0];

        $this->assertTrue($projectData['has_multiple_timelines']);
        $this->assertEquals(2, $projectData['eligible_timelines_count']);
        $this->assertCount(2, $projectData['timelines']);

        // Flat rows should contain 2 rows (one for each timeline)
        $this->assertCount(2, $report['flat_rows']);
        $this->assertEquals('Sprint Phase 1', $report['flat_rows'][0]['timeline_name']);
        $this->assertEquals('50%', $report['flat_rows'][0]['values']['progress']);
        $this->assertEquals('100%', $report['flat_rows'][1]['values']['progress']);
    }

    public function test_timezone_aware_date_filtering_created_at(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_created_at_filter_test',
            'report_name' => 'Created At Filter Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai', // UTC+4
            'reporting_period' => 'previous_day',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        // Simulated reference date: 2026-11-22 10:00:00 Asia/Dubai
        $referenceDate = Carbon::parse('2026-11-22 10:00:00', 'Asia/Dubai');
        // Previous day in Dubai is 2026-11-21 00:00:00 to 2026-11-21 23:59:59 (+04:00)

        $matchingProject = Project::create([
            'name' => 'Created Yesterday In Dubai',
            'project_code' => 'PRJ-YEST-01',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);
        DB::table('projects')->where('id', $matchingProject->id)->update([
            'created_at' => Carbon::parse('2026-11-21 12:00:00', 'Asia/Dubai')->utc()->toDateTimeString(),
        ]);

        $olderProject = Project::create([
            'name' => 'Created 5 Days Ago',
            'project_code' => 'PRJ-OLD-01',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);
        DB::table('projects')->where('id', $olderProject->id)->update([
            'created_at' => Carbon::parse('2026-11-16 12:00:00', 'Asia/Dubai')->utc()->toDateTimeString(),
        ]);

        $report = $this->service->prepareReportData($setting, $referenceDate);

        $names = collect($report['projects'])->pluck('name')->all();
        $this->assertContains('Created Yesterday In Dubai', $names);
        $this->assertNotContains('Created 5 Days Ago', $names);
    }

    public function test_date_filtering_start_date(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_start_date_filter_test',
            'report_name' => 'Start Date Filter Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'last_7_days',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'start_date',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $referenceDate = Carbon::parse('2026-11-22 10:00:00', 'UTC');

        Project::create([
            'name' => 'In Range Start Date Project',
            'project_code' => 'PRJ-IN-01',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
            'start_date' => '2026-11-20',
        ]);

        Project::create([
            'name' => 'Out of Range Start Date Project',
            'project_code' => 'PRJ-OUT-01',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
            'start_date' => '2026-10-01',
        ]);

        $report = $this->service->prepareReportData($setting, $referenceDate);

        $names = collect($report['projects'])->pluck('name')->all();
        $this->assertContains('In Range Start Date Project', $names);
        $this->assertNotContains('Out of Range Start Date Project', $names);
    }

    public function test_handles_configurations_with_no_matching_projects(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_empty_test',
            'report_name' => 'Empty Result Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'previous_day',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [999999], // non-existent status ID
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $report = $this->service->prepareReportData($setting);

        $this->assertTrue($report['metadata']['is_empty']);
        $this->assertEquals(0, $report['metadata']['total_projects']);
        $this->assertEquals(0, $report['metadata']['total_rows']);
        $this->assertEmpty($report['projects']);
        $this->assertEmpty($report['flat_rows']);
        $this->assertNotEmpty($report['columns']);
        $this->assertNotEmpty($report['headers']);
    }
}
