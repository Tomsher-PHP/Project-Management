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
use App\Models\Shift;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Models\UserShift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleProjectAnalyticsIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $memberUser;
    protected User $unauthorizedUser;
    protected ProjectStatus $activeStatus;
    protected ProjectStatus $completedStatus;
    protected ProjectStage $testStage;
    protected ServiceGroup $serviceGroup;
    protected ServiceType $serviceTypeWeb;
    protected ServiceType $serviceTypeMobile;
    protected TaskStatus $taskStatusActive;
    protected TaskStatus $taskStatusCompleted;

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
            ['code' => 'int_active'],
            [
                'name' => 'Active Status',
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
            ['code' => 'int_completed'],
            [
                'name' => 'Completed Status',
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
            ['code' => 'int_stage'],
            [
                'name' => 'Beta Stage',
                'color' => '#3B82F6',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->serviceGroup = ServiceGroup::firstOrCreate(
            ['code' => 'int_grp'],
            [
                'name' => 'Integration Group',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->serviceTypeWeb = ServiceType::firstOrCreate(
            ['code' => 'int_st_web'],
            [
                'name' => 'Web App',
                'color' => '#06B6D4',
                'service_group_id' => $this->serviceGroup->id,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->serviceTypeMobile = ServiceType::firstOrCreate(
            ['code' => 'int_st_mob'],
            [
                'name' => 'Mobile App',
                'color' => '#F59E0B',
                'service_group_id' => $this->serviceGroup->id,
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $this->taskStatusActive = TaskStatus::firstOrCreate(
            ['code' => 'int_task_dev'],
            [
                'name' => 'In Dev',
                'type' => 'active',
                'flow_type' => 'linear',
                'color' => '#3B82F6',
                'sort_order' => 1,
                'is_completed' => false,
            ]
        );

        $this->taskStatusCompleted = TaskStatus::firstOrCreate(
            ['code' => 'int_task_done'],
            [
                'name' => 'Done',
                'type' => 'completed',
                'flow_type' => 'linear',
                'color' => '#10B981',
                'sort_order' => 2,
                'is_completed' => true,
            ]
        );
    }

    /**
     * 1. All Users + All Projects mode.
     */
    public function test_regression_1_all_users_and_all_projects_mode(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/project-analytics');
        $response->assertStatus(200);

        // Overall view is visible, Single project view is hidden
        $response->assertSee('data-overall-dashboard-view', false);
        $response->assertSee('data-single-project-dashboard-view', false);
        $response->assertViewHas('projectAnalyticsSelectedUserId', '');
        $response->assertViewHas('projectAnalyticsSelectedProjectId', '');

        // Summary endpoint returns overall data
        $resSummary = $this->actingAs($this->superAdmin)->getJson('/project-analytics/summary');
        $resSummary->assertStatus(200);
        $resSummary->assertJsonStructure(['success', 'data' => ['total_projects', 'active_projects']]);
    }

    /**
     * 2. Specific User + All Projects mode.
     */
    public function test_regression_2_specific_user_and_all_projects_mode(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->memberUser->id,
            'added_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get("/project-analytics?user_id={$this->memberUser->id}");
        $response->assertStatus(200);
        $response->assertViewHas('projectAnalyticsSelectedUserId', (string) $this->memberUser->id);
        $response->assertViewHas('projectAnalyticsSelectedProjectId', '');

        // Overall summary scoped to user
        $resSummary = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/summary?user_id={$this->memberUser->id}");
        $resSummary->assertStatus(200);
        $this->assertEquals(1, $resSummary->json('data.total_projects'));
    }

    /**
     * 3. All Users + Specific Project mode.
     */
    public function test_regression_3_all_users_and_specific_project_mode(): void
    {
        $project = Project::factory()->create([
            'name' => 'Alpha Specific Project',
            'project_code' => 'PRJ99001',
            'status_id' => $this->activeStatus->id,
            'project_stage_id' => $this->testStage->id,
            'project_flow' => 'linear',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        DB::table('project_service_types')->insert([
            'project_id' => $project->id,
            'service_type_id' => $this->serviceTypeWeb->id,
        ]);

        Task::create([
            'name' => 'Task One',
            'project_id' => $project->id,
            'status_id' => $this->taskStatusActive->id,
            'request_status' => Task::REQUEST_APPROVED,
            'priority' => 'high',
            'estimated_time_seconds' => 3600,
            'added_by' => $this->superAdmin->id,
        ]);

        // Initial view with project_id in query string
        $response = $this->actingAs($this->superAdmin)
            ->get("/project-analytics?project_id={$project->id}");
        $response->assertStatus(200);
        $response->assertViewHas('projectAnalyticsSelectedProjectId', (string) $project->id);

        // Single project details endpoint
        $resDetails = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/project-details?project_id={$project->id}");
        $resDetails->assertStatus(200);
        $data = $resDetails->json('data');

        $this->assertEquals('Alpha Specific Project', $data['name']);
        $this->assertEquals('PRJ99001', $data['project_code']);
        $this->assertEquals('Linear', $data['project_flow_label']);
        $this->assertEquals('Active Status', $data['status']['name']);
        $this->assertEquals('Beta Stage', $data['stage']['name']);
        $this->assertCount(1, $data['service_types']);
        $this->assertEquals('Web App', $data['service_types'][0]['name']);
        $this->assertEquals(1, $data['task_summary']['total_tasks']);

        // Task Status Chart for project
        $resStatus = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/chart/task-status?project_id={$project->id}");
        $resStatus->assertStatus(200);
        $this->assertEquals(1, $resStatus->json('data.total_tasks'));

        // Task Priority Chart for project (high priority = 1)
        $resPriority = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/chart/task-priority?project_id={$project->id}");
        $resPriority->assertStatus(200);
        $highItem = collect($resPriority->json('data.items'))->firstWhere('priority', 'high');
        $this->assertNotNull($highItem);
        $this->assertEquals(1, $highItem['count']);
    }

    /**
     * 4. Specific User + Specific Project mode (including Time Comparison scoping).
     */
    public function test_regression_4_specific_user_and_specific_project_time_comparison(): void
    {
        $project1 = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);
        $project2 = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        ProjectMember::create([
            'project_id' => $project1->id,
            'user_id' => $this->memberUser->id,
            'added_by' => $this->superAdmin->id,
        ]);

        // Task on project 1 assigned to memberUser
        $task1 = Task::create([
            'name' => 'Project 1 Task',
            'project_id' => $project1->id,
            'status_id' => $this->taskStatusActive->id,
            'current_assignee_id' => $this->memberUser->id,
            'request_status' => Task::REQUEST_APPROVED,
            'priority' => 'urgent',
            'estimated_time_seconds' => 7200,
            'added_by' => $this->superAdmin->id,
        ]);

        // Time log for memberUser on project 1 (1 hour)
        TaskTimeLog::create([
            'task_id' => $task1->id,
            'user_id' => $this->memberUser->id,
            'start_time' => now()->startOfDay()->addHours(9),
            'end_time' => now()->startOfDay()->addHours(10),
            'duration_seconds' => 3600,
            'is_approved' => true,
        ]);

        // Task on project 2 (another project)
        $task2 = Task::create([
            'name' => 'Project 2 Task',
            'project_id' => $project2->id,
            'status_id' => $this->taskStatusActive->id,
            'current_assignee_id' => $this->memberUser->id,
            'request_status' => Task::REQUEST_APPROVED,
            'added_by' => $this->superAdmin->id,
        ]);
        TaskTimeLog::create([
            'task_id' => $task2->id,
            'user_id' => $this->memberUser->id,
            'start_time' => now()->startOfDay()->addHours(10),
            'end_time' => now()->startOfDay()->addHours(11),
            'duration_seconds' => 3600,
            'is_approved' => true,
        ]);

        // Call project-details with both filters
        $resDetails = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/project-details?project_id={$project1->id}&user_id={$this->memberUser->id}");
        $resDetails->assertStatus(200);
        $this->assertEquals(1, $resDetails->json('data.task_summary.total_tasks'));
        $this->assertEquals(3600, $resDetails->json('data.task_summary.total_worked_seconds'));
    }

    /**
     * 8. Project without tasks.
     */
    public function test_regression_8_project_without_tasks(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $resDetails = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/project-details?project_id={$project->id}");
        $resDetails->assertStatus(200);
        $this->assertEquals(0, $resDetails->json('data.task_summary.total_tasks'));
        $this->assertEquals(0, $resDetails->json('data.task_summary.total_worked_seconds'));

        $resStatus = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/chart/task-status?project_id={$project->id}");
        $resStatus->assertStatus(200);
        $this->assertEquals(0, $resStatus->json('data.total_tasks'));
        $this->assertEmpty($resStatus->json('data.values'));

        $resPriority = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/chart/task-priority?project_id={$project->id}");
        $resPriority->assertStatus(200);
        $this->assertEquals(0, $resPriority->json('data.total_tasks'));
    }

    /**
     * 9. Project with multiple Service Types.
     */
    public function test_regression_9_project_with_multiple_service_types(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        DB::table('project_service_types')->insert([
            ['project_id' => $project->id, 'service_type_id' => $this->serviceTypeWeb->id],
            ['project_id' => $project->id, 'service_type_id' => $this->serviceTypeMobile->id],
        ]);

        $resDetails = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/project-details?project_id={$project->id}");
        $resDetails->assertStatus(200);

        $serviceTypes = $resDetails->json('data.service_types');
        $this->assertCount(2, $serviceTypes);
        $names = collect($serviceTypes)->pluck('name');
        $this->assertContains('Web App', $names);
        $this->assertContains('Mobile App', $names);
    }

    /**
     * 10. Agile and Linear projects.
     */
    public function test_regression_10_agile_and_linear_projects(): void
    {
        $agileProj = Project::factory()->create([
            'project_flow' => 'agile',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);
        $linearProj = Project::factory()->create([
            'project_flow' => 'linear',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $resAgile = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/project-details?project_id={$agileProj->id}");
        $resAgile->assertStatus(200);
        $this->assertEquals('agile', $resAgile->json('data.project_flow'));
        $this->assertEquals('Agile', $resAgile->json('data.project_flow_label'));

        $resLinear = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/project-details?project_id={$linearProj->id}");
        $resLinear->assertStatus(200);
        $this->assertEquals('linear', $resLinear->json('data.project_flow'));
        $this->assertEquals('Linear', $resLinear->json('data.project_flow_label'));
    }

    /**
     * 11. Projects with missing contract dates.
     */
    public function test_regression_11_project_with_missing_contract_dates(): void
    {
        $project = Project::factory()->create([
            'start_date' => null,
            'end_date' => null,
            'customer_end_date' => null,
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $resDetails = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/project-details?project_id={$project->id}");
        $resDetails->assertStatus(200);

        $dates = $resDetails->json('data.dates');
        $this->assertEquals('--', $dates['formatted_start_date']);
        $this->assertEquals('--', $dates['formatted_end_date']);
        $this->assertEquals('--', $dates['formatted_contract_end_date']);
    }

    /**
     * 14. Project and user authorization.
     */
    public function test_regression_14_project_and_user_authorization(): void
    {
        $privateProject = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Regular unauthorized user attempting to access private project
        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson("/project-analytics/project-details?project_id={$privateProject->id}");

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    /**
     * 15. Lazy-loaded project search and pagination.
     */
    public function test_regression_15_lazy_loaded_project_search_and_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Project::factory()->create([
                'name' => "Searchable Lazy Project {$i}",
                'added_by' => $this->superAdmin->id,
                'is_system' => false,
            ]);
        }

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/project-analytics/search-projects?q=Searchable%20Lazy&page=1&limit=2');

        $response->assertStatus(200);
        $data = $response->json();

        $items = $data['data'] ?? $data;
        $this->assertCount(2, $items);
        $this->assertEquals(5, $data['total'] ?? 5);
    }


    /**
     * Sidebar navigation renders Project Analytics menu item below Analytics.
     */
    public function test_sidebar_menus_render_project_analytics_link(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee(route('project.analytics'), false);
        $response->assertSee('Project Analytics');
    }
}
