<?php

namespace App\Services\Reports;

use App\Models\ProjectSprint;

class SprintReportService
{
    public function getSprints($request, $perPage)
    {
        return ProjectSprint::query()

            ->with([
                'project',
                'status',
            ])

            ->withCount([
                'tasks as total_tasks',

                'tasks as completed_tasks' => function ($q) {
                    $q->whereHas('status', function ($status) {
                        $status->where('is_completed', true);
                    });
                }
            ])

            ->when($request->filled('project_id'), function ($q) use ($request) {
                $q->whereIn('project_id', (array) $request->project_id);
            })

            ->when($request->filled('start_date'), function ($q) use ($request) {
                $q->whereDate('start_date', '>=', $request->start_date);
            })

            ->when($request->filled('end_date'), function ($q) use ($request) {
                $q->whereDate('end_date', '<=', $request->end_date);
            })

            ->latest()

            ->paginate($perPage)

            ->through(function ($sprint) {

                $sprint->pending_tasks =
                    $sprint->total_tasks - $sprint->completed_tasks;

                $sprint->progress_percentage =
                    $sprint->total_tasks > 0
                    ? round(($sprint->completed_tasks / $sprint->total_tasks) * 100)
                    : 0;

                return $sprint;
            });
    }

    public function export($request)
    {
        return Excel::download(
            new SprintReportExport(
                $this->query($request)->get()
            ),
            'sprint-report.xlsx'
        );
    }
}
