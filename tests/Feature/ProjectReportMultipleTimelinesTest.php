<?php

namespace Tests\Feature;

use App\Exports\ProjectReportExport;
use App\Models\Project;
use App\Models\ProjectTimeline;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\Reports\ProjectReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectReportMultipleTimelinesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProjectReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_super_admin' => false]);
        $this->actingAs($this->user);
        $this->service = app(ProjectReportService::class);
    }

    public function test_report_uses_only_eligible_timelines_with_active_timeline_first(): void
    {
        $project = $this->createProject('Multiple timelines');
        $completedOlder = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed older', '2026-08-01', '2026-08-31', 7200, 3600);
        $active = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active timeline', '2026-10-01', '2026-10-31', 3600, 5400);
        $completedNewer = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed newer', '2026-09-01', '2026-09-30', 10800, 9000);
        $this->createTimeline($project, ProjectTimeline::STATUS_PLANNED, 'Planned', '2026-11-01', '2026-11-30');
        $this->createTimeline($project, ProjectTimeline::STATUS_CANCELLED, 'Cancelled', '2026-07-01', '2026-07-31');

        $result = $this->service->getProjects(Request::create('/reports/projects-report'), 20)->first();

        $this->assertSame($project->id, $result->id);
        $this->assertSame($active->id, $result->primaryReportTimeline->id);
        $this->assertSame(3, $result->eligible_timelines_count);
        $this->assertSame(
            [$active->id, $completedNewer->id, $completedOlder->id],
            $result->reportTimelines->pluck('id')->all()
        );
        $this->assertSame(5400, $result->primaryReportTimeline->report_actual_seconds);
        $this->assertSame(150.0, $result->primaryReportTimeline->report_progress_percentage);
    }

    public function test_latest_completed_timeline_is_primary_when_no_active_timeline_exists(): void
    {
        $project = $this->createProject('Completed timelines');
        $older = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Older', '2026-08-01', '2026-08-31');
        $newer = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Newer', '2026-09-01', '2026-09-30');

        $result = $this->service->getProjects(Request::create('/reports/projects-report'), 20)->first();

        $this->assertSame($newer->id, $result->primaryReportTimeline->id);
        $this->assertSame([$newer->id, $older->id], $result->reportTimelines->pluck('id')->all());
    }

    public function test_date_filter_and_sort_use_the_displayed_primary_timeline_dates(): void
    {
        $matching = $this->createProject('Matching timeline', [
            'start_date' => '2020-01-01',
            'end_date' => '2020-01-31',
        ]);
        $this->createTimeline($matching, ProjectTimeline::STATUS_ACTIVE, 'Matching active', '2026-11-01', '2026-11-30');

        $outside = $this->createProject('Outside timeline', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);
        $this->createTimeline($outside, ProjectTimeline::STATUS_ACTIVE, 'Outside active', '2027-01-01', '2027-01-31');

        $filtered = $this->service->getProjects(Request::create('/reports/projects-report', 'GET', [
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
        ]), 20);

        $this->assertSame([$matching->id], $filtered->pluck('id')->all());
        $this->assertSame(1, $this->service->getStats(Request::create('/reports/projects-report', 'GET', [
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
        ]))['total']);

        $sorted = $this->service->getProjects(Request::create('/reports/projects-report', 'GET', [
            'sort_by' => 'start_date',
            'sort_dir' => 'asc',
        ]), 20);

        $this->assertSame([$matching->id, $outside->id], $sorted->pluck('id')->all());
    }

    public function test_excel_exports_one_complete_row_per_eligible_timeline_and_one_fallback_row(): void
    {
        $project = $this->createProject('Exported project', ['priority' => 'high']);
        $active = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active export', '2026-10-01', '2026-10-31', 3600, 7200);
        $completed = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed export', '2026-09-01', '2026-09-30', 7200, 3600);
        $this->createTimeline($project, ProjectTimeline::STATUS_PLANNED, 'Planned export', '2026-11-01', '2026-11-30');
        $fallbackProject = $this->createProject('Fallback project');
        $this->createTimeline($fallbackProject, ProjectTimeline::STATUS_CANCELLED, 'Cancelled only', '2026-08-01', '2026-08-31');

        $request = Request::create('/reports/project/export', 'GET', [
            'visible_columns' => 'project_name,timeline,start_date,estimated_hours,actual_hours,progress,priority',
        ]);
        $projects = $this->service->exportProjects($request);
        $columns = $this->service->resolveExportColumns($request);

        DB::enableQueryLog();
        $queryCountBeforeMapping = count(DB::getQueryLog());
        $rows = (new ProjectReportExport($projects, $columns))->collection();
        $queryCountAfterMapping = count(DB::getQueryLog());

        $this->assertCount(3, $rows);
        $this->assertSame('Active export', $rows[0]['Timeline Name']);
        $this->assertSame('Active', $rows[0]['Timeline Status']);
        $this->assertSame('02h : 00m', $rows[0]['Actual Hours']);
        $this->assertSame('200%', $rows[0]['Progress']);
        $this->assertSame('Completed export', $rows[1]['Timeline Name']);
        $this->assertSame('Completed', $rows[1]['Timeline Status']);
        $this->assertSame('Fallback project', $rows[2]['Project Name']);
        $this->assertSame('-', $rows[2]['Timeline Name']);
        $this->assertSame($queryCountBeforeMapping, $queryCountAfterMapping);
        $this->assertSame($active->id, $projects->first()->reportTimelines[0]->id);
        $this->assertSame($completed->id, $projects->first()->reportTimelines[1]->id);
    }

    public function test_inaccessible_projects_and_their_timelines_are_excluded(): void
    {
        $otherUser = User::factory()->create();
        $visible = $this->createProject('Visible', ['added_by' => $this->user->id]);
        $hidden = $this->createProject('Hidden', ['added_by' => $otherUser->id]);
        $hidden->updateQuietly(['added_by' => $otherUser->id]);
        $this->createTimeline($visible, ProjectTimeline::STATUS_ACTIVE, 'Visible timeline');
        $this->createTimeline($hidden, ProjectTimeline::STATUS_ACTIVE, 'Hidden timeline');

        $projects = $this->service->exportProjects(Request::create('/reports/project/export'));

        $this->assertSame([$visible->id], $projects->pluck('id')->all());
        $this->assertSame(['Visible timeline'], $projects->first()->reportTimelines->pluck('name')->all());
    }

    public function test_customer_columns_are_hidden_without_permission_in_report_and_export(): void
    {
        Permission::firstOrCreate(['name' => 'reports.project_view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'project.customer_end_date', 'guard_name' => 'web']);
        $this->user->givePermissionTo('reports.project_view');

        $project = $this->createProject('No Customer Permission');
        $this->createTimeline(
            $project,
            ProjectTimeline::STATUS_ACTIVE,
            'Active timeline',
            '2026-10-01',
            '2026-10-31',
            3600,
            1800,
            '2026-11-15',
            7200
        );
        $this->createTimeline(
            $project,
            ProjectTimeline::STATUS_COMPLETED,
            'Completed timeline',
            '2026-09-01',
            '2026-09-30',
            3600,
            3600,
            '2026-10-05',
            7200
        );

        $columns = $this->service->getColumnLabels();
        $this->assertArrayNotHasKey('customer_end_date', $columns);
        $this->assertArrayNotHasKey('customer_estimated_hours', $columns);
        $this->assertArrayNotHasKey('customer_progress', $columns);

        $exportRequest = Request::create('/reports/project/export', 'GET', [
            'visible_columns' => 'project_name,end_date,customer_end_date,estimated_hours,customer_estimated_hours,progress,customer_progress',
        ]);
        $exportColumns = $this->service->resolveExportColumns($exportRequest);
        $this->assertSame([
            'project_name' => 'Project Name',
            'end_date' => 'End Date',
            'estimated_hours' => 'Estimated Hours',
            'progress' => 'Progress',
        ], $exportColumns);

        $response = $this->get(route('reports.projects'));
        $response->assertOk();
        $response->assertDontSee('Customer End Date');
        $response->assertDontSee('Cust Estimate');
        $response->assertDontSee('Cust Progress');
        $response->assertDontSee('col-customer_end_date');
        $response->assertDontSee('col-customer_estimated_hours');
        $response->assertDontSee('col-customer_progress');
    }

    public function test_customer_columns_are_included_with_permission_in_report_accordion_and_export(): void
    {
        Permission::firstOrCreate(['name' => 'reports.project_view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reports.project_export', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'project.customer_end_date', 'guard_name' => 'web']);
        $this->user->givePermissionTo(['reports.project_view', 'reports.project_export', 'project.customer_end_date']);

        $project = $this->createProject('With Customer Permission');
        $this->createTimeline(
            $project,
            ProjectTimeline::STATUS_ACTIVE,
            'Active customer timeline',
            '2026-10-01',
            '2026-10-31',
            3600,
            3600,
            '2026-11-15',
            7200
        );
        $this->createTimeline(
            $project,
            ProjectTimeline::STATUS_COMPLETED,
            'Completed customer timeline',
            '2026-09-01',
            '2026-09-30',
            3600,
            5400,
            '2026-10-05',
            3600
        );

        $columns = $this->service->getColumnLabels();
        $columnKeys = array_keys($columns);
        $this->assertSame(array_search('end_date', $columnKeys, true) + 1, array_search('customer_end_date', $columnKeys, true));
        $this->assertSame(array_search('estimated_hours', $columnKeys, true) + 1, array_search('customer_estimated_hours', $columnKeys, true));
        $this->assertSame(array_search('progress', $columnKeys, true) + 1, array_search('customer_progress', $columnKeys, true));
        $this->assertSame('Customer End Date', $columns['customer_end_date']);
        $this->assertSame('Cust Estimate', $columns['customer_estimated_hours']);
        $this->assertSame('Cust Progress', $columns['customer_progress']);

        $exportRequest = Request::create('/reports/project/export', 'GET', [
            'visible_columns' => 'project_name,end_date,customer_end_date,estimated_hours,customer_estimated_hours,actual_hours,progress,customer_progress',
        ]);
        $projects = $this->service->exportProjects($exportRequest);
        $exportColumns = $this->service->resolveExportColumns($exportRequest);
        $rows = (new ProjectReportExport($projects, $exportColumns))->collection();

        $this->assertCount(2, $rows);
        $this->assertSame(AppServiceProvider::formatAppDate('2026-11-15', '-'), $rows[0]['Customer End Date']);
        $this->assertSame('02h : 00m', $rows[0]['Cust Estimate']);
        $this->assertSame('50%', $rows[0]['Cust Progress']);
        $this->assertSame(AppServiceProvider::formatAppDate('2026-10-05', '-'), $rows[1]['Customer End Date']);
        $this->assertSame('01h : 00m', $rows[1]['Cust Estimate']);
        $this->assertSame('150%', $rows[1]['Cust Progress']);

        $response = $this->get(route('reports.projects'));
        $response->assertOk();
        $response->assertSee('Customer End Date');
        $response->assertSee('Cust Estimate');
        $response->assertSee('Cust Progress');
        $response->assertSee(AppServiceProvider::formatAppDate('2026-11-15', '-'));
        $response->assertSee(AppServiceProvider::formatAppDate('2026-10-05', '-'));
        $response->assertSee('50%');
        $response->assertSee('150%');
    }

    private function createProject(string $name, array $attributes = []): Project
    {
        return Project::factory()->create(array_merge([
            'name' => $name,
            'added_by' => $this->user->id,
            'is_system' => false,
        ], $attributes));
    }

    private function createTimeline(
        Project $project,
        int $status,
        string $name,
        string $startDate = '2026-10-01',
        string $endDate = '2026-10-31',
        int $estimatedSeconds = 3600,
        int $actualSeconds = 0,
        ?string $customerEndDate = null,
        ?int $customerEstimateSeconds = null,
    ): ProjectTimeline {
        return ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => $name,
            'type' => ProjectTimeline::DEFAULT_TYPE,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'customer_end_date' => $customerEndDate,
            'estimated_time_seconds' => $estimatedSeconds,
            'customer_estimate_seconds' => $customerEstimateSeconds,
            'actual_time_seconds' => $actualSeconds,
            'sort_order' => ((int) $project->projectTimelines()->max('sort_order')) + 1,
            'created_by' => $this->user->id,
        ]);
    }
}
