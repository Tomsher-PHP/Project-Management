<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QuickTaskCreateTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectStatus $projectStatus;
    protected TaskType $defaultTaskType;
    protected TaskType $otherTaskType;
    protected TaskMode $defaultTaskMode;
    protected TaskMode $otherTaskMode;
    protected TaskStatus $linearDefaultStatus;
    protected TaskStatus $linearActiveStatus;
    protected TaskStatus $agileDefaultStatus;
    protected TaskStatus $agileActiveStatus;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'task.quick_task', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'task.create', 'guard_name' => 'web']);

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

        $this->otherTaskType = TaskType::create([
            'name' => 'Feature',
            'code' => 'feature',
            'color' => '#22C55E',
            'sort_order' => 1,
            'is_default' => false,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->defaultTaskType = TaskType::create([
            'name' => 'Normal',
            'code' => 'normal',
            'color' => '#a9aaa9',
            'sort_order' => 2,
            'is_default' => true,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->otherTaskMode = TaskMode::create([
            'name' => 'Rework',
            'code' => 'rework',
            'color' => '#EF4444',
            'sort_order' => 1,
            'is_default' => false,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->defaultTaskMode = TaskMode::create([
            'name' => 'New',
            'code' => 'new',
            'color' => '#3B82F6',
            'sort_order' => 2,
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

        $this->linearActiveStatus = TaskStatus::create([
            'flow_type' => 'linear',
            'name' => 'Processing',
            'code' => 'processing',
            'color' => '#007bff',
            'type' => 'active',
            'sort_order' => 2,
            'is_default' => false,
            'is_completed' => false,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->agileDefaultStatus = TaskStatus::create([
            'flow_type' => 'agile',
            'name' => 'To Do',
            'code' => 'to_do',
            'color' => '#6c757d',
            'type' => 'pending',
            'sort_order' => 1,
            'is_default' => true,
            'is_completed' => false,
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->agileActiveStatus = TaskStatus::create([
            'flow_type' => 'agile',
            'name' => 'In Progress',
            'code' => 'in_progress',
            'color' => '#007bff',
            'type' => 'active',
            'sort_order' => 2,
            'is_default' => false,
            'is_completed' => false,
            'is_active' => true,
            'is_system' => true,
        ]);
    }

    protected function createUserWithQuickTaskPermission(): User
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $user->givePermissionTo('task.quick_task');

        return $user;
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
            'estimated_time_seconds' => 3600,
            'default_task_estimate_seconds' => 1800,
            'default_billable' => true,
            'is_active' => true,
        ], $attributes));

        if ($isSystem) {
            $project->is_system = true;
            $project->save();
        }

        return $project->fresh();
    }

    protected function addMember(Project $project, User $user): void
    {
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_active' => true,
        ]);
    }

    public function test_workspace_shows_quick_task_button_and_default_system_project_only_with_permission(): void
    {
        $miscProject = $this->createProject([
            'name' => 'Misc',
            'is_system' => true,
            'default_task_estimate_seconds' => 0,
            'default_billable' => false,
        ]);

        $authorizedUser = $this->createUserWithQuickTaskPermission();
        $this->actingAs($authorizedUser)
            ->get(route('user.workspace'))
            ->assertOk()
            ->assertSee('data-quick-task-create-open', false)
            ->assertSee('id="quick_task_create_modal"', false)
            ->assertSee('data-default-project-id="' . $miscProject->id . '"', false);

        $unauthorizedUser = User::factory()->create(['is_super_admin' => false]);
        $this->actingAs($unauthorizedUser)
            ->get(route('user.workspace'))
            ->assertOk()
            ->assertDontSee('data-quick-task-create-open', false)
            ->assertDontSee('id="quick_task_create_modal"', false);
    }

    public function test_unauthorized_user_cannot_access_quick_task_endpoints(): void
    {
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)
            ->getJson(route('tasks.quick-task.projects'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => 'Blocked Quick Task',
                'start_timer' => false,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_quick_task_project_search_is_lazy_and_preserves_standard_search_exclusion(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);
        $memberProject = $this->createProject(['name' => 'Client Portal']);
        $otherProject = $this->createProject(['name' => 'Hidden Project']);
        $this->addMember($memberProject, $user);

        $quickResults = $this->actingAs($user)
            ->getJson(route('tasks.quick-task.projects', ['q' => '']))
            ->assertOk()
            ->json();

        $quickIds = collect($quickResults)->pluck('id')->all();
        $this->assertContains($miscProject->id, $quickIds);
        $this->assertContains($memberProject->id, $quickIds);
        $this->assertNotContains($otherProject->id, $quickIds);

        // Existing /projects/search must still exclude system projects
        $standardResults = $this->actingAs($user)
            ->getJson(route('projects.search', ['q' => 'Misc']))
            ->assertOk()
            ->json();

        $this->assertNotContains($miscProject->id, collect($standardResults)->pluck('id')->all());
    }

    public function test_user_without_task_create_can_create_quick_task_in_system_project_with_backend_defaults(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $miscProject = $this->createProject([
            'name' => 'Misc',
            'is_system' => true,
            'project_flow' => 'linear',
            'default_task_estimate_seconds' => 7200,
            'default_billable' => true,
        ]);

        $timeline = ProjectTimeline::create([
            'project_id' => $miscProject->id,
            'name' => 'Default Timeline',
            'status' => ProjectTimeline::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => 'Quick Ad-hoc Task',
                'start_timer' => false,
                // Attempted overrides must be ignored
                'estimated_time_minutes' => 120,
                'is_billable' => true,
                'priority' => 'urgent',
                'due_date_time' => '2030-01-01 12:00:00',
                'request_status' => 'pending',
                'current_assignee_id' => 999999,
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Task created successfully.',
                'timer_started' => false,
            ]);

        $task = Task::query()->findOrFail($response->json('task_id'));

        $this->assertSame($miscProject->id, $task->project_id);
        $this->assertSame('Quick Ad-hoc Task', $task->name);
        $this->assertSame($user->id, $task->added_by);
        $this->assertSame($user->id, $task->current_assignee_id);
        $this->assertSame(0, $task->estimated_time_seconds);
        $this->assertNull($task->due_date_time);
        $this->assertSame($this->defaultTaskType->id, (int) $task->task_type_id);
        $this->assertSame($this->defaultTaskMode->id, (int) $task->task_mode_id);
        $this->assertSame('medium', $task->priority);
        $this->assertFalse($task->is_billable);
        $this->assertSame(Task::REQUEST_TYPE_ASSIGNED, $task->request_type);
        $this->assertSame(Task::REQUEST_APPROVED, $task->request_status);
        $this->assertSame($user->id, $task->approved_by);
        $this->assertNotNull($task->approved_at);
        $this->assertSame($this->linearDefaultStatus->id, $task->status_id);
        $this->assertSame($timeline->id, $task->project_timeline_id);
        $this->assertNull($task->project_milestone_id);
        $this->assertNull($task->project_sprint_id);

        // No project membership record inserted for system project
        $this->assertDatabaseMissing('project_members', [
            'project_id' => $miscProject->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_inaccessible_or_non_member_regular_project_is_rejected(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $regularProject = $this->createProject(['name' => 'Restricted Project']);

        $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $regularProject->id,
                'name' => 'Should Fail',
                'start_timer' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
    }

    public function test_empty_task_name_returns_validation_error(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);

        $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => '   ',
                'start_timer' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_agile_project_uses_unplanned_work_milestone_and_backlog_sprint(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $agileProject = $this->createProject([
            'name' => 'Agile App',
            'project_flow' => 'agile',
        ]);
        $this->addMember($agileProject, $user);

        $response = $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $agileProject->id,
                'name' => 'Agile Quick Task',
                'start_timer' => false,
            ])
            ->assertOk();

        $task = Task::query()->with(['projectMilestone', 'projectSprint'])->findOrFail($response->json('task_id'));

        $this->assertSame($this->agileDefaultStatus->id, $task->status_id);
        $this->assertNotNull($task->project_milestone_id);
        $this->assertNotNull($task->project_sprint_id);
        $this->assertTrue((bool) $task->projectMilestone->is_backlog);
        $this->assertTrue((bool) $task->projectSprint->is_backlog);
    }

    public function test_save_leaves_existing_running_timer_unaffected(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);
        $existingTask = Task::create([
            'project_id' => $miscProject->id,
            'name' => 'Existing Running Task',
            'status_id' => $this->linearActiveStatus->id,
            'current_assignee_id' => $user->id,
            'request_status' => 'approved',
        ]);
        $existingAssignment = TaskAssignmentLog::create([
            'task_id' => $existingTask->id,
            'user_id' => $user->id,
            'assigned_from' => now()->subMinutes(10),
            'is_current' => true,
        ]);
        $runningLog = TaskTimeLog::create([
            'task_id' => $existingTask->id,
            'user_id' => $user->id,
            'task_assignment_log_id' => $existingAssignment->id,
            'started_at' => now()->subMinutes(10),
            'is_running' => true,
            'is_approved' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => 'Saved Only Task',
                'start_timer' => false,
            ])
            ->assertOk();

        $this->assertTrue((bool) $runningLog->fresh()->is_running);
        $this->assertSame(1, TaskTimeLog::where('user_id', $user->id)->where('is_running', true)->count());
    }

    public function test_save_and_start_without_running_timer_starts_new_timer(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);

        $response = $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => 'Immediate Start Task',
                'start_timer' => true,
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Task created and timer started successfully.',
                'timer_started' => true,
            ]);

        $task = Task::query()->findOrFail($response->json('task_id'));
        $this->assertSame($this->linearActiveStatus->id, $task->status_id);

        $runningLog = TaskTimeLog::where('task_id', $task->id)->where('user_id', $user->id)->first();
        $this->assertNotNull($runningLog);
        $this->assertTrue((bool) $runningLog->is_running);
        $this->assertTrue((bool) $runningLog->is_approved);
    }

    public function test_save_and_start_with_running_timer_switches_timer_and_leaves_other_users_unaffected(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $otherUser = User::factory()->create();
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);

        $existingTask = Task::create([
            'project_id' => $miscProject->id,
            'name' => 'Previous Task',
            'status_id' => $this->linearActiveStatus->id,
            'current_assignee_id' => $user->id,
            'request_status' => 'approved',
        ]);
        $existingAssignment = TaskAssignmentLog::create([
            'task_id' => $existingTask->id,
            'user_id' => $user->id,
            'assigned_from' => now()->subMinutes(15),
            'is_current' => true,
        ]);
        $previousLog = TaskTimeLog::create([
            'task_id' => $existingTask->id,
            'user_id' => $user->id,
            'task_assignment_log_id' => $existingAssignment->id,
            'started_at' => now()->subMinutes(15),
            'is_running' => true,
            'is_approved' => true,
        ]);

        $otherTask = Task::create([
            'project_id' => $miscProject->id,
            'name' => 'Other User Task',
            'status_id' => $this->linearActiveStatus->id,
            'current_assignee_id' => $otherUser->id,
            'request_status' => 'approved',
        ]);
        $otherAssignment = TaskAssignmentLog::create([
            'task_id' => $otherTask->id,
            'user_id' => $otherUser->id,
            'assigned_from' => now()->subMinutes(5),
            'is_current' => true,
        ]);
        $otherLog = TaskTimeLog::create([
            'task_id' => $otherTask->id,
            'user_id' => $otherUser->id,
            'task_assignment_log_id' => $otherAssignment->id,
            'started_at' => now()->subMinutes(5),
            'is_running' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => 'Switched Quick Task',
                'start_timer' => true,
            ])
            ->assertOk();

        $newTaskId = $response->json('task_id');

        $previousLog->refresh();
        $this->assertFalse((bool) $previousLog->is_running);
        $this->assertNotNull($previousLog->ended_at);
        $this->assertGreaterThan(0, $previousLog->duration_seconds);

        $this->assertSame(1, TaskTimeLog::where('user_id', $user->id)->where('is_running', true)->count());
        $this->assertTrue(
            TaskTimeLog::where('task_id', $newTaskId)->where('user_id', $user->id)->where('is_running', true)->exists()
        );

        // Other user's timer remains running
        $this->assertTrue((bool) $otherLog->fresh()->is_running);
    }

    public function test_missing_default_task_type_or_mode_returns_error_and_preserves_running_timer(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true]);

        $existingTask = Task::create([
            'project_id' => $miscProject->id,
            'name' => 'Previous Task',
            'status_id' => $this->linearActiveStatus->id,
            'current_assignee_id' => $user->id,
            'request_status' => 'approved',
        ]);
        $existingAssignment = TaskAssignmentLog::create([
            'task_id' => $existingTask->id,
            'user_id' => $user->id,
            'assigned_from' => now()->subMinutes(5),
            'is_current' => true,
        ]);
        $previousLog = TaskTimeLog::create([
            'task_id' => $existingTask->id,
            'user_id' => $user->id,
            'task_assignment_log_id' => $existingAssignment->id,
            'started_at' => now()->subMinutes(5),
            'is_running' => true,
            'is_approved' => true,
        ]);

        $this->defaultTaskType->update(['is_default' => false]);

        $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => 'Should Fail Config',
                'start_timer' => true,
            ])
            ->assertUnprocessable()
            ->assertJson([
                'status' => false,
                'message' => 'Default Task Type is not configured. Please contact an administrator.',
            ]);

        $this->assertDatabaseMissing('tasks', ['name' => 'Should Fail Config']);
        $this->assertTrue((bool) $previousLog->fresh()->is_running);
    }

    public function test_timer_failure_rolls_back_task_creation_and_preserves_previous_timer(): void
    {
        $user = $this->createUserWithQuickTaskPermission();
        $miscProject = $this->createProject(['name' => 'Misc', 'is_system' => true, 'project_flow' => 'linear']);

        // Remove active linear status so starting timer fails
        $this->linearActiveStatus->update(['is_active' => false]);

        $this->actingAs($user)
            ->postJson(route('tasks.quick-task.store'), [
                'project_id' => $miscProject->id,
                'name' => 'Should Rollback',
                'start_timer' => true,
            ])
            ->assertUnprocessable()
            ->assertJson(['status' => false]);

        $this->assertDatabaseMissing('tasks', ['name' => 'Should Rollback']);
    }
}
