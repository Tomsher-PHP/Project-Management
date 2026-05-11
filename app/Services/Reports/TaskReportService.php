<?php

namespace App\Services\Reports;

use App\Exports\TaskReportExport;
use App\Models\Task;
use App\Services\TaskQueryService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TaskReportService
{
    protected TaskQueryService $taskQueryService;

    public function __construct(
        TaskQueryService $taskQueryService
    ) {
        $this->taskQueryService = $taskQueryService;
    }

    /**
     * Base Query
     */
    protected function query($user, array $filters = [])
    {
        return $this->taskQueryService
            ->baseQuery($user)

            ->with([
                'project:id,name',
                'projectMilestone:id,name',
                'projectSprint:id,name',
                'currentAssignee:id,name',
                'status:id,name,color,type,is_completed',
                'taskType:id,name,code,color',
                'taskMode:id,name,code,color',
            ])

            ->filter($filters)

            ->sort($filters);
    }

    /**
     * Task Report List
     */
    public function getTasks(
        Request $request,
        int $perPage
    ) {
        $user = $request->user();

        $tasks = $this->query(
            $user,
            $request->all()
        )
            ->paginate($perPage)
            ->withQueryString();

        $tasks->getCollection()->transform(function ($task) {

            $task->estimated_hours =
                round(($task->estimated_time_seconds ?? 0) / 3600);

            $task->actual_hours =
                round(($task->actual_time_seconds ?? 0) / 3600);

            $task->progress_percentage =
                $task->progress ?? 0;

            $status =
                strtolower($task->status->name ?? '');

            $task->status_badge_class = match ($status) {
                'completed' => 'bg-green-100 text-green-700',
                'in progress' => 'bg-yellow-100 text-yellow-700',
                'pending' => 'bg-gray-100 text-gray-700',
                default => 'bg-blue-100 text-blue-700',
            };

            return $task;
        });

        return $tasks;
    }

    /**
     * Export
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $tasks = $this->query(
            $user,
            $request->all()
        )->get();

        $tasks->transform(function ($task) {

            $task->estimated_hours =
                round(($task->estimated_time_seconds ?? 0) / 3600);

            $task->actual_hours =
                round(($task->actual_time_seconds ?? 0) / 3600);

            $task->progress_percentage =
                $task->progress ?? 0;

            return $task;
        });

        return Excel::download(
            new TaskReportExport($tasks),
            'task-report.xlsx'
        );
    }
}