<?php

namespace App\Exports;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DailyReportExport implements FromCollection, WithHeadings
{
    protected Collection $reports;
    protected array $columns;

    public function __construct(Collection $reports, array $columns)
    {
        $this->reports = $reports;
        $this->columns = $columns;
    }

    public function collection()
    {
        return $this->reports->values()->map(function ($report) {
            return collect($this->columns)
                ->mapWithKeys(function ($label, $key) use ($report) {
                    return [$label => $this->resolveColumnValue($report, $key)];
                })
                ->all();
        });
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    protected function resolveColumnValue($report, string $column): string
    {
        return match ($column) {
            'project' => $report->task?->project?->name ?? '-',
            'user' => $report->user?->name ?? '-',
            'date' => AppServiceProvider::formatAppDate($report->started_at),
            'start_time' => AppServiceProvider::formatAppTime($report->started_at),
            'end_time' => AppServiceProvider::formatAppTime($report->ended_at),
            'duration' => $report->duration_seconds ? formatSecondsToHMS($report->duration_seconds) : '-',
            'task' => $report->task?->name ?? '-',
            default => '-',
        };
    }
}
