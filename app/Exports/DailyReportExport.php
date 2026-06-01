<?php

namespace App\Exports;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DailyReportExport implements FromCollection, WithHeadings
{
    protected Collection $reports;

    public function __construct(Collection $reports)
    {
        $this->reports = $reports;
    }

    public function collection()
    {
        return $this->reports->values()->map(function ($report, $index) {
            return [
                '#' => $index + 1,
                'Project' => $report->task?->project?->name ?? '-',
                'User' => $report->user?->name ?? '-',
                'Date' => AppServiceProvider::formatAppDate($report->started_at),
                'Start Time' => AppServiceProvider::formatAppTime($report->started_at),
                'End Time' => AppServiceProvider::formatAppTime($report->ended_at),
                'Duration' => $report->duration_seconds ? formatSecondsToHMS($report->duration_seconds) : '-',
                'Task' => $report->task?->name ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Project',
            'User',
            'Date',
            'Start Time',
            'End Time',
            'Duration',
            'Task',
        ];
    }
}
