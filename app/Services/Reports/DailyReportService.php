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

                'task.project:id,name'
            ])

            ->when($request->project_id, function ($q) use ($request) {

                $q->whereHas('task', function ($taskQuery) use ($request) {

                    $taskQuery->where('project_id', $request->project_id);
                });
            })

            ->when($request->staff_id, function ($q) use ($request) {

                $q->where('user_id', $request->staff_id);
            })

            ->when($request->task_id, function ($q) use ($request) {

                $q->where('task_id', $request->task_id);
            })

            ->when(
                !empty($request->start_date) &&
                    !empty($request->end_date),

                function ($q) use ($request) {

                    $q->whereBetween('started_at', [
                        $request->start_date . ' 00:00:00',
                        $request->end_date . ' 23:59:59',
                    ]);
                }
            )

            ->orderBy('started_at');
    }

    public function getReports($request, $perPage)
    {
        return $this->query($request)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getTotalMinutes($request)
    {
        return round(
            $this->query($request)
                ->sum('duration_seconds') / 60
        );
    }

    public function export($request)
    {
        return Excel::download(
            new DailyReportExport(
                $this->query($request)->get()
            ),
            'daily-report.xlsx'
        );
    }
}
