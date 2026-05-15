<?php

namespace App\Services\Reports;

use App\Models\ProjectMilestone;
use Maatwebsite\Excel\Facades\Excel;

class MilestoneReportService
{
    public function getMilestones($request, $perPage)
    {
        return ProjectMilestone::query()

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
                $q->whereDate('due_date', '>=', $request->start_date);
            })

            ->when($request->filled('end_date'), function ($q) use ($request) {
                $q->whereDate('due_date', '<=', $request->end_date);
            })

            ->latest()

            ->paginate($perPage)

            ->through(function ($milestone) {

                $milestone->progress_percentage =
                    $milestone->total_tasks > 0
                    ? round(($milestone->completed_tasks / $milestone->total_tasks) * 100)
                    : 0;

                return $milestone;
            });
    }

    public function export($request)
    {
        return Excel::download(
            new MilestoneReportExport(
                $this->query($request)->get()
            ),
            'milestone-report.xlsx'
        );
    }
}
