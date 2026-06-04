<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProjectReportExport implements FromCollection, WithCustomStartCell, WithEvents, WithHeadings
{
    protected const MIN_COLUMN_WIDTH_INCHES = 1.45;
    protected const MAX_COLUMN_WIDTH_INCHES = 3.06;

    protected Collection $projects;
    protected array $columns;
    protected array $filters;
    protected array $filterSummary;
    protected array $projectMetricCache = [];
    protected Carbon $generatedAt;

    public function __construct(
        Collection $projects,
        array $columns,
        array $filters = [],
        ?Carbon $generatedAt = null
    ) {
        $this->projects = $projects;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->generatedAt = $generatedAt?->copy()
            ?? Carbon::now((string) config('constants.timezone', config('app.timezone')));
        $this->filterSummary = $this->buildFilterSummary();
    }

    public function collection()
    {
        return $this->projects->values()->map(function ($project) {
            return collect($this->columns)
                ->mapWithKeys(function ($label, $key) use ($project) {
                    return [$label => $this->resolveColumnValue($project, $key)];
                })
                ->all();
        });
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function startCell(): string
    {
        return 'A' . $this->tableStartRow();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $this->lastColumnLetter();
                $headerRow = $this->tableStartRow();
                $lastDataRow = $headerRow + $this->projects->count();

                $this->writeHeaderSection($sheet, $lastColumn);
                $this->styleTable($sheet, $lastColumn, $headerRow, $lastDataRow);
                $this->applyColumnLayout($sheet, $headerRow, $lastDataRow);
            },
        ];
    }

    protected function resolveColumnValue($project, string $column): string
    {
        $metrics = $this->resolveProjectMetrics($project);
        $progressPercentage = $this->calculateProgressPercentage(
            $metrics['estimated_seconds'],
            $metrics['actual_seconds']
        );

        return match ($column) {
            'project_name' => $project->name ?? '-',
            'customer' => $project->customer?->name ?? '-',
            'sales_person' => $project->salesPerson?->name ?? '-',
            'start_date' => AppServiceProvider::formatAppDate($project->start_date, '-'),
            'end_date' => AppServiceProvider::formatAppDate($project->end_date, '-'),
            'estimated_hours' => formatSecondsToHoursMinutes($metrics['estimated_seconds']),
            'actual_hours' => formatSecondsToHoursMinutes($metrics['actual_seconds']),
            'progress' => $progressPercentage . '%',
            'priority' => $this->resolvePriorityLabel($project->priority),
            'milestone_status' => sprintf(
                '%d / %d',
                $metrics['completed_milestones'],
                $metrics['total_milestones']
            ),
            'status' => $project->projectStatus?->name ?? '-',
            'stage' => $project->projectStage?->name ?? '-',
            default => '-',
        };
    }

    protected function tableStartRow(): int
    {
        return count($this->filterSummary) + 4;
    }

    protected function lastColumnLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(count($this->columns), 1));
    }

    protected function writeHeaderSection($sheet, string $lastColumn): void
    {
        $sheet->setCellValue('A1', 'Project Report');
        $sheet->mergeCells("A1:{$lastColumn}1");

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;

        foreach ($this->filterSummary as $label => $value) {
            if ($lastColumn === 'A') {
                $sheet->setCellValue("A{$row}", $label . ': ' . $value);
            } else {
                $sheet->setCellValue("A{$row}", $label . ':');
                $sheet->setCellValue("B{$row}", $value);
                $sheet->mergeCells("B{$row}:{$lastColumn}{$row}");
            }

            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'font' => [
                    'size' => 10,
                    'color' => ['rgb' => '334155'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        if ($lastColumn === 'A') {
            $sheet->setCellValue("A{$row}", 'Generated At: ' . $this->generatedAtLabel());
        } else {
            $sheet->setCellValue("A{$row}", 'Generated At:');
            $sheet->setCellValue("B{$row}", $this->generatedAtLabel());
            $sheet->mergeCells("B{$row}:{$lastColumn}{$row}");
        }

        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => '475569'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getRowDimension($row)->setRowHeight(20);

        $spacerRow = $row + 1;
        $sheet->getRowDimension($spacerRow)->setRowHeight(10);
    }

    protected function styleTable($sheet, string $lastColumn, int $headerRow, int $lastDataRow): void
    {
        $headerRange = "A{$headerRow}:{$lastColumn}{$headerRow}";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8EEF9'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '94A3B8'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        if ($lastDataRow > $headerRow) {
            $bodyRange = 'A' . ($headerRow + 1) . ":{$lastColumn}{$lastDataRow}";

            $sheet->getStyle($bodyRange)->applyFromArray([
                'font' => [
                    'size' => 10,
                    'color' => ['rgb' => '1E293B'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            for ($row = $headerRow + 1; $row <= $lastDataRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(22);

                if ((($row - $headerRow) % 2) === 0) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('F8FAFC');
                }
            }
        }

        $sheet->freezePane('A' . ($headerRow + 1));

        $columnKeys = array_keys($this->columns);

        foreach ($columnKeys as $index => $columnKey) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $range = "{$columnLetter}{$headerRow}:{$columnLetter}" . max($lastDataRow, $headerRow);

            $sheet->getStyle($range)->getAlignment()->setHorizontal(
                in_array($columnKey, ['start_date', 'end_date', 'estimated_hours', 'actual_hours', 'progress', 'milestone_status'], true)
                    ? Alignment::HORIZONTAL_CENTER
                    : Alignment::HORIZONTAL_LEFT
            );
        }
    }

    protected function applyColumnLayout($sheet, int $headerRow, int $lastDataRow): void
    {
        $minWidth = $this->convertInchesToColumnWidth(self::MIN_COLUMN_WIDTH_INCHES);
        $maxWidth = $this->convertInchesToColumnWidth(self::MAX_COLUMN_WIDTH_INCHES);
        $wrapColumns = ['project_name', 'customer', 'sales_person', 'status', 'stage'];

        foreach (array_keys($this->columns) as $index => $columnKey) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $width = $this->resolveColumnWidth($columnKey, $minWidth, $maxWidth);

            $sheet->getColumnDimension($columnLetter)->setAutoSize(false);
            $sheet->getColumnDimension($columnLetter)->setWidth($width);

            if (in_array($columnKey, $wrapColumns, true)) {
                $sheet->getStyle("{$columnLetter}{$headerRow}:{$columnLetter}" . max($headerRow, $lastDataRow))
                    ->getAlignment()
                    ->setWrapText(true);
            }
        }
    }

    protected function buildFilterSummary(): array
    {
        $summary = [];

        if (filled($this->filters['name'] ?? null)) {
            $summary['Name'] = trim((string) $this->filters['name']);
        }

        $dateSummary = $this->buildDateRangeSummary();

        if ($dateSummary !== null) {
            $summary['Date Range'] = $dateSummary;
        }

        $projectFlows = $this->resolveProjectFlowLabels();

        if ($projectFlows !== []) {
            $summary['Project Flow'] = implode(', ', $projectFlows);
        }

        foreach ([
            'Project' => [Project::class, 'id'],
            'Customer' => [Customer::class, 'customer_id'],
            'Project Status' => [ProjectStatus::class, 'status_id'],
        ] as $label => [$modelClass, $requestKey]) {
            $names = $this->resolveFilterNames($modelClass, $requestKey);

            if ($names !== []) {
                $summary[$label] = implode(', ', $names);
            }
        }

        $priorities = $this->resolvePriorityLabelsFromFilters();

        if ($priorities !== []) {
            $summary['Priority'] = implode(', ', $priorities);
        }

        return $summary;
    }

    protected function buildDateRangeSummary(): ?string
    {
        $startDate = $this->parseDateInput($this->filters['start_date'] ?? null);
        $endDate = $this->parseDateInput($this->filters['end_date'] ?? null);

        if ($startDate && $endDate) {
            if ($startDate->gt($endDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            return AppServiceProvider::formatAppDate($startDate->toDateString())
                . ' - ' .
                AppServiceProvider::formatAppDate($endDate->toDateString());
        }

        $singleDate = $startDate ?? $endDate;

        return $singleDate
            ? AppServiceProvider::formatAppDate($singleDate->toDateString())
            : null;
    }

    protected function resolveProjectFlowLabels(): array
    {
        $selectedFlows = collect($this->filters['project_flow'] ?? [])
            ->flatten()
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($selectedFlows->isEmpty()) {
            return [];
        }

        $flowLabels = collect(config('project_constants.project_flows', []));

        return $selectedFlows
            ->map(fn(string $flow) => $flowLabels->get($flow))
            ->filter(fn($label) => filled($label))
            ->unique()
            ->values()
            ->all();
    }

    protected function resolvePriorityLabelsFromFilters(): array
    {
        $selectedPriorities = collect($this->filters['priority'] ?? [])
            ->flatten()
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($selectedPriorities->isEmpty()) {
            return [];
        }

        return $selectedPriorities
            ->map(fn(string $priority) => $this->resolvePriorityLabel($priority))
            ->filter(fn(string $label) => $label !== '-')
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveFilterNames(string $modelClass, string $requestKey): array
    {
        $selectedIds = collect($this->filters[$requestKey] ?? [])
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            return [];
        }

        $namesById = $modelClass::query()
            ->whereIn('id', $selectedIds)
            ->pluck('name', 'id');

        return $selectedIds
            ->map(fn(int $id) => $namesById->get($id))
            ->filter(fn($name) => filled($name))
            ->unique()
            ->values()
            ->all();
    }

    protected function resolvePriorityLabel(?string $priority): string
    {
        if (! filled($priority)) {
            return '-';
        }

        return config('project_constants.project_priorities.' . $priority . '.label')
            ?? ucfirst(str_replace('_', ' ', $priority));
    }

    protected function calculateProgressPercentage(int $estimatedSeconds, int $actualSeconds): string
    {
        $percentage = $estimatedSeconds > 0
            ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
            : 0;

        return rtrim(rtrim(number_format((float) $percentage, 2, '.', ''), '0'), '.');
    }

    protected function generatedAtLabel(): string
    {
        return sprintf(
            '%s, %s',
            AppServiceProvider::formatAppDate($this->generatedAt),
            AppServiceProvider::formatAppTime($this->generatedAt),
        );
    }

    protected function parseDateInput(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', trim($value));
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === trim($value)
            ? $date
            : null;
    }

    protected function resolveProjectMetrics($project): array
    {
        $cacheKey = (string) ($project->id ?? spl_object_id($project));

        if (isset($this->projectMetricCache[$cacheKey])) {
            return $this->projectMetricCache[$cacheKey];
        }

        return $this->projectMetricCache[$cacheKey] = [
            'estimated_seconds' => (int) ($project->projectMilestones->sum('estimated_time_seconds') ?? 0),
            'actual_seconds' => (int) ($project->projectMilestones->sum('actual_time_seconds') ?? 0),
            'completed_milestones' => (int) ($project->completed_milestones ?? 0),
            'total_milestones' => (int) ($project->total_milestones ?? 0),
        ];
    }

    protected function resolveColumnWidth(string $columnKey, float $minWidth, float $maxWidth): float
    {
        $values = $this->projects->map(fn($project) => $this->resolveColumnValue($project, $columnKey))
            ->push($this->columns[$columnKey] ?? '')
            ->filter(fn($value) => is_string($value));

        $longestLength = $values
            ->map(fn(string $value) => mb_strlen(preg_replace('/\s+/', ' ', trim($value))))
            ->max() ?? 0;

        $calculatedWidth = $longestLength + 2;

        return max($minWidth, min($maxWidth, (float) $calculatedWidth));
    }

    protected function convertInchesToColumnWidth(float $inches): float
    {
        return round($inches * 9.14, 2);
    }
}
