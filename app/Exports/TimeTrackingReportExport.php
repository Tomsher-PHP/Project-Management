<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\User;
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

class TimeTrackingReportExport implements FromCollection, WithCustomStartCell, WithEvents, WithHeadings
{
    protected const MIN_COLUMN_WIDTH_INCHES = 1.45;
    protected const MAX_COLUMN_WIDTH_INCHES = 3.06;

    protected Collection $reports;
    protected array $columns;
    protected array $filters;
    protected array $filterSummary;
    protected Carbon $generatedAt;

    public function __construct(Collection $reports, array $columns, array $filters = [], ?Carbon $generatedAt = null)
    {
        $this->reports = $reports;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->generatedAt = $generatedAt?->copy()
            ?? Carbon::now((string) config('constants.timezone', config('app.timezone')));
        $this->filterSummary = $this->buildFilterSummary();
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
                $lastDataRow = $headerRow + $this->reports->count();

                $this->writeHeaderSection($sheet, $lastColumn);
                $this->styleTable($sheet, $lastColumn, $headerRow, $lastDataRow);
                $this->applyColumnLayout($sheet, $headerRow, $lastDataRow);
            },
        ];
    }

    protected function resolveColumnValue($report, string $column): string
    {
        return match ($column) {
            'project' => $report->task?->project?->name ?? '-',
            'milestone' => $report->task?->projectMilestone?->name
                ?? $report->task?->projectSprint?->projectMilestone?->name
                ?? '-',
            'sprint' => $report->task?->projectSprint?->name ?? '-',
            'task' => $report->task?->name ?? '-',
            'user' => $report->user?->name ?? '-',
            'date' => AppServiceProvider::formatAppDate($report->started_at),
            'start_time' => AppServiceProvider::formatAppTime($report->started_at),
            'end_time' => AppServiceProvider::formatAppTime($report->ended_at),
            'duration' => $report->duration_seconds ? formatSecondsToHMS($report->duration_seconds) : '-',
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
        $sheet->setCellValue('A1', 'Time Tracking Report');
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
            $bodyRange = "A" . ($headerRow + 1) . ":{$lastColumn}{$lastDataRow}";

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
                in_array($columnKey, ['date', 'start_time', 'end_time', 'duration'], true)
                    ? Alignment::HORIZONTAL_CENTER
                    : Alignment::HORIZONTAL_LEFT
            );
        }
    }

    protected function applyColumnLayout($sheet, int $headerRow, int $lastDataRow): void
    {
        $minWidth = $this->convertInchesToColumnWidth(self::MIN_COLUMN_WIDTH_INCHES);
        $maxWidth = $this->convertInchesToColumnWidth(self::MAX_COLUMN_WIDTH_INCHES);
        $wrapColumns = ['project', 'milestone', 'sprint', 'task'];

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

        $dateSummary = $this->buildDateRangeSummary();

        if ($dateSummary !== null) {
            $summary['Date Range'] = $dateSummary;
        }

        foreach ([
            'Project' => [Project::class, 'project_id'],
            'Milestone' => [ProjectMilestone::class, 'project_milestone_id'],
            'Sprint' => [ProjectSprint::class, 'project_sprint_id'],
            'User' => [User::class, 'user_id'],
        ] as $label => [$modelClass, $requestKey]) {
            $names = $this->resolveFilterNames($modelClass, $requestKey);

            if ($names !== []) {
                $summary[$label] = implode(', ', $names);
            }
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

        $namesById = match ($modelClass) {
            ProjectMilestone::class => ProjectMilestone::query()
                ->with('project:id,name')
                ->whereIn('id', $selectedIds)
                ->get(['id', 'project_id', 'name'])
                ->mapWithKeys(fn(ProjectMilestone $milestone) => [
                    $milestone->id => $milestone->project?->name
                        ? "{$milestone->project->name} / {$milestone->name}"
                        : $milestone->name,
                ]),
            ProjectSprint::class => ProjectSprint::query()
                ->with(['project:id,name', 'projectMilestone:id,name'])
                ->whereIn('id', $selectedIds)
                ->get(['id', 'project_id', 'project_milestone_id', 'name'])
                ->mapWithKeys(fn(ProjectSprint $sprint) => [
                    $sprint->id => collect([
                        $sprint->project?->name,
                        $sprint->projectMilestone?->name,
                        $sprint->name,
                    ])->filter()->implode(' / '),
                ]),
            default => $modelClass::query()
                ->whereIn('id', $selectedIds)
                ->pluck('name', 'id'),
        };

        return $selectedIds
            ->map(fn(int $id) => $namesById->get($id))
            ->filter(fn($name) => filled($name))
            ->unique()
            ->values()
            ->all();
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

    protected function resolveColumnWidth(string $columnKey, float $minWidth, float $maxWidth): float
    {
        $values = $this->reports->map(fn($report) => $this->resolveColumnValue($report, $columnKey))
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
