<?php

namespace Tests\Feature;

use App\Exceptions\TaskTimerAlreadyRunningException;
use App\Exceptions\TaskTimerStateConflictException;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\TaskServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskTimerSingleRunningEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TaskStatus $activeStatus;
    protected Project $project;
    protected Task $task1;
    protected Task $task2;
    protected TaskServices $taskServices;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskServices = app(TaskServices::class);
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();

        $this->activeStatus = TaskStatus::firstOrCreate(
            ['type' => 'active'],
            ['name' => 'In Progress', 'code' => 'in_progress', 'order' => 1]
        );

        $this->task1 = Task::create([
            'project_id' => $this->project->id,
            'name' => 'Task Alpha',
            'code' => 'ALPHA-1',
            'status_id' => $this->activeStatus->id,
            'current_assignee_id' => $this->user->id,
            'request_status' => 'approved',
        ]);

        TaskAssignmentLog::create([
            'task_id' => $this->task1->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->user->id,
            'is_current' => 1,
            'assigned_at' => now(),
        ]);

        $this->task2 = Task::create([
            'project_id' => $this->project->id,
            'name' => 'Task Beta',
            'code' => 'BETA-2',
            'status_id' => $this->activeStatus->id,
            'current_assignee_id' => $this->user->id,
            'request_status' => 'approved',
        ]);

        TaskAssignmentLog::create([
            'task_id' => $this->task2->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->user->id,
            'is_current' => 1,
            'assigned_at' => now(),
        ]);
    }

    public function test_user_cannot_start_two_running_timers_on_different_tasks(): void
    {
        // 1. Start timer on task 1
        $log1 = $this->taskServices->startTimer($this->task1, $this->user->id);
        $this->assertNotNull($log1);
        $this->assertTrue((bool) $log1->is_running);

        // 2. Attempt to start timer on task 2 for the same user
        $exceptionThrown = false;
        try {
            $this->taskServices->startTimer($this->task2, $this->user->id);
        } catch (TaskTimerAlreadyRunningException $e) {
            $exceptionThrown = true;
            $restriction = $e->getRestriction();
            $this->assertSame('running_timer_exists', $restriction['reason'] ?? null);
            $this->assertSame($this->task1->id, $restriction['running_task_id'] ?? null);
            $this->assertStringContainsString('Task Alpha', $restriction['message'] ?? '');
        }

        $this->assertTrue($exceptionThrown, 'Expected TaskTimerAlreadyRunningException was not thrown');

        // Exactly one timer must be running for this user
        $runningCount = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->count();

        $this->assertSame(1, $runningCount);
    }

    public function test_user_cannot_start_duplicate_timer_on_same_task(): void
    {
        $log1 = $this->taskServices->startTimer($this->task1, $this->user->id);
        $this->assertTrue((bool) $log1->is_running);

        $exceptionThrown = false;
        try {
            $this->taskServices->startTimer($this->task1, $this->user->id);
        } catch (TaskTimerAlreadyRunningException $e) {
            $exceptionThrown = true;
            $restriction = $e->getRestriction();
            $this->assertSame('already_running', $restriction['reason'] ?? null);
        }

        $this->assertTrue($exceptionThrown, 'Expected TaskTimerAlreadyRunningException was not thrown');

        $runningCount = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('task_id', $this->task1->id)
            ->where('is_running', 1)
            ->count();

        $this->assertSame(1, $runningCount);
    }

    public function test_stopping_first_task_allows_starting_second_task(): void
    {
        $this->taskServices->startTimer($this->task1, $this->user->id);
        $this->taskServices->stopTimer($this->task1, $this->user);

        $stoppedCount = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('task_id', $this->task1->id)
            ->where('is_running', 0)
            ->count();
        $this->assertSame(1, $stoppedCount);

        // Now starting task 2 should succeed
        $log2 = $this->taskServices->startTimer($this->task2, $this->user->id);
        $this->assertTrue((bool) $log2->is_running);

        $runningLogs = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->get();

        $this->assertCount(1, $runningLogs);
        $this->assertSame($this->task2->id, $runningLogs->first()->task_id);
    }

    public function test_http_endpoint_returns_task_switch_confirmation_when_running_timer_exists(): void
    {
        $this->taskServices->startTimer($this->task1, $this->user->id);

        $response = $this->actingAs($this->user)->postJson(route('tasks.start', $this->task2));

        $response->assertStatus(422);
        $response->assertJson([
            'requires_confirmation' => true,
            'running_task_id' => $this->task1->id,
        ]);

        $runningCount = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->count();

        $this->assertSame(1, $runningCount);
    }

    public function test_transactional_user_lock_prevents_race_condition_when_starting_timer(): void
    {
        // Simulate two concurrent requests arriving:
        // Request 1 passes initial pre-check before either transaction writes anything
        $restriction1 = $this->taskServices->getStartRestriction($this->task1, $this->user);
        $this->assertNull($restriction1);

        // Request 2 for a different task also passes initial pre-check before Request 1 commits
        $restriction2 = $this->taskServices->getStartRestriction($this->task2, $this->user);
        $this->assertNull($restriction2);

        // Request 1 enters startTimer and commits
        $log1 = $this->taskServices->startTimer($this->task1, $this->user->id);
        $this->assertNotNull($log1);

        // Request 2 now enters startTimer (which would previously succeed because it only checked task_id)
        // With our fix, startTimer locks user and inspects running timers across all tasks
        $this->expectException(TaskTimerAlreadyRunningException::class);
        $this->taskServices->startTimer($this->task2, $this->user->id);
    }

    public function test_atomic_switch_successfully_switches_from_task_a_to_task_b(): void
    {
        // Start task 1 and set its start time to 10 minutes ago
        $log1 = $this->taskServices->startTimer($this->task1, $this->user->id);
        $log1->update(['started_at' => now()->subMinutes(10)]);

        $result = $this->taskServices->switchTimer(
            $this->task2,
            $this->user,
            expectedCurrentTaskId: $this->task1->id
        );

        $this->assertSame('switched', $result['status']);
        $this->assertSame('Timer switched successfully.', $result['message']);
        $this->assertNotNull($result['stopped_time_log']);
        $this->assertNotNull($result['running_time_log']);

        // Task 1's timer must be stopped with recorded duration
        $log1->refresh();
        $this->assertFalse((bool) $log1->is_running);
        $this->assertNotNull($log1->ended_at);
        $this->assertGreaterThanOrEqual(600, $log1->duration_seconds);

        // Task 1 task actual_time_seconds must be incremented
        $this->task1->refresh();
        $this->assertGreaterThanOrEqual(600, $this->task1->actual_time_seconds);

        // Task 1 assignment log worked_time_seconds must be incremented
        $assignment1 = TaskAssignmentLog::where('task_id', $this->task1->id)
            ->where('user_id', $this->user->id)
            ->first();
        $this->assertGreaterThanOrEqual(600, $assignment1->worked_time_seconds);

        // Task 2's timer must be running
        $log2 = $result['running_time_log'];
        $this->assertTrue((bool) $log2->is_running);
        $this->assertSame($this->task2->id, $log2->task_id);

        // Exactly one running timer for this user in total
        $runningCount = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->count();
        $this->assertSame(1, $runningCount);
    }

    public function test_atomic_switch_rolls_back_when_starting_task_b_fails(): void
    {
        // Start task 1
        $log1 = $this->taskServices->startTimer($this->task1, $this->user->id);
        $initialStartedAt = $log1->started_at;

        // Create Task 3 with NO assignment log, so createRunningTimeLog will fail
        $task3 = Task::create([
            'project_id' => $this->project->id,
            'name' => 'Task Gamma (No Assignment)',
            'code' => 'GAMMA-3',
            'status_id' => $this->activeStatus->id,
            'current_assignee_id' => $this->user->id,
            'request_status' => 'approved',
        ]);
        // Note: No TaskAssignmentLog created for task3

        $exceptionThrown = false;
        try {
            $this->taskServices->switchTimer(
                $task3,
                $this->user,
                expectedCurrentTaskId: $this->task1->id
            );
        } catch (\RuntimeException $e) {
            $exceptionThrown = true;
            $this->assertSame('Assignment not found', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown);

        // Task 1 timer must STILL be running because transaction rolled back!
        $log1->refresh();
        $this->assertTrue((bool) $log1->is_running);
        $this->assertNull($log1->ended_at);
        $this->assertSame(0, (int) $log1->duration_seconds);

        // Exactly 1 timer running (Task 1)
        $runningLogs = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->get();
        $this->assertCount(1, $runningLogs);
        $this->assertSame($this->task1->id, $runningLogs->first()->task_id);
    }

    public function test_atomic_switch_rejects_stale_confirmation_when_running_task_changed(): void
    {
        // Start task 1
        $this->taskServices->startTimer($this->task1, $this->user->id);

        // Request passes a stale expected running task ID (e.g. 9999)
        $this->expectException(TaskTimerStateConflictException::class);
        $this->taskServices->switchTimer(
            $this->task2,
            $this->user,
            expectedCurrentTaskId: 9999
        );
    }

    public function test_atomic_switch_when_no_timer_is_running(): void
    {
        // User has no timer running; switch with null expected task starts the task directly
        $result = $this->taskServices->switchTimer(
            $this->task1,
            $this->user,
            expectedCurrentTaskId: null
        );

        $this->assertSame('started', $result['status']);
        $this->assertTrue((bool) $result['running_time_log']->is_running);
        $this->assertNull($result['stopped_time_log']);

        $runningCount = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->count();
        $this->assertSame(1, $runningCount);
    }

    public function test_atomic_switch_when_requested_task_is_already_running(): void
    {
        $log1 = $this->taskServices->startTimer($this->task1, $this->user->id);
        $initialStartedAt = $log1->started_at;

        // Switching to the task that is already running
        $result = $this->taskServices->switchTimer(
            $this->task1,
            $this->user,
            expectedCurrentTaskId: $this->task1->id
        );

        $this->assertSame('already_running', $result['status']);
        $this->assertNull($result['stopped_time_log']);

        $log1->refresh();
        $this->assertTrue((bool) $log1->is_running);
        $this->assertSame($initialStartedAt->toDateTimeString(), $log1->started_at->toDateTimeString());

        // No duplicate time log created
        $count = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('task_id', $this->task1->id)
            ->count();
        $this->assertSame(1, $count);
    }

    public function test_http_switch_endpoint_switches_timers_atomically(): void
    {
        $log1 = $this->taskServices->startTimer($this->task1, $this->user->id);
        $log1->update(['started_at' => now()->subMinutes(5)]);

        $response = $this->actingAs($this->user)->postJson(route('tasks.switch', $this->task2), [
            'expected_running_task_id' => $this->task1->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'switched',
            'stopped_task' => [
                'task_id' => $this->task1->id,
            ],
            'started_task' => [
                'task_id' => $this->task2->id,
            ],
        ]);

        // Verify database state: task 1 stopped, task 2 running
        $this->assertDatabaseHas('task_time_logs', [
            'task_id' => $this->task1->id,
            'user_id' => $this->user->id,
            'is_running' => 0,
        ]);

        $this->assertDatabaseHas('task_time_logs', [
            'task_id' => $this->task2->id,
            'user_id' => $this->user->id,
            'is_running' => 1,
        ]);

        $runningCount = TaskTimeLog::query()
            ->where('user_id', $this->user->id)
            ->where('is_running', 1)
            ->count();
        $this->assertSame(1, $runningCount);
    }

    public function test_http_switch_endpoint_rejects_stale_confirmation(): void
    {
        $this->taskServices->startTimer($this->task1, $this->user->id);

        $response = $this->actingAs($this->user)->postJson(route('tasks.switch', $this->task2), [
            'expected_running_task_id' => 8888,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'conflict' => true,
            'current_running_task_id' => $this->task1->id,
        ]);

        // Task 1 must remain running
        $log1 = TaskTimeLog::where('task_id', $this->task1->id)->where('user_id', $this->user->id)->first();
        $this->assertTrue((bool) $log1->is_running);
    }
}
