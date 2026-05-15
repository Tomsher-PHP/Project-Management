<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaskReportExport implements FromCollection, WithHeadings
{
    protected Collection $tasks;

    public function __construct(Collection $tasks)
    {
        $this->tasks = $tasks;
    }

    public function collection()
    {
        return $this->tasks->map(function ($task, $index) {

            return [
                '#'                 => $index + 1,
                'Task Name'         => $task->name,
                'Project'           => $task->project->name ?? '-',
                'Assigned To'       => $task->assignedUser->name ?? '-',
                'Start Date'        => $task->start_date?->format('d M Y'),
                'Due Date'          => $task->due_date?->format('d M Y'),
                'Estimated Hours'   => $task->estimated_hours,
                'Actual Hours'      => $task->actual_hours,
                'Progress %'        => $task->progress_percentage,
                'Status'            => $task->taskStatus->name ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Task Name',
            'Project',
            'Assigned To',
            'Start Date',
            'Due Date',
            'Estimated Hours',
            'Actual Hours',
            'Progress %',
            'Status',
        ];
    }
}