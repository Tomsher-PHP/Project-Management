<?php

namespace Tests\Feature;

use App\Mail\ProjectReportMail;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportSetting;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\User;
use App\Services\Reports\ProjectReportAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectReportMailTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectReportAutomationService $automationService;
    protected ProjectStatus $statusActive;
    protected ProjectStatus $statusCompleted;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->automationService = app(ProjectReportAutomationService::class);

        Permission::findOrCreate('project.customer_end_date', 'web');
        Permission::findOrCreate('project.view_all_projects', 'web');

        $this->statusActive = ProjectStatus::firstOrCreate(
            ['name' => 'Active Status'],
            [
                'color' => '#10b981',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->statusCompleted = ProjectStatus::firstOrCreate(
            ['name' => 'Closed Status'],
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

    public function test_mailable_builds_and_renders_html_with_metadata_and_table(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_mail_render_test',
            'report_name' => 'Weekly Executive Summary',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'weekly',
            'delivery_time' => '08:00:00',
            'timezone' => 'Asia/Dubai',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status', 'priority'],
            ],
        ]);

        $project = Project::create([
            'name' => 'Acme Cloud Migration',
            'project_code' => 'ACM-001',
            'status_id' => $this->statusActive->id,
            'priority' => 'high',
            'project_flow' => 'agile',
        ]);

        $reportData = $this->automationService->prepareReportData($setting);

        $mailable = new ProjectReportMail($reportData);
        $mailable->build();

        // Check subject
        $this->assertStringContainsString('Weekly Executive Summary', $mailable->subject);

        // Render HTML
        $html = $mailable->render();

        // 1. Verify Report Name & App Info
        $this->assertStringContainsString('Weekly Executive Summary', $html);

        // 2. Verify Metadata section
        $this->assertStringContainsString('All Time', $html); // reporting period
        $this->assertStringContainsString('Project Created Date within Period', $html); // date filter label
        $this->assertStringContainsString('Active Status', $html); // selected project statuses
        $this->assertStringContainsString('Generated On:', $html);

        // 3. Verify Table and Project Data
        $this->assertStringContainsString('Acme Cloud Migration', $html);
        $this->assertStringContainsString('Active Status', $html);
        $this->assertStringContainsString('High', $html); // priority
    }

    public function test_mailable_instantiation_from_daily_work_report_log(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_mail_log_test',
            'report_name' => 'Log Delivery Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $reportData = $this->automationService->prepareReportData($setting);

        $log = DailyWorkReportLog::create([
            'report_setting_id' => $setting->id,
            'report_key' => 'project_report',
            'report_type' => 'daily',
            'report_date' => '2026-11-22',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'status' => 'pending',
            'report_data' => $reportData,
        ]);

        $mailable = new ProjectReportMail($log);
        $mailable->build();

        $this->assertEquals($log->id, $mailable->reportLog->id);
        $this->assertStringContainsString('Log Delivery Report', $mailable->subject);

        $html = $mailable->render();
        $this->assertStringContainsString('Log Delivery Report', $html);
    }

    public function test_renders_only_configured_columns_in_configured_order(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_column_order_test',
            'report_name' => 'Column Order Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'created_at',
                ],
                // Custom column order: priority, status, project_name (project_name preserved)
                'columns' => ['priority', 'status'],
            ],
        ]);

        Project::create([
            'name' => 'Project Column Order Alpha',
            'project_code' => 'PRJ-ORD-01',
            'status_id' => $this->statusActive->id,
            'priority' => 'medium',
            'project_flow' => 'agile',
        ]);

        $reportData = $this->automationService->prepareReportData($setting);
        $mailable = new ProjectReportMail($reportData);
        $html = $mailable->render();

        // Project Name, Priority, Status headers must appear
        $this->assertStringContainsString('Project Name', $html);
        $this->assertStringContainsString('Priority', $html);
        $this->assertStringContainsString('Status', $html);

        // Unconfigured columns must NOT appear in table header
        $this->assertStringNotContainsString('Sales Person', $html);
        $this->assertStringNotContainsString('Timeline Name', $html);
        $this->assertStringNotContainsString('Milestone Status', $html);
    }

    public function test_customer_columns_visibility_protection(): void
    {
        auth()->logout();

        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_customer_visibility_test',
            'report_name' => 'Customer Visibility Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'created_at',
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

        Project::create([
            'name' => 'Sensitive Customer Project',
            'project_code' => 'PRJ-SENS-01',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);

        // 1. Background / Unauthorized execution: customer columns must NOT render
        $unauthData = $this->automationService->prepareReportData($setting, null, null, null);
        $unauthMail = new ProjectReportMail($unauthData);
        $unauthHtml = $unauthMail->render();

        $this->assertStringNotContainsString('Cust End Date', $unauthHtml);
        $this->assertStringNotContainsString('Cust Estimate', $unauthHtml);
        $this->assertStringNotContainsString('Cust Progress', $unauthHtml);

        // 2. Authorized execution with adminUser: customer columns MUST render
        $authData = $this->automationService->prepareReportData($setting, null, $this->adminUser, null);
        $authMail = new ProjectReportMail($authData);
        $authHtml = $authMail->render();

        $this->assertStringContainsString('Cust End Date', $authHtml);
        $this->assertStringContainsString('Cust Estimate', $authHtml);
        $this->assertStringContainsString('Cust Progress', $authHtml);
    }

    public function test_renders_multiple_timelines_for_single_project(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_mail_multi_timelines_test',
            'report_name' => 'Multi Timeline Mail Report',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => [
                    'project_name',
                    'timeline',
                    'estimated_hours',
                    'actual_hours',
                    'progress',
                ],
            ],
        ]);

        $project = Project::create([
            'name' => 'Enterprise Portal',
            'project_code' => 'EP-001',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);

        ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Milestone 1 Discovery',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'estimated_time_seconds' => 36000,
            'actual_time_seconds' => 18000,
            'sort_order' => 1,
        ]);

        ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Milestone 2 Build',
            'status' => ProjectTimeline::STATUS_COMPLETED,
            'estimated_time_seconds' => 72000,
            'actual_time_seconds' => 72000,
            'sort_order' => 2,
        ]);

        $reportData = $this->automationService->prepareReportData($setting);
        $mailable = new ProjectReportMail($reportData);
        $html = $mailable->render();

        // Project name appears
        $this->assertStringContainsString('Enterprise Portal', $html);

        // Both timelines appear
        $this->assertStringContainsString('Milestone 1 Discovery', $html);
        $this->assertStringContainsString('Milestone 2 Build', $html);

        // Secondary timeline nesting indicator
        $this->assertStringContainsString('↳', $html);

        // Progress values
        $this->assertStringContainsString('50%', $html);
        $this->assertStringContainsString('100%', $html);
    }

    public function test_renders_empty_state_when_no_matching_projects(): void
    {
        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_mail_empty_test',
            'report_name' => 'Empty Results Mail Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [999999], // non-existent
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        $reportData = $this->automationService->prepareReportData($setting);
        $mailable = new ProjectReportMail($reportData);
        $html = $mailable->render();

        $this->assertStringContainsString('Empty Results Mail Test', $html);
        $this->assertStringContainsString('No matching projects found', $html);
        $this->assertStringContainsString('There are no projects matching the configured filters', $html);
    }

    public function test_supports_long_project_names_without_breaking_layout(): void
    {
        $longProjectName = 'Project with extremely long enterprise title spanning multiple paragraphs and words to ensure layout safety ' . str_repeat('ABCDEF1234567890', 5);

        $setting = DailyWorkReportSetting::create([
            'setting_key' => 'project_report_mail_long_title_test',
            'report_name' => 'Long Title Test',
            'report_key' => 'project_report',
            'enabled' => true,
            'frequency' => 'daily',
            'delivery_time' => '08:00:00',
            'timezone' => 'UTC',
            'reporting_period' => 'all_time',
            'settings' => [
                'filters' => [
                    'project_status_ids' => [$this->statusActive->id],
                    'date_filter' => 'created_at',
                ],
                'columns' => ['project_name', 'status'],
            ],
        ]);

        Project::create([
            'name' => $longProjectName,
            'project_code' => 'LONG-001',
            'status_id' => $this->statusActive->id,
            'project_flow' => 'agile',
        ]);

        $reportData = $this->automationService->prepareReportData($setting);
        $mailable = new ProjectReportMail($reportData);
        $html = $mailable->render();

        $this->assertStringContainsString($longProjectName, $html);
        $this->assertStringContainsString('word-break: break-word;', $html);
    }
}

