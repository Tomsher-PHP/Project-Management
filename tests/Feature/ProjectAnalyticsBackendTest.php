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
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectAnalyticsBackendTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $regularUser;
    protected ProjectStatus $activeStatus;
    protected ProjectStatus $completedStatus;
    protected ProjectStage $testStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $this->activeStatus = ProjectStatus::firstOrCreate(
            ['code' => 'active_test'],
            [
                'name' => 'Active Test',
                'color' => '#10B981',
                'type' => ProjectStatus::TYPE_OPEN,
                'sort_order' => 1,
                'is_default' => true,
                'is_completed' => false,
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $this->completedStatus = ProjectStatus::firstOrCreate(
            ['code' => 'completed_test'],
            [
                'name' => 'Completed Test',
                'color' => '#3B82F6',
                'type' => ProjectStatus::TYPE_COMPLETED,
                'sort_order' => 2,
                'is_default' => false,
                'is_completed' => true,
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $this->testStage = ProjectStage::firstOrCreate(
            ['code' => 'stage_dev_test'],
            [
                'name' => 'Development Test',
                'color' => '#6366F1',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        // Ensure permission exists for tests
        Permission::firstOrCreate(['name' => 'project.customer_end_date', 'guard_name' => 'web']);
    }

    /**
     * Unauthenticated requests to project analytics endpoints must redirect to login.
     */
    public function test_unauthenticated_user_cannot_access_project_analytics(): void
    {
        $this->get('/project-analytics')->assertStatus(302)->assertRedirect('/login');
        $this->getJson('/project-analytics/summary')->assertStatus(401);
        $this->getJson('/project-analytics/projects-by-service-type')->assertStatus(401);
    }

    /**
     * Initial Blade page request must return 200 and not calculate or contain summary counts.
     */
    public function test_blade_index_does_not_calculate_summary_counts(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/project-analytics');

        $response->assertStatus(200);
        $response->assertViewHas('selectableUsers');
        $response->assertViewHas('priorityOptions');

        // Initial view must not have computed summary metrics in view data
        $response->assertViewMissing('total_projects');
        $response->assertViewMissing('total_tasks');
    }

    /**
     * Unauthorized user and project filters must return HTTP 403 JSON responses.
     */
    public function test_unauthorized_user_and_project_filters_return_403(): void
    {
        $otherUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $inaccessibleProject = Project::factory()->create([
            'added_by' => $otherUser->id,
            'is_system' => false,
        ]);

        // Regular user trying to access other user's data without permissions
        $response = $this->actingAs($this->regularUser)
            ->getJson("/project-analytics/summary?user_id={$otherUser->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You are not authorized to view data for this user.',
        ]);

        // Regular user trying to access project they are not member/creator of
        $projectResponse = $this->actingAs($this->regularUser)
            ->getJson("/project-analytics/summary?project_id={$inaccessibleProject->id}");

        $projectResponse->assertStatus(403);
        $projectResponse->assertJson([
            'success' => false,
            'message' => 'You are not authorized to view data for this project.',
        ]);
    }

    /**
     * Dedicated AJAX summary endpoint returns correct aggregation.
     */
    public function test_summary_counts_endpoint_aggregates_metrics(): void
    {
        $project1 = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'status_id' => $this->activeStatus->id,
            'project_stage_id' => $this->testStage->id,
            'is_system' => false,
        ]);

        $project2 = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'status_id' => $this->completedStatus->id,
            'project_stage_id' => $this->testStage->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/summary?project_id={$project1->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_projects',
                'active_projects',
                'completed_projects',
                'total_tasks',
                'active_tasks',
                'completed_tasks',
                'pending_tasks',
                'total_worked_seconds',
                'formatted_worked_time',
                'total_estimated_seconds',
                'formatted_estimated_time',
                'upcoming_contracts',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['total_projects']);
        $this->assertEquals(1, $data['active_projects']);
        $this->assertEquals(0, $data['completed_projects']);
    }

    /**
     * Projects by Service Type supports projects assigned to multiple Service Types.
     */
    public function test_projects_by_service_type_supports_multiple_types(): void
    {
        $group = ServiceGroup::firstOrCreate(
            ['code' => 'grp_test'],
            ['name' => 'Test Group', 'sort_order' => 1, 'is_active' => true]
        );

        $st1 = ServiceType::firstOrCreate(
            ['code' => 'st_web_test'],
            ['name' => 'Web Dev Test', 'color' => '#3B82F6', 'service_group_id' => $group->id, 'is_active' => true, 'sort_order' => 1]
        );
        $st2 = ServiceType::firstOrCreate(
            ['code' => 'st_mob_test'],
            ['name' => 'Mobile Dev Test', 'color' => '#10B981', 'service_group_id' => $group->id, 'is_active' => true, 'sort_order' => 2]
        );

        $multiProject = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Assign project to both service types
        DB::table('project_service_types')->insert([
            ['project_id' => $multiProject->id, 'service_type_id' => $st1->id, 'created_at' => now(), 'updated_at' => now()],
            ['project_id' => $multiProject->id, 'service_type_id' => $st2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/projects-by-service-type?project_id={$multiProject->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'labels',
                'values',
                'colors',
                'items',
                'total_projects',
            ],
        ]);

        $items = collect($response->json('data.items'));
        $item1 = $items->firstWhere('id', $st1->id);
        $item2 = $items->firstWhere('id', $st2->id);

        $this->assertNotNull($item1);
        $this->assertNotNull($item2);
        $this->assertEquals(1, $item1['count']);
        $this->assertEquals(1, $item2['count']);
    }

    /**
     * Projects by Flow groups by Agile and Linear values.
     */
    public function test_projects_by_flow_groups_agile_and_linear(): void
    {
        $agileProject = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'project_flow' => 'agile',
            'is_system' => false,
        ]);

        $linearProject = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'project_flow' => 'linear',
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/project-analytics/projects-by-flow');

        $response->assertStatus(200);
        $items = collect($response->json('data.items'));

        $agileItem = $items->firstWhere('flow', 'agile');
        $linearItem = $items->firstWhere('flow', 'linear');

        $this->assertNotNull($agileItem);
        $this->assertNotNull($linearItem);
        $this->assertGreaterThanOrEqual(1, $agileItem['count']);
        $this->assertGreaterThanOrEqual(1, $linearItem['count']);
    }

    /**
     * Projects by Status groups by authoritative status implementation.
     */
    public function test_projects_by_status(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'status_id' => $this->activeStatus->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/projects-by-status?project_id={$project->id}");

        $response->assertStatus(200);
        $items = collect($response->json('data.items'));
        $activeItem = $items->firstWhere('id', $this->activeStatus->id);

        $this->assertNotNull($activeItem);
        $this->assertEquals(1, $activeItem['count']);
    }

    /**
     * Projects by Stage uses authoritative project stages.
     */
    public function test_projects_by_stage(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'project_stage_id' => $this->testStage->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/projects-by-stage?project_id={$project->id}");

        $response->assertStatus(200);
        $items = collect($response->json('data.items'));
        $stageItem = $items->firstWhere('id', $this->testStage->id);

        $this->assertNotNull($stageItem);
        $this->assertEquals(1, $stageItem['count']);
    }

    /**
     * Upcoming contract expirations excludes expired contracts and checks permission.
     */
    public function test_upcoming_contract_expirations(): void
    {
        $project = Project::factory()->create([
            'status_id' => $this->activeStatus->id,
            'is_system' => false,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->regularUser->id,
            'added_by' => $this->superAdmin->id,
        ]);

        $futureDate = now()->addDays(10)->toDateString();
        $pastDate = now()->subDays(5)->toDateString();

        // 1. Future active timeline (should be included)
        $futureTimeline = ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Future Contract',
            'start_date' => now()->subMonth(),
            'customer_end_date' => $futureDate,
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'type' => 'fixed',
            'customer_estimate_seconds' => 36000,
        ]);

        // 2. Past timeline (must be excluded)
        $pastTimeline = ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Expired Contract',
            'start_date' => now()->subMonths(2),
            'customer_end_date' => $pastDate,
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'type' => 'fixed',
        ]);

        // 3. Future completed timeline (must be excluded)
        $completedTimeline = ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => 'Completed Contract',
            'start_date' => now()->subMonth(),
            'customer_end_date' => $futureDate,
            'status' => ProjectTimeline::STATUS_COMPLETED,
            'type' => 'fixed',
        ]);

        // User without permission
        $responseNoPerm = $this->actingAs($this->regularUser)
            ->getJson("/project-analytics/upcoming-contracts?project_id={$project->id}");

        $responseNoPerm->assertStatus(200);
        $this->assertFalse($responseNoPerm->json('data.can_view'));
        $this->assertEmpty($responseNoPerm->json('data.contracts'));

        // User with permission
        $this->superAdmin->givePermissionTo('project.customer_end_date');
        $responseWithPerm = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/upcoming-contracts?project_id={$project->id}");

        $responseWithPerm->assertStatus(200);
        $this->assertTrue($responseWithPerm->json('data.can_view'));

        $contracts = collect($responseWithPerm->json('data.contracts'));
        $this->assertTrue($contracts->contains('id', $futureTimeline->id));
        $this->assertFalse($contracts->contains('id', $pastTimeline->id));
        $this->assertFalse($contracts->contains('id', $completedTimeline->id));

        $contractData = $contracts->firstWhere('id', $futureTimeline->id);
        $this->assertArrayHasKey('timeline_name', $contractData);
        $this->assertArrayHasKey('customer_name', $contractData);
        $this->assertArrayHasKey('days_remaining', $contractData);
        $this->assertArrayHasKey('formatted_customer_end_date', $contractData);
        $this->assertArrayHasKey('formatted_customer_estimate', $contractData);
    }

    /**
     * Task status and priority charts for a project.
     */
    public function test_task_status_and_priority_charts(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
            'project_flow' => 'agile',
        ]);

        $taskStatus = TaskStatus::firstOrCreate(
            ['flow_type' => 'agile', 'name' => 'In Progress Test'],
            [
                'code' => 'in_prog_test',
                'color' => '#F59E0B',
                'type' => 'active',
                'sort_order' => 1,
                'is_completed' => 0,
            ]
        );

        $task = Task::create([
            'project_id' => $project->id,
            'task_code' => 'TSK00001',
            'name' => 'Analytics Test Task',
            'status_id' => $taskStatus->id,
            'priority' => 'high',
            'request_status' => 'approved',
            'added_by' => $this->superAdmin->id,
            'current_assignee_id' => $this->superAdmin->id,
        ]);

        $statusResponse = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/chart/task-status?project_id={$project->id}");

        $statusResponse->assertStatus(200);
        $this->assertEquals(1, $statusResponse->json('data.total_tasks'));

        $priorityResponse = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/chart/task-priority?project_id={$project->id}");

        $priorityResponse->assertStatus(200);
        $highPriorityItem = collect($priorityResponse->json('data.items'))->firstWhere('priority', 'high');
        $this->assertNotNull($highPriorityItem);
        $this->assertEquals(1, $highPriorityItem['count']);
    }

    /**
     * User/project time comparison endpoint.
     */
    public function test_time_comparison_chart_endpoint(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/chart/time-comparison?project_id={$project->id}&date=" . now()->toDateString());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'labels',
                'values',
                'formatted_values',
                'colors',
            ],
        ]);
    }

    /**
     * Search projects endpoint for Tom Select autocomplete.
     */
    public function test_search_projects_endpoint(): void
    {
        $project = Project::factory()->create([
            'name' => 'Searchable Project Alpha',
            'project_code' => 'PRJ99901',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/project-analytics/search-projects?q=Alpha');

        $response->assertStatus(200);
        $results = collect($response->json());
        $this->assertTrue($results->contains('id', $project->id));
        $this->assertArrayHasKey('value', $results->first());
        $this->assertArrayHasKey('text', $results->first());
    }

    /**
     * User involvement filtering rules: user involved in Project A but not Project B.
     */
    public function test_user_involvement_filtering(): void
    {
        $memberUser = User::factory()->create(['is_super_admin' => false]);

        $projectA = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $projectB = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Add memberUser to Project A only
        ProjectMember::create([
            'project_id' => $projectA->id,
            'user_id' => $memberUser->id,
            'added_by' => $this->superAdmin->id,
        ]);

        // Filtering by memberUser as superAdmin
        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/summary?user_id={$memberUser->id}");

        $response->assertStatus(200);
        // Only 1 project involved
        $this->assertEquals(1, $response->json('data.total_projects'));

        // Filtering by memberUser AND projectB (which user is NOT involved in) -> 0 projects
        $responseCombined = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/summary?user_id={$memberUser->id}&project_id={$projectB->id}");

        $responseCombined->assertStatus(200);
        $this->assertEquals(0, $responseCombined->json('data.total_projects'));
    }

    /**
     * Empty datasets return valid structures with 0 counts.
     */
    public function test_empty_datasets_return_clean_structure(): void
    {
        $lonelyUser = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($lonelyUser)->getJson('/project-analytics/summary');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'total_projects' => 0,
                'active_projects' => 0,
                'completed_projects' => 0,
                'total_tasks' => 0,
            ],
        ]);
    }

    /**
     * Verify existing User Analytics endpoints continue to function without regression.
     */
    public function test_compatibility_with_existing_user_analytics_endpoints(): void
    {
        $project = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $this->actingAs($this->superAdmin)->get('/user-analytics')->assertStatus(200);
        $this->actingAs($this->superAdmin)->getJson('/user-analytics/summary')->assertStatus(200);
        $this->actingAs($this->superAdmin)->getJson('/user-analytics/chart/task-status')->assertStatus(200);
        $this->actingAs($this->superAdmin)->getJson('/user-analytics/chart/task-priority')->assertStatus(200);

        // Time comparison without project_id
        $timeResponse1 = $this->actingAs($this->superAdmin)
            ->getJson('/user-analytics/chart/time-comparison');
        $timeResponse1->assertStatus(200);

        // Time comparison with project_id (enhanced backwards compatibility)
        $timeResponse2 = $this->actingAs($this->superAdmin)
            ->getJson("/user-analytics/chart/time-comparison?project_id={$project->id}");
        $timeResponse2->assertStatus(200);
    }
}

