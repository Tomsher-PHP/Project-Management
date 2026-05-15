<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProjectReportExport implements FromCollection, WithHeadings
{
    protected Collection $projects;

    public function __construct(Collection $projects)
    {
        $this->projects = $projects;
    }

    public function collection()
    {
        return $this->projects->map(function ($project, $index) {

        $estimatedSeconds = $project->projectMilestones->sum('estimated_time_seconds') ?? 0;
        $actualSeconds = $project->projectMilestones->sum('actual_time_seconds') ?? 0;

        $progress_percentage = $estimatedSeconds > 0
            ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
            : 0;

            return [
                '#'                 => $index + 1,
                'Project Name'      => $project->name,
                'Customer'          => $project->customer->name ?? '-',
                'Sales Person'      => $project->salesPerson->name ?? '-',
                'Start Date'        => $project->start_date?->format('d M Y'),
                'End Date'          => $project->end_date?->format('d M Y'),
                'Estimated Hours' => formatSecondsToHoursMinutes($project->projectMilestones->sum('estimated_time_seconds')),
                'Actual Hours' => formatSecondsToHoursMinutes($project->projectMilestones->sum('actual_time_seconds')),
                'Progress %' => $progress_percentage . '%',
                'Milestones'        => $project->completed_milestones . ' / ' . $project->total_milestones,
                'Status'            => $project->projectStatus->name ?? '-',
                'Stage'             => $project->projectStage->name ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Project Name',
            'Customer',
            'Sales Person',
            'Start Date',
            'End Date',
            'Estimated Hours',
            'Actual Hours',
            'Progress %',
            'Milestones',
            'Status',
            'Stage',
        ];
    }
}