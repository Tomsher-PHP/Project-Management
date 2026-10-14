<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\ProjectTimeline;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\ProjectTimeService;
use App\Services\TaskRequestServices;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    $user = User::first() ?? User::factory()->create();
    $project = Project::factory()->create(['project_flow' => 'agile']);
    
    $timeline = ProjectTimeline::create(['project_id' => $project->id, 'name' => 'Timeline A', 'status' => 1]);

    // Create a pending task
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'project_timeline_id' => $timeline->id,
        'request_status' => 'pending',
        'current_assignee_id' => $user->id,
    ]);
    
    // Create an unapproved time log
    $log = TaskTimeLog::create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->subHour(),
        'ended_at' => now(),
        'duration_seconds' => 3600,
        'is_approved' => false
    ]);
    
    $pts = app(ProjectTimeService::class);
    $pts->recalculateByTask($task->id);

    $task->refresh();
    $timeline->refresh();
    echo "Before approval:\n";
    echo "Task Actual Time: {$task->actual_time_seconds}\n";
    echo "Timeline Actual Time: {$timeline->actual_time_seconds}\n";

    // Now approve it
    $trs = app(TaskRequestServices::class);
    // TRICK: handleAction expects it to be pending, we bypass it for testing by calling approve using reflection
    $reflection = new ReflectionClass($trs);
    $method = $reflection->getMethod('approve');
    $method->setAccessible(true);
    $method->invoke($trs, $user, $task);

    $task->refresh();
    $timeline->refresh();
    echo "After approval:\n";
    echo "Task Actual Time: {$task->actual_time_seconds}\n";
    echo "Timeline Actual Time: {$timeline->actual_time_seconds}\n";

} finally {
    DB::rollBack();
}
