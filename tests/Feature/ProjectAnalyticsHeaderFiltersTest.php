<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProjectAnalyticsHeaderFiltersTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $regularUser;
    protected ProjectStatus $activeStatus;
    protected ProjectStage $testStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->is_super_admin = true;
        $this->superAdmin->save();

        $this->regularUser = User::factory()->create();
        $this->regularUser->is_super_admin = false;
        $this->regularUser->save();

        $this->activeStatus = ProjectStatus::firstOrCreate(
            ['code' => 'active_filter_test'],
            [
                'name' => 'Active Filter Test',
                'color' => '#10B981',
                'type' => ProjectStatus::TYPE_OPEN,
                'sort_order' => 1,
                'is_default' => true,
                'is_completed' => false,
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $this->testStage = ProjectStage::firstOrCreate(
            ['code' => 'stage_filter_test'],
            [
                'name' => 'Stage Filter Test',
                'color' => '#6366F1',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }

    /**
     * Initial page rendering defaults to All Users and All Projects, without auto-selecting logged-in user.
     * Full project list is NOT loaded into the initial page DOM.
     */
    public function test_initial_page_rendering_header_defaults(): void
    {
        // Create multiple projects in DB
        $project1 = Project::factory()->create([
            'name' => 'Project Alpha For Initial Load Test',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);
        $project2 = Project::factory()->create([
            'name' => 'Project Beta For Initial Load Test',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/project-analytics');

        $response->assertStatus(200);

        // Header defaults
        $response->assertViewHas('projectAnalyticsSelectedUserId', '');
        $response->assertViewHas('projectAnalyticsSelectedProjectId', '');
        $response->assertViewHas('selectedProject', null);

        // All Users and All Projects are displayed as defaults
        $response->assertSee('All Users');
        $response->assertSee('All Projects');

        // Confirm full project list is NOT embedded in the initial page HTML
        $response->assertDontSee($project1->name);
        $response->assertDontSee($project2->name);
    }

    /**
     * User dropdown shows only users involved in projects the logged-in user is authorized to access.
     */
    public function test_user_dropdown_shows_only_users_involved_in_accessible_projects(): void
    {
        $involvedUser = User::factory()->create(['name' => 'Involved Teammate User']);
        $uninvolvedUser = User::factory()->create(['name' => 'Uninvolved Remote User']);

        // Create accessible project and attach involvedUser as a member
        $project = Project::factory()->create([
            'is_system' => false,
        ]);
        $project->added_by = $this->superAdmin->id;
        $project->save();

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $involvedUser->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/project-analytics');

        $response->assertStatus(200);
        $selectableUsers = $response->viewData('projectSelectableUsers');
        $this->assertTrue($selectableUsers->contains('id', $involvedUser->id));
        $this->assertFalse($selectableUsers->contains('id', $uninvolvedUser->id));

        $response->assertSee($involvedUser->name);
        $response->assertDontSee($uninvolvedUser->name);
    }

    /**
     * Project lazy loading: All Users selection loads all accessible projects on demand.
     */
    public function test_project_lazy_loading_all_users_loads_accessible_projects(): void
    {
        $accessibleProject = Project::factory()->create([
            'name' => 'Accessible Lazy Project',
            'is_system' => false,
        ]);
        $accessibleProject->added_by = $this->regularUser->id;
        $accessibleProject->save();

        $otherUser = User::factory()->create(['is_super_admin' => false]);
        $inaccessibleProject = Project::factory()->create([
            'name' => 'Inaccessible Lazy Project',
            'is_system' => false,
        ]);
        $inaccessibleProject->added_by = $otherUser->id;
        $inaccessibleProject->save();

        // When All Users is selected (no user_id passed)
        $response = $this->actingAs($this->regularUser)->getJson('/project-analytics/search-projects');

        $response->assertStatus(200);
        $results = collect($response->json());

        $this->assertTrue($results->contains('id', $accessibleProject->id));
        $this->assertFalse($results->contains('id', $inaccessibleProject->id));
    }

    /**
     * Project lazy loading: When a specific user is selected, loads only projects associated with that user.
     */
    public function test_project_lazy_loading_specific_user_loads_only_user_projects(): void
    {
        $targetUser = User::factory()->create();

        $projectWithUser = Project::factory()->create([
            'name' => 'Project Associated With User',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $projectWithoutUser = Project::factory()->create([
            'name' => 'Project Not Associated With User',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Attach targetUser to projectWithUser only
        ProjectMember::create([
            'project_id' => $projectWithUser->id,
            'user_id' => $targetUser->id,
            'added_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/search-projects?user_id={$targetUser->id}");

        $response->assertStatus(200);
        $results = collect($response->json());

        $this->assertTrue($results->contains('id', $projectWithUser->id));
        $this->assertFalse($results->contains('id', $projectWithoutUser->id));
    }

    /**
     * Search and pagination in project lazy loading.
     */
    public function test_search_and_pagination_in_project_lazy_loading(): void
    {
        $projectAlpha = Project::factory()->create([
            'name' => 'Zeus Project Alpha',
            'project_code' => 'PRJZEUS01',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $projectBeta = Project::factory()->create([
            'name' => 'Zeus Project Beta',
            'project_code' => 'PRJZEUS02',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Search by query
        $searchResponse = $this->actingAs($this->superAdmin)
            ->getJson('/project-analytics/search-projects?q=Alpha');

        $searchResponse->assertStatus(200);
        $searchResults = collect($searchResponse->json());
        $this->assertTrue($searchResults->contains('id', $projectAlpha->id));
        $this->assertFalse($searchResults->contains('id', $projectBeta->id));

        // Pagination limit
        $pagedResponse = $this->actingAs($this->superAdmin)
            ->getJson('/project-analytics/search-projects?q=Zeus&limit=1&page=1');

        $pagedResponse->assertStatus(200);
        $this->assertCount(1, $pagedResponse->json());
    }

    /**
     * Validating project existence for user when changing user selection:
     * - Valid project returns the project option
     * - Invalid project returns empty array
     */
    public function test_validating_project_selection_when_changing_user(): void
    {
        $targetUser = User::factory()->create();

        $projectA = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $projectB = Project::factory()->create([
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        // Attach user to Project A only
        ProjectMember::create([
            'project_id' => $projectA->id,
            'user_id' => $targetUser->id,
            'added_by' => $this->superAdmin->id,
        ]);

        // Check Project A for targetUser: must return Project A
        $responseA = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/search-projects?user_id={$targetUser->id}&project_id={$projectA->id}");

        $responseA->assertStatus(200);
        $this->assertTrue(collect($responseA->json())->contains('id', $projectA->id));

        // Check Project B for targetUser: must return empty array
        $responseB = $this->actingAs($this->superAdmin)
            ->getJson("/project-analytics/search-projects?user_id={$targetUser->id}&project_id={$projectB->id}");

        $responseB->assertStatus(200);
        $this->assertEmpty($responseB->json());
    }

    /**
     * Server-side security checks: unauthorized users or projects cannot be requested.
     */
    public function test_server_side_authorization_prevents_unauthorized_manipulation(): void
    {
        $otherUser = User::factory()->create(['is_super_admin' => false]);
        $inaccessibleProject = Project::factory()->create([
            'added_by' => $otherUser->id,
            'is_system' => false,
        ]);

        // Regular user requesting inaccessible project on page request
        $this->actingAs($this->regularUser)
            ->get("/project-analytics?project_id={$inaccessibleProject->id}")
            ->assertStatus(403);

        // Regular user requesting inaccessible user on page request
        $this->actingAs($this->regularUser)
            ->get("/project-analytics?user_id={$otherUser->id}")
            ->assertStatus(403);

        // Regular user requesting inaccessible project on search endpoint
        $this->actingAs($this->regularUser)
            ->getJson("/project-analytics/search-projects?user_id={$otherUser->id}")
            ->assertStatus(403);
    }

    /**
     * Preserving valid project selection when requested in URL.
     */
    public function test_page_request_with_valid_parameters_preserves_selection(): void
    {
        $project = Project::factory()->create([
            'name' => 'Preserved Selected Project',
            'added_by' => $this->superAdmin->id,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get("/project-analytics?project_id={$project->id}");

        $response->assertStatus(200);
        $response->assertViewHas('projectAnalyticsSelectedProjectId', (string) $project->id);
        $response->assertViewHas('selectedProject');

        // Only the selected project is rendered in the initial DOM
        $response->assertSee($project->name);
    }
}
