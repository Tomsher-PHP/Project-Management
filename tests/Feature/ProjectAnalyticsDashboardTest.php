<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\ServiceGroup;
use App\Models\ServiceType;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectAnalyticsDashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $memberUser;
    protected User $unauthorizedUser;
    protected ProjectStatus $activeStatus;
    protected ProjectStatus $completedStatus;
    protected ProjectStage $testStage;
    protected ServiceGroup $serviceGroup;
    protected ServiceType $serviceTypeA;
    protected ServiceType $serviceTypeB;
    protected TaskStatus $taskStatusActive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->is_super_admin = true;
        $this->superAdmin->save();
        $this->superAdmin->givePermissionTo('project.customer_end_date');

        $this->memberUser = User::factory()->create();
        $this->memberUser->is_super_admin = false;
        $this->memberUser->save();

        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->is_super_admin = false;
        $this->unauthorizedUser->save();

        $this->activeStatus = ProjectStatus::firstOrCreate(
            ['code' => 'dash_active'],
            [
                'name' => 'Active',
                'color' => '#10B981',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'sort_order' => 1,
                'is_default' => true,
                'is_completed' => false,
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $this->completedStatus = ProjectStatus::firstOrCreate(
            ['code' => 'dash_completed'],
            [
                'name' => 'Completed',
                'color' => '#8B5CF6',
                'type' => ProjectStatus::TYPE_COMPLETED,
                'sort_order' => 2,
                'is_default' => false,
                'is_completed' => true,
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $this->testStage = ProjectStage::firstOrCreate(
            ['code' => 'dash_stage_1'],
            [
                'name' => 'Development Stage',
                'color' => '#3B82F6',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->serviceGroup = ServiceGroup::firstOrCreate(
            ['code' => 'dash_grp'],
            [
                'name' => 'Dashboard Service Group',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->serviceTypeA = ServiceType::firstOrCreate(
            ['code' => 'dash_st_a'],
            [
                'name' => 'Web App Dev',
                'color' => '#06B6D4',
                'service_group_id' => $this->serviceGroup->id,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->serviceTypeB = ServiceType::firstOrCreate(
            ['code' => 'dash_st_b'],
            [
                'name' => 'Mobile Dev',
                'color' => '#F59E0B',
                'service_group_id' => $this->serviceGroup->id,
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $this->taskStatusActive = TaskStatus::firstOrCreate(
            ['name' => 'In Progress'],
            [
                'type' => 'active',
                'color' => '#3B82F6',
                'sort_order' => 1,
            ]
        );
    }

    /**
     * Initial page rendering renders ONLY page structure, header filters, and loading placeholders.
     * Confirms that initial page rendering does NOT calculate project summary counts or query project details into Blade.
     */
    public function test_initial_page_renders_only_structure_and_placeholders_without_calculating_summary_counts(): void
    {
        // Create projects in DB
        $project1 = Project::factory()->create([
            'name' => 'Heavy Project Alpha',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/project-analytics');

        $response->assertStatus(200);

        // Header defaults
        $response->assertSee('All Users');
        $response->assertSee('All Projects');

        // Confirm placeholders are present
        $response->assertSee('data-project-analytics-summary-section', false);
        $response->assertSee('data-project-analytics-summary-count="total_projects"', false);
        $response->assertSee('data-project-analytics-chart="flow"', false);
        $response->assertSee('data-project-analytics-chart="status"', false);
        $response->assertSee('data-project-analytics-chart="stage"', false);
        $response->assertSee('data-project-analytics-section="service-type"', false);
        $response->assertSee('data-project-analytics-section="upcoming-contracts"', false);

        // Confirm calculated counts are NOT in view data
        $response->assertViewMissing('projectSummaryCounts');

        // Confirm full project list or project records are not embedded in HTML
        $response->assertDontSee($project1->name);
    }

    /**
     * Test AJAX summary count loading and accuracy.
     */
    public function test_ajax_summary_endpoint_calculates_and_returns_summary_counts(): void
    {
        $project = Project::factory()->create([
            'status_id' => $this->activeStatus->id,
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $task = Task::create([
            'name' => 'Sample Task',
            'project_id' => $project->id,
            'status_id' => $this->taskStatusActive->id,
            'request_status' => Task::REQUEST_APPROVED,
            'estimated_time_seconds' => 7200,
            'added_by' => $this->superAdmin->id,
        ]);

        TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $this->superAdmin->id,
            'start_time' => now()->subHours(1),
            'end_time' => now(),
            'duration_seconds' => 3600,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson('/project-analytics/summary');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'total_projects' => 1,
                'active_projects' => 1,
                'completed_projects' => 0,
                'total_tasks' => 1,
                'active_tasks' => 1,
                'completed_tasks' => 0,
                'total_worked_seconds' => 3600,
                'formatted_worked_time' => '01h 00m',
                'total_estimated_seconds' => 7200,
                'formatted_estimated_time' => '02h 00m',
            ],
        ]);
    }

    /**
     * Test AJAX summary endpoint with both filters individually and together.
     */
    public function test_ajax_summary_endpoint_with_user_and_project_filters(): void
    {
        // Project 1: assigned to memberUser
        $project1 = Project::factory()->create([
            'status_id' => $this->activeStatus->id,
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);
        ProjectMember::create([
            'project_id' => $project1->id,
            'user_id' => $this->memberUser->id,
            'added_by' => $this->superAdmin->id,
        ]);

        // Project 2: only superAdmin
        $project2 = Project::factory()->create([
            'status_id' => $this->completedStatus->id,
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // 1. Filter by user_id only
        $resUser = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/summary?user_id={$this->memberUser->id}");
        $resUser->assertStatus(200);
        $this->assertEquals(1, $resUser->json('data.total_projects'));
        $this->assertEquals(1, $resUser->json('data.active_projects'));
        $this->assertEquals(0, $resUser->json('data.completed_projects'));

        // 2. Filter by project_id only
        $resProj = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/summary?project_id={$project2->id}");
        $resProj->assertStatus(200);
        $this->assertEquals(1, $resProj->json('data.total_projects'));
        $this->assertEquals(0, $resProj->json('data.active_projects'));
        $this->assertEquals(1, $resProj->json('data.completed_projects'));

        // 3. Filter by both user_id and project_id together
        $resBoth = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/summary?user_id={$this->memberUser->id}&project_id={$project1->id}");
        $resBoth->assertStatus(200);
        $this->assertEquals(1, $resBoth->json('data.total_projects'));
    }

    /**
     * Test Projects by Service Type supports multiple Service Types per project and active filters.
     */
    public function test_projects_by_service_type_supports_multiple_service_types(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Attach both service types to this project
        DB::table('project_service_types')->insert([
            ['project_id' => $project->id, 'service_type_id' => $this->serviceTypeA->id],
            ['project_id' => $project->id, 'service_type_id' => $this->serviceTypeB->id],
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/projects-by-service-type?project_id={$project->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['total_projects']);

        $itemA = collect($data['items'])->firstWhere('id', $this->serviceTypeA->id);
        $itemB = collect($data['items'])->firstWhere('id', $this->serviceTypeB->id);

        $this->assertNotNull($itemA);
        $this->assertNotNull($itemB);
        $this->assertEquals(1, $itemA['count']);
        $this->assertEquals(1, $itemB['count']);
        $this->assertEquals($this->serviceTypeA->color, $itemA['color']);
        $this->assertEquals($this->serviceTypeB->color, $itemB['color']);
    }

    /**
     * Test Projects by Flow returns Agile and Linear counts and percentages.
     */
    public function test_projects_by_flow_returns_agile_and_linear_breakdown(): void
    {
        Project::factory()->create([
            'project_flow' => 'agile',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);
        Project::factory()->create([
            'project_flow' => 'linear',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson('/project-analytics/projects-by-flow');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(2, $data['total_projects']);
        $this->assertContains('Agile', $data['labels']);
        $this->assertContains('Linear', $data['labels']);

        $agileItem = collect($data['items'])->firstWhere('flow', 'agile');
        $linearItem = collect($data['items'])->firstWhere('flow', 'linear');

        $this->assertEquals(1, $agileItem['count']);
        $this->assertEquals(1, $linearItem['count']);
    }

    /**
     * Test Projects by Status and Projects by Stage return authoritative metadata.
     */
    public function test_projects_by_status_and_stage_return_authoritative_metadata(): void
    {
        $project = Project::factory()->create([
            'status_id' => $this->activeStatus->id,
            'project_stage_id' => $this->testStage->id,
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Status
        $resStatus = $this->actingAs($this->superAdmin)->getJson('/project-analytics/projects-by-status');
        $resStatus->assertStatus(200);
        $statusItem = collect($resStatus->json('data.items'))->firstWhere('id', $this->activeStatus->id);
        $this->assertNotNull($statusItem);
        $this->assertEquals(1, $statusItem['count']);
        $this->assertEquals($this->activeStatus->color, $statusItem['color']);

        // Stage
        $resStage = $this->actingAs($this->superAdmin)->getJson('/project-analytics/projects-by-stage');
        $resStage->assertStatus(200);
        $stageItem = collect($resStage->json('data.items'))->firstWhere('id', $this->testStage->id);
        $this->assertNotNull($stageItem);
        $this->assertEquals(1, $stageItem['count']);
        $this->assertEquals($this->testStage->color, $stageItem['color']);
    }

    /**
     * Test Upcoming Contract Expirations table endpoint:
     * - Returns nearest expiration first
     * - Excludes expired contracts
     * - Includes service types
     * - Respects active filters
     */
    public function test_upcoming_contract_expirations_endpoint_includes_service_types_and_filters(): void
    {
        $customer = Customer::create([
            'name' => 'Acme Corporation',
            'customer_code' => 'CUST_DASH_001',
            'added_by' => $this->superAdmin->id,
        ]);

        $project = Project::factory()->create([
            'name' => 'Contract Expiration Test Project',
            'customer_id' => $customer->id,
            'status_id' => $this->activeStatus->id,
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        DB::table('project_service_types')->insert([
            'project_id' => $project->id,
            'service_type_id' => $this->serviceTypeA->id,
        ]);

        // Upcoming timeline in 7 days (expiring soon)
        $timelineSoon = ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Phase 1 Contract',
            'start_date' => now()->subMonth(),
            'customer_end_date' => now()->addDays(7)->toDateString(),
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'type' => 'fixed',
        ]);

        // Upcoming timeline in 30 days
        $timelineLater = ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Phase 2 Contract',
            'start_date' => now()->subMonth(),
            'customer_end_date' => now()->addDays(30)->toDateString(),
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'type' => 'fixed',
        ]);

        // Expired timeline (must be excluded)
        $timelineExpired = ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Past Contract',
            'start_date' => now()->subMonths(3),
            'customer_end_date' => now()->subDays(5)->toDateString(),
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'type' => 'fixed',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/upcoming-contracts?project_id={$project->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertTrue($data['can_view']);
        $this->assertCount(2, $data['contracts']);

        // Sorted by nearest expiration first
        $this->assertEquals($timelineSoon->id, $data['contracts'][0]['id']);
        $this->assertEquals($timelineLater->id, $data['contracts'][1]['id']);

        // Service types included
        $firstContract = $data['contracts'][0];
        $this->assertEquals('Contract Expiration Test Project', $firstContract['project_name']);
        $this->assertEquals('Acme Corporation', $firstContract['customer_name']);
        $this->assertTrue($firstContract['is_expiring_soon']);
        $this->assertNotEmpty($firstContract['service_types']);
        $this->assertEquals('Web App Dev', $firstContract['service_types'][0]['name']);
        $this->assertEquals('Web App Dev', $firstContract['service_type_names']);
    }

    /**
     * Test empty state responses when no matching records exist.
     */
    public function test_empty_state_responses_when_no_matching_records(): void
    {
        // Fresh user with no accessible projects
        $emptyUser = User::factory()->create();
        $emptyUser->is_super_admin = false;
        $emptyUser->save();

        $responseSummary = $this->actingAs($emptyUser)->getJson('/project-analytics/summary');
        $responseSummary->assertStatus(200);
        $this->assertEquals(0, $responseSummary->json('data.total_projects'));

        $responseService = $this->actingAs($emptyUser)->getJson('/project-analytics/projects-by-service-type');
        $responseService->assertStatus(200);
        $this->assertEquals(0, $responseService->json('data.total_projects'));
        $this->assertEmpty($responseService->json('data.items'));

        $responseFlow = $this->actingAs($emptyUser)->getJson('/project-analytics/projects-by-flow');
        $responseFlow->assertStatus(200);
        $this->assertEquals(0, $responseFlow->json('data.total_projects'));

        $responseContracts = $this->actingAs($emptyUser)->getJson('/project-analytics/upcoming-contracts');
        $responseContracts->assertStatus(200);
        $this->assertEmpty($responseContracts->json('data.contracts'));
    }

    /**
     * Test unauthorized filter requests are rejected with 403 on AJAX endpoints.
     */
    public function test_unauthorized_filter_requests_are_rejected_with_403(): void
    {
        // Project owned by superAdmin
        $privateProject = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // regular user attempts to access privateProject data
        $response = $this->actingAs($this->memberUser)
            ->getJson("/project-analytics/summary?project_id={$privateProject->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You are not authorized to view data for this project.',
        ]);

        $responseContracts = $this->actingAs($this->memberUser)
            ->getJson("/project-analytics/upcoming-contracts?project_id={$privateProject->id}");

        $responseContracts->assertStatus(403);
    }
}
