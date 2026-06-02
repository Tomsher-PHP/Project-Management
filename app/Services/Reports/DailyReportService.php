<?php

namespace App\Services\Reports;

use App\Models\TaskTimeLog;
class DailyReportService
{

    protected function query($request)
    {
        return TaskTimeLog::query()

            ->with([
                'user:id,name',
                'task:id,name,project_id',
                'task.project:id,name',
            ])

            ->when($request->project_id, function ($q) use ($request) {

                $projectIds = (array) $request->project_id;

                $q->whereHas('task', function ($taskQuery) use ($projectIds) {
                    $taskQuery->whereIn('project_id', $projectIds);
                });
            })

            ->when($request->user_id, function ($q) use ($request) {

                $userIds = (array) $request->user_id;

                $q->whereIn('user_id', $userIds);
            })

            ->when($request->start_date, function ($q) use ($request) {

                $q->whereDate('started_at', '>=', $request->start_date);
            })

            ->when($request->end_date, function ($q) use ($request) {

                $q->whereDate('started_at', '<=', $request->end_date);
            })

            ->latest('started_at');
    }

    /**
     * Report List
     */
    public function getLogs($request, $perPage)
    {
        $logs = $this->query($request)
            ->paginate($perPage)
            ->withQueryString();

        $logs->getCollection()->transform(function ($log) {

            $log->formatted_time =
                formatSecondsToHoursMinutes(
                    $log->logged_seconds ?? 0
                );

            return $log;
        });

        return $logs;
    }

    /**
     * Export
     */
    public function exportLogs($request)
    {
        return $this->query($request)->get();
    }
}
