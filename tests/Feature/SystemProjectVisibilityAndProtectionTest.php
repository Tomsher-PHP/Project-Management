<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemProjectVisibilityAndProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectStatus $projectStatus;
    protected TaskType $defaultTaskType;
    protected TaskMode $defaultTaskMode;
    protected TaskStatus $linearDefaultStatus;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'project.view',
            'project.edit',
            'project.delete',
            'project.status_change',
            'task.view',
            'task.quick_task',
        ] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $this->projectStatus = ProjectStatus::create([
            'name' => 'Open',
            'code' => 'open',
            'color' => '#6c757d',
            'type' => 'open',
            'sort_order' => 1,
            'is_default' => true,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->defaultTaskType = TaskType::create([
            'name' => 'Normal',
            'code' => 'normal',
            'color' => '#a9aaa9',
            'sort_order' => 1,
            'is_default' => true,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->defaultTaskMode = TaskMode::create([
            'name' => 'New',
            'code' => 'new',
            'color' => '#3B82F6',
            'sort_order' => 1,
            'is_default' => true,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->linearDefaultStatus = TaskStatus::create([
            'flow_type' => 'linear',
            'name' => 'Open',
            'code' => 'open',
            'color' => '#6c757d',
            'type' => 'pending',
            'sort_order' => 1,
            'is_default' => true,
            'is_completed' => false,
            'is_active' => true,
            'is_system' => true,
        ]);
    }

    protected function createProject(array $attributes = []): Project
    {
        $isSystem = (bool) ($attributes['is_system'] ?? false);
        unset($attributes['is_system']);

        $project = Project::create(array_merge([
            'project_code' => Project::generateProjectCode(),
            'name' => 'Sample Project',
            'project_flow' => 'linear',
            'priority' => 'medium',
            'status_id' => $this->projectStatus->id,
            'estimated_time_seconds' => 0,
            'default_task_estimate_seconds' => 0,
            'default_billable' => false,
            'is_active' => true,
        ], $attributes));

        if ($isSystem) {
            $project->is_system = true;
            $project->save();
        }

        return $project->fresh();
    }

    protected function createTask(Project $project, User $assignee, array $attributes = []): Task
    {
        return Task::create(array_merge([
            'project_id' => $project->id,
            'name' => 'Sample Task',
            'status_id' => $this->linearDefaultStatus->id,
            'task_type_id' => $this->defaultTaskType->id,
            'task_mode_id' => $this->defaultTaskMode->id,
            'priority' => 'medium',
            'current_assignee_id' => $assignee->id,
            'added_by' => $assignee->id,
            'request_type' => Task::REQUEST_TYPE_ASSIGNED,
            'request_status' => Task::REQUEST_APPROVED,
        ], $attributes));
    }

    public function test_projects_list_and_detail_show_system_project_with_badge_and_hide_edit_delete(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $user->givePermissionTo(['project.view', 'project.edit', 'project.delete', 'project.status_change']);

        $systemProject = $this->createProject([
            'name' => 'Misc',
            'is_system' => true,
        ]);

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee($systemProject->name)
            ->assertSee('System')
            ->assertDontSee('project-delete-form')
            ->assertDontSee('project-edit-btn');

        $this->actingAs($user)
            ->get(route('projects.edit', $systemProject))
            ->assertOk()
            ->assertSee($systemProject->name)
            ->assertSee('System')
            ->assertDontSee('project-edit-btn');
    }

    public function test_task_list_kanban_workspace_and_project_tasks_show_non_break_tasks_and_hide_break_tasks(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $user->givePermissionTo(['project.view', 'task.view']);
        $otherUser = User::factory()->create(['is_super_admin' => false]);

        $systemProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);
        $regularProject = $this->createProject(['name' => 'Client App', 'added_by' => $user->id]);

        $assignedTask = $this->createTask($systemProject, $user, [
            'name' => 'System Assigned Task',
            'request_type' => Task::REQUEST_TYPE_ASSIGNED,
        ]);

        $selfTask = $this->createTask($systemProject, $user, [
            'name' => 'System Self Task',
            'request_type' => Task::REQUEST_TYPE_SELF,
            'request_status' => Task::REQUEST_PENDING,
        ]);

        $otherUsersTask = $this->createTask($systemProject, $otherUser, [
            'name' => 'Other User System Task',
            'request_type' => Task::REQUEST_TYPE_ASSIGNED,
        ]);

        $systemBreakTask = $this->createTask($systemProject, $user, [
            'name' => 'System Break Task',
            'request_type' => Task::REQUEST_TYPE_BREAK,
        ]);

        $regularBreakTask = $this->createTask($regularProject, $user, [
            'name' => 'Regular Break Task',
            'request_type' => Task::REQUEST_TYPE_BREAK,
        ]);

        // Task List
        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee($assignedTask->name)
            ->assertSee($selfTask->name)
            ->assertDontSee($otherUsersTask->name)
            ->assertDontSee($systemBreakTask->name)
            ->assertDontSee($regularBreakTask->name);

        // Task Kanban Board (linear flow AJAX)
        $this->actingAs($user)
            ->get(route('tasks.kanbanMode', ['flow' => 'linear']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee($assignedTask->name)
            ->assertSee($selfTask->name)
            ->assertDontSee($otherUsersTask->name)
            ->assertDontSee($systemBreakTask->name)
            ->assertDontSee($regularBreakTask->name);

        // User Workspace (linear flow)
        $this->actingAs($user)
            ->get(route('user.workspace', ['flow' => 'linear']))
            ->assertOk()
            ->assertSee($assignedTask->name)
            ->assertSee($selfTask->name)
            ->assertDontSee($otherUsersTask->name)
            ->assertDontSee($systemBreakTask->name)
            ->assertDontSee($regularBreakTask->name);

        // Project Detail Task Groups
        $this->actingAs($user)
            ->getJson(route('projects.tasks.groups.show', ['project' => $systemProject, 'group' => 'all-tasks']))
            ->assertOk()
            ->assertSee($assignedTask->name)
            ->assertSee($selfTask->name)
            ->assertDontSee($otherUsersTask->name)
            ->assertDontSee($systemBreakTask->name);

        // Break tasks are NOT deleted and remain queryable via Task::query()
        $this->assertTrue(Task::query()->whereKey($systemBreakTask->id)->exists());
        $this->assertTrue(Task::query()->whereKey($regularBreakTask->id)->exists());
    }

    public function test_system_project_cannot_be_edited_or_deleted_even_by_super_admin(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $systemProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);

        $this->actingAs($superAdmin)
            ->putJson(route('projects.update', $systemProject), [
                'name' => 'Hacked Misc',
                'project_flow' => 'linear',
                'priority' => 'medium',
                'status_id' => $this->projectStatus->id,
            ])
            ->assertForbidden()
            ->assertJson(['message' => 'System projects cannot be edited.']);

        $this->actingAs($superAdmin)
            ->patchJson(route('projects.updateProjectStatus', $systemProject), [
                'status_id' => $this->projectStatus->id,
                'change_date' => now()->format('Y-m-d'),
            ])
            ->assertForbidden()
            ->assertJson(['message' => 'System projects cannot be edited.']);

        $this->actingAs($superAdmin)
            ->patchJson(route('projects.updateProjectStage', $systemProject), [
                'project_stage_id' => null,
                'change_date' => now()->format('Y-m-d'),
            ])
            ->assertForbidden()
            ->assertJson(['message' => 'System projects cannot be edited.']);

        $this->actingAs($superAdmin)
            ->getJson(route('projects.delete-summary', $systemProject))
            ->assertForbidden()
            ->assertJson(['message' => 'System projects cannot be deleted.']);

        $this->actingAs($superAdmin)
            ->deleteJson(route('projects.destroy', $systemProject))
            ->assertForbidden()
            ->assertJson(['message' => 'System projects cannot be deleted.']);

        $this->assertDatabaseHas('projects', [
            'id' => $systemProject->id,
            'name' => 'Misc',
            'deleted_at' => null,
        ]);
    }

    public function test_regular_project_can_still_be_deleted_by_authorized_user(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $user->givePermissionTo(['project.view', 'project.delete']);
        $regularProject = $this->createProject(['name' => 'Deletable Project', 'added_by' => $user->id]);

        $this->actingAs($user)
            ->deleteJson(route('projects.destroy', $regularProject))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('projects', ['id' => $regularProject->id]);
    }
}
