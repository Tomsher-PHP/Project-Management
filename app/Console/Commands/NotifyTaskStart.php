<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyTaskStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:notify-start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify assignees when an active task should be started soon.';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $now = now();
        $sentCount = 0;
        $minutesBefore = (int) env('TASK_START_NOTIFICATION_MINUTES_BEFORE', 10);

        $tasks = Task::query()
            ->with([
                'project:id,name',
                'status:id,name,type,is_completed',
                'currentAssignee:id,name,email',
            ])
            ->whereNotNull('current_assignee_id')
            ->whereNotNull('due_date_time')
            ->whereNull('start_notify_at')
            ->where('due_date_time', '>=', now())
            ->where('estimated_time_seconds', '>', 0)
            ->whereHas('status', fn($query) => $query->where('type', 'pending'))
            ->whereDoesntHave('timeLogs')
            ->get(['id', 'name', 'project_id', 'status_id', 'current_assignee_id', 'due_date_time', 'estimated_time_seconds']);

        if ($tasks->isNotEmpty()) {
            foreach ($tasks as $task) {
                $dueAt = $task->due_date_time;
                $startAt = $dueAt->copy()->subSeconds($task->estimated_time_seconds);
                $notifyAt = $startAt->copy()->subMinutes($minutesBefore);

                if ($now->gte($notifyAt)) {
                    if ($notificationService->notifyTaskStart($task)) {
                        $sentCount++;
                    }
                }
            }

            Log::info("Task start notifications sent: {$sentCount}");
        }

        return self::SUCCESS;
    }
}
