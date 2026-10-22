<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\Task\RunningTaskNavbarService;
use App\Services\TaskServices;
use App\View\Components\RunningTaskBar;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskNavbarTimerWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Project $project;
    protected TaskStatus $activeStatus;
    protected TaskStatus $completedStatus;
    protected TaskStatus $pendingStatus;
    protected TaskServices $taskServices;
    protected RunningTaskNavbarService $navbarService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskServices = app(TaskServices::class);
        $this->navbarService = app(RunningTaskNavbarService::class);

        $this->user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->project = Project::factory()->create([
            'project_flow' => 'agile',
        ]);

        $this->activeStatus = TaskStatus::firstOrCreate(
            ['type' => 'active', 'flow_type' => 'agile'],
            [
                'name' => 'In Progress',
                'code' => 'in_progress',
                'sort_order' => 1,
                'is_active' => true,
                'is_completed' => false,
            ]
        );

        $this->completedStatus = TaskStatus::firstOrCreate(
            ['type' => 'completed', 'flow_type' => 'agile'],
            [
                'name' => 'Done',
                'code' => 'done',
                'sort_order' => 2,
                'is_active' => true,
                'is_completed' => true,
            ]
        );

        $this->pendingStatus = TaskStatus::firstOrCreate(
            ['type' => 'pending', 'flow_type' => 'agile'],
            [
                'name' => 'To Do',
                'code' => 'to_do',
                'sort_order' => 0,
                'is_active' => true,
                'is_completed' => false,
            ]
        );
    }

    protected function createTask(string $name, ?TaskStatus $status = null): Task
    {
        $status ??= $this->activeStatus;

        $task = Task::create([
            'project_id' => $this->project->id,
            'name' => $name,
            'code' => strtoupper(substr(md5($name), 0, 6)),
            'status_id' => $status->id,
            'current_assignee_id' => $this->user->id,
            'request_status' => 'approved',
        ]);

        TaskAssignmentLog::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->user->id,
            'is_current' => 1,
            'assigned_at' => now(),
        ]);

        return $task;
    }

    public function test_starting_active_task_makes_it_the_running_navbar_task(): void
    {
        $task = $this->createTask('Task Alpha');

        $response = $this->actingAs($this->user)->postJson(route('tasks.start', $task));
        $response->assertOk();
        $response->assertJson([
            'navbar_timer' => [
                'active' => true,
                'state' => 'running',
                'taskId' => (string) $task->id,
                'taskName' => 'Task Alpha',
                'isRunning' => true,
                'shouldShowTimer' => true,
            ],
        ]);

        $runningLog = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->first();

        $this->assertNotNull($runningLog);
        $this->assertNull($runningLog->ended_at);

        $navbarState = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertTrue($navbarState['active']);
        $this->assertSame('running', $navbarState['state']);
        $this->assertSame((string) $task->id, $navbarState['taskId']);
    }

    public function test_stopping_active_task_leaves_same_task_visible_as_paused(): void
    {
        $task = $this->createTask('Task Alpha');

        $this->taskServices->startTimer($task, $this->user->id);

        $response = $this->actingAs($this->user)->postJson(route('tasks.stop', $task));
        $response->assertOk();
        $response->assertJson([
            'navbar_timer' => [
                'active' => true,
                'state' => 'stopped',
                'taskId' => (string) $task->id,
                'taskName' => 'Task Alpha',
                'isRunning' => false,
                'shouldShowTimer' => true,
            ],
        ]);

        $stoppedLog = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('user_id', $this->user->id)
            ->where('is_running', 0)
            ->first();

        $this->assertNotNull($stoppedLog);
        $this->assertNotNull($stoppedLog->ended_at);

        $navbarState = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertTrue($navbarState['active']);
        $this->assertSame('stopped', $navbarState['state']);
        $this->assertSame((string) $task->id, $navbarState['taskId']);
    }

    public function test_completing_latest_paused_task_returns_empty_navbar_state(): void
    {
        $task = $this->createTask('Task Bravo');

        $log = $this->taskServices->startTimer($task, $this->user->id);
        $this->taskServices->stopTimer($task, $this->user);

        // Transition task Bravo to completed status
        $response = $this->actingAs($this->user)->patchJson(route('tasks.transition-status'), [
            'status_id' => $this->completedStatus->id,
            'moved_task_id' => $task->id,
            'task_ids' => [$task->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status_type' => 'completed',
            'navbar_timer' => [
                'active' => false,
                'shouldShowTimer' => false,
                'taskId' => '',
            ],
        ]);

        $navbarState = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertFalse($navbarState['active']);
        $this->assertFalse($navbarState['shouldShowTimer']);
        $this->assertSame('', $navbarState['taskId']);
    }

    public function test_completing_running_task_stops_timer_correctly_and_returns_empty_navbar_state(): void
    {
        $task = $this->createTask('Task Charlie');

        $log = $this->taskServices->startTimer($task, $this->user->id);
        $log->update(['started_at' => now()->subMinutes(15)]);

        // Transition running task Charlie directly to completed
        $response = $this->actingAs($this->user)->patchJson(route('tasks.transition-status'), [
            'status_id' => $this->completedStatus->id,
            'moved_task_id' => $task->id,
            'task_ids' => [$task->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status_type' => 'completed',
            'navbar_timer' => [
                'active' => false,
                'shouldShowTimer' => false,
                'taskId' => '',
            ],
        ]);

        // Timer must be stopped and duration must be recorded
        $log->refresh();
        $this->assertFalse((bool) $log->is_running);
        $this->assertNotNull($log->ended_at);
        $this->assertGreaterThanOrEqual(900, $log->duration_seconds);

        $navbarState = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertFalse($navbarState['active']);
        $this->assertFalse($navbarState['shouldShowTimer']);
        $this->assertSame('', $navbarState['taskId']);
    }

    public function test_completing_task_b_does_not_fall_back_to_older_stopped_task_c(): void
    {
        // 1. Task C was stopped earlier (e.g. yesterday)
        $taskC = $this->createTask('Task Charlie (Older)');
        $logC = $this->taskServices->startTimer($taskC, $this->user->id);
        $logC->update(['started_at' => now()->subDays(1)->subHours(2)]);
        $this->taskServices->recordTimeLogStop($logC, $taskC, $this->user, now()->subDays(1)->subHours(1));

        // 2. Task B is started today, then stopped
        $taskB = $this->createTask('Task Bravo (Recent)');
        $logB = $this->taskServices->startTimer($taskB, $this->user->id);
        $logB->update(['started_at' => now()->subHours(1)]);
        $this->taskServices->recordTimeLogStop($logB, $taskB, $this->user, now());

        // Navbar currently displays Task B as paused
        $stateBefore = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertTrue($stateBefore['active']);
        $this->assertSame((string) $taskB->id, $stateBefore['taskId']);
        $this->assertSame('stopped', $stateBefore['state']);

        // 3. User transitions Task B to Completed
        $response = $this->actingAs($this->user)->patchJson(route('tasks.transition-status'), [
            'status_id' => $this->completedStatus->id,
            'moved_task_id' => $taskB->id,
            'task_ids' => [$taskB->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'navbar_timer' => [
                'active' => false,
                'shouldShowTimer' => false,
                'taskId' => '',
            ],
        ]);

        // The navbar must be completely EMPTY, NOT falling back to Task C!
        $stateAfter = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertFalse($stateAfter['active']);
        $this->assertFalse($stateAfter['shouldShowTimer']);
        $this->assertSame('', $stateAfter['taskId']);
        $this->assertNotEquals((string) $taskC->id, $stateAfter['taskId']);
    }

    public function test_moving_displayed_task_to_pending_status_removes_it_from_navbar(): void
    {
        $task = $this->createTask('Task Delta');

        $this->taskServices->startTimer($task, $this->user->id);
        $this->taskServices->stopTimer($task, $this->user);

        // Move to 'To Do' (pending)
        $response = $this->actingAs($this->user)->patchJson(route('tasks.transition-status'), [
            'status_id' => $this->pendingStatus->id,
            'moved_task_id' => $task->id,
            'task_ids' => [$task->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'navbar_timer' => [
                'active' => false,
                'shouldShowTimer' => false,
                'taskId' => '',
            ],
        ]);

        $navbarState = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertFalse($navbarState['active']);
        $this->assertSame('', $navbarState['taskId']);

        // Attempting to resume/start timer on task in pending status must be rejected
        $startResponse = $this->actingAs($this->user)->postJson(route('tasks.start', $task));
        $startResponse->assertStatus(422);
        $startResponse->assertJson([
            'message' => 'Move this task to an active status before starting the timer.',
        ]);
    }

    public function test_subsequent_explicit_start_of_task_c_displays_task_c_as_running(): void
    {
        // Setup: Task B is completed, navbar is empty, Task C is in progress
        $taskC = $this->createTask('Task Charlie');
        $taskB = $this->createTask('Task Bravo');
        $this->taskServices->startTimer($taskB, $this->user->id);
        $this->taskServices->stopTimer($taskB, $this->user);
        $taskB->update(['status_id' => $this->completedStatus->id]);

        $stateBefore = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertFalse($stateBefore['active']);
        $this->assertSame('', $stateBefore['taskId']);

        // Explicit start of Task C
        $response = $this->actingAs($this->user)->postJson(route('tasks.start', $taskC));
        $response->assertOk();
        $response->assertJson([
            'navbar_timer' => [
                'active' => true,
                'state' => 'running',
                'taskId' => (string) $taskC->id,
                'taskName' => 'Task Charlie',
            ],
        ]);

        $stateAfter = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertTrue($stateAfter['active']);
        $this->assertSame('running', $stateAfter['state']);
        $this->assertSame((string) $taskC->id, $stateAfter['taskId']);
    }

    public function test_navbar_remains_empty_after_page_reload_when_latest_task_is_ineligible_even_with_older_active_tasks(): void
    {
        // Older active task log
        $taskOlder = $this->createTask('Task Older Active');
        $logOlder = $this->taskServices->startTimer($taskOlder, $this->user->id);
        $logOlder->update(['started_at' => now()->subDays(2)]);
        $this->taskServices->recordTimeLogStop($logOlder, $taskOlder, $this->user, now()->subDays(2)->addHour());

        // Latest task completed
        $taskLatest = $this->createTask('Task Latest Completed');
        $logLatest = $this->taskServices->startTimer($taskLatest, $this->user->id);
        $this->taskServices->stopTimer($taskLatest, $this->user);
        $taskLatest->update(['status_id' => $this->completedStatus->id]);

        // Simulate page reload via component instantiation and service invocation
        $bladeComponent = new RunningTaskBar($this->navbarService);
        $state = $this->navbarService->getForUser($this->user->id);

        $this->assertFalse($state['shouldShowTimer']);
        $this->assertFalse($state['isRunning']);
        $this->assertNull($state['runningTask']);
        $this->assertSame(0, $state['currentSeconds']);

        $frontendState = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertFalse($frontendState['active']);
        $this->assertSame('', $frontendState['taskId']);
    }

    public function test_task_settings_update_returns_authoritative_navbar_state(): void
    {
        $task = $this->createTask('Task Echo');
        $log = $this->taskServices->startTimer($task, $this->user->id);

        $taskType = \App\Models\TaskType::firstOrCreate(
            ['code' => 'feature'],
            ['name' => 'Feature', 'is_active' => true, 'sort_order' => 1]
        );
        $taskMode = \App\Models\TaskMode::firstOrCreate(
            ['code' => 'new'],
            ['name' => 'New', 'is_active' => true, 'sort_order' => 1]
        );

        // Update task settings to change status to completed
        $response = $this->actingAs($this->user)->putJson(
            route('projects.tasks.update', [$this->project, $task]),
            [
                'name' => 'Task Echo Updated',
                'status_id' => $this->completedStatus->id,
                'task_type_id' => $taskType->id,
                'task_mode_id' => $taskMode->id,
                'priority' => 'medium',
                'due_date_time' => now()->addDays(5)->toDateTimeString(),
            ]
        );

        $response->assertOk();
        $response->assertJson([
            'status' => true,
            'navbar_timer' => [
                'active' => false,
                'shouldShowTimer' => false,
                'taskId' => '',
            ],
        ]);

        $log->refresh();
        $this->assertFalse((bool) $log->is_running);
        $this->assertNotNull($log->ended_at);

        $navbarState = $this->navbarService->getFrontendStateForUser($this->user->id);
        $this->assertFalse($navbarState['active']);
        $this->assertSame('', $navbarState['taskId']);
    }

    public function test_starting_or_resuming_non_active_task_is_rejected_by_backend(): void
    {
        $completedTask = $this->createTask('Completed Task', $this->completedStatus);
        $pendingTask = $this->createTask('Pending Task', $this->pendingStatus);

        // Try start completed
        $response1 = $this->actingAs($this->user)->postJson(route('tasks.start', $completedTask));
        $response1->assertStatus(422);
        $response1->assertJson([
            'message' => 'Move this task to an active status before starting the timer.',
        ]);

        // Try start pending
        $response2 = $this->actingAs($this->user)->postJson(route('tasks.start', $pendingTask));
        $response2->assertStatus(422);
        $response2->assertJson([
            'message' => 'Move this task to an active status before starting the timer.',
        ]);

        // Try switch to completed
        $response3 = $this->actingAs($this->user)->postJson(route('tasks.switch', $completedTask));
        $response3->assertStatus(422);
        $response3->assertJson([
            'message' => 'Move this task to an active status before starting the timer.',
        ]);

        // Try switch to pending
        $response4 = $this->actingAs($this->user)->postJson(route('tasks.switch', $pendingTask));
        $response4->assertStatus(422);
        $response4->assertJson([
            'message' => 'Move this task to an active status before starting the timer.',
        ]);
    }
}
