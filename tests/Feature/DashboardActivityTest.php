<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\Project;
use App\Services\DashboardServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test dashboard view contains new sections for authenticated user
     */
    public function test_dashboard_contains_worked_time_and_running_tasks_sections()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Users Task Worked Time');
        $response->assertSee('Running Tasks');
    }

    /**
     * Test dashboard worked time JSON endpoint
     */
    public function test_dashboard_worked_time_endpoint_returns_json()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');

        $response = $this->actingAs($user)->get('/dashboard/worked-time?date=' . today()->toDateString());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'user_id',
                    'user_name',
                    'date',
                    'total_worked_time',
                    'shift_working_hour'
                ]
            ]
        ]);
    }

    /**
     * Test dashboard shows running tasks with estimated time and color class
     */
    public function test_dashboard_running_tasks_correctly_aggregates_worked_time()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');

        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Running Task Test',
            'code' => 'TASK-RUNNING-1',
            'estimated_time_seconds' => 3600
        ]);

        // Completed approved log (1200 seconds)
        TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => now()->subMinutes(10),
            'duration_seconds' => 1200,
            'is_running' => false,
            'is_approved' => true
        ]);

        // Running log (600 seconds)
        TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(10),
            'is_running' => true
        ]);

        $service = app(DashboardServices::class);
        $runningTasks = $service->getRunningTasks($user);

        $this->assertCount(1, $runningTasks);
        $this->assertEquals('Running Task Test', $runningTasks[0]['task_name']);
        
        // 1200 actual + 600 running = 1800 seconds -> 30m 00s
        $this->assertEquals('30m 00s', $runningTasks[0]['worked_time']);
        $this->assertEquals('1h 00m', $runningTasks[0]['estimated_time']);
        $this->assertStringContainsString('text-success-300', $runningTasks[0]['color_class']);
    }

    /**
     * Test worked time with shift working hour calculation
     */
    public function test_dashboard_worked_time_calculates_shift_working_hour()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Task Test',
            'code' => 'TASK-TEST-1',
            'estimated_time_seconds' => 3600
        ]);

        // Completed approved log (7200 seconds)
        TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => now()->subHours(5),
            'ended_at' => now()->subHours(3),
            'duration_seconds' => 7200,
            'is_running' => false,
            'is_approved' => true
        ]);

        // Create shift: 9:00 AM to 6:00 PM (9 hours), break duration 1 hour (3600 seconds)
        // Shift Working Hour = 8 hours (08h 00m)
        \App\Models\UserShiftAssignment::create([
            'user_id' => $user->id,
            'shift_name' => 'General Shift',
            'time_from' => '09:00:00',
            'time_to' => '18:00:00',
            'break_duration' => 3600,
            'date_from' => today()->subDays(1),
            'date_to' => today()->addDays(5),
        ]);

        $service = app(DashboardServices::class);
        $workedTime = $service->getUsersTaskWorkedTime($user, today()->toDateString());

        $this->assertCount(1, $workedTime);
        $this->assertEquals('General Shift', $user->activeShift->shift_name ?? null);
        $this->assertEquals('8h 00m', $workedTime[0]['shift_working_hour']);
    }

    /**
     * Test timezone boundary conversion logic
     */
    public function test_dashboard_worked_time_handles_configured_timezone_boundaries()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'TZ Task Test',
            'code' => 'TASK-TZ-1',
            'estimated_time_seconds' => 3600
        ]);

        // Configured timezone: Asia/Dubai (UTC+4)
        config(['constants.timezone' => 'Asia/Dubai']);

        // Local Date: 2026-05-24
        // Local start of day: 2026-05-24 00:00:00 Asia/Dubai => 2026-05-23 20:00:00 UTC
        // Local end of day: 2026-05-24 23:59:59 Asia/Dubai => 2026-05-24 19:59:59 UTC

        // 1. Log at 2026-05-23 21:00:00 UTC (is 2026-05-24 01:00:00 Asia/Dubai)
        // This log falls inside local 2026-05-24
        $logInDubaiDay = TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => '2026-05-23 21:00:00',
            'ended_at' => '2026-05-23 22:00:00',
            'duration_seconds' => 3600,
            'is_running' => false,
            'is_approved' => true
        ]);

        // 2. Log at 2026-05-24 21:00:00 UTC (is 2026-05-25 01:00:00 Asia/Dubai)
        // This log falls OUTSIDE local 2026-05-24
        $logOutsideDubaiDay = TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => '2026-05-24 21:00:00',
            'ended_at' => '2026-05-24 22:00:00',
            'duration_seconds' => 3600,
            'is_running' => false,
            'is_approved' => true
        ]);

        $service = app(DashboardServices::class);
        $workedTime = $service->getUsersTaskWorkedTime($user, '2026-05-24');

        // It should match user's logged time for 2026-05-24 Asia/Dubai timezone day
        $this->assertCount(1, $workedTime);
        $this->assertEquals('1h 00m', $workedTime[0]['total_worked_time']);
    }
}
