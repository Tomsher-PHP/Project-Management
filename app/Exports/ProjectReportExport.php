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

            return [
                '#'                 => $index + 1,
                'Project Name'      => $project->name,
                'Customer'          => $project->customer->name ?? '-',
                'Sales Person'      => $project->salesPerson->name ?? '-',
                'Start Date'        => $project->start_date?->format('d M Y'),
                'End Date'          => $project->end_date?->format('d M Y'),
                'Estimated Hours' => round(($project->estimated_time_seconds ?? 0) / 3600),
                'Actual Hours' => round(($project->actual_time_seconds ?? 0) / 3600),
                'Progress %' => ($project->estimated_time_seconds ?? 0) > 0
                    ? round(
                        (($project->actual_time_seconds ?? 0) /
                        $project->estimated_time_seconds) * 100
                    )
                    : 0,
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