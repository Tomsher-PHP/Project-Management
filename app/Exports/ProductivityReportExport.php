<?php

namespace App\Exports;

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

class ProductivityReportExport implements FromCollection, WithCustomStartCell, WithEvents, WithHeadings
{
    protected const MIN_COLUMN_WIDTH_INCHES = 1.45;
    protected const MAX_COLUMN_WIDTH_INCHES = 3.06;

    protected Collection $rows;
    protected array $columns;
    protected array $filters;
    protected array $filterSummary;
    protected Carbon $generatedAt;

    public function __construct(Collection $rows, array $columns, array $filters = [], ?Carbon $generatedAt = null)
    {
        $this->rows = $rows->values();
        $this->columns = $columns;
        $this->filters = $filters;
        $this->generatedAt = $generatedAt?->copy()
            ?? Carbon::now((string) config('constants.timezone', config('app.timezone')));
        $this->filterSummary = $this->buildFilterSummary();
    }

    public function collection()
    {
        return $this->rows->map(function (array $row) {
            return collect($this->columns)
                ->mapWithKeys(function ($label, $key) use ($row) {
                    return [$label => $this->resolveColumnValue($row, $key)];
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
                $lastDataRow = $headerRow + $this->rows->count();

                $this->writeHeaderSection($sheet, $lastColumn);
                $this->styleTable($sheet, $lastColumn, $headerRow, $lastDataRow);
                $this->applyColumnLayout($sheet, $headerRow, $lastDataRow);
                $this->applyIndicatorStyles($sheet, $headerRow);
            },
        ];
    }

    protected function resolveColumnValue(array $row, string $column): string
    {
        return match ($column) {
            'user' => (string) ($row['user_name'] ?? '-'),
            'completed_tasks_count' => (string) ($row['completed_tasks_count'] ?? 0),
            'estimated_hours' => (string) ($row['estimated_hours'] ?? '--'),
            'spend_hours' => (string) ($row['spend_hours'] ?? '--'),
            'saved_hours' => (string) ($row['saved_hours'] ?? '--'),
            'efficiency' => (string) ($row['efficiency_label'] ?? '0%'),
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
        $sheet->setCellValue('A1', 'Productivity Report');
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

        foreach (array_keys($this->columns) as $index => $columnKey) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $range = "{$columnLetter}{$headerRow}:{$columnLetter}" . max($lastDataRow, $headerRow);

            $sheet->getStyle($range)->getAlignment()->setHorizontal(
                in_array($columnKey, ['completed_tasks_count', 'estimated_hours', 'spend_hours', 'saved_hours', 'efficiency'], true)
                    ? Alignment::HORIZONTAL_CENTER
                    : Alignment::HORIZONTAL_LEFT
            );
        }
    }

    protected function applyColumnLayout($sheet, int $headerRow, int $lastDataRow): void
    {
        $minWidth = $this->convertInchesToColumnWidth(self::MIN_COLUMN_WIDTH_INCHES);
        $maxWidth = $this->convertInchesToColumnWidth(self::MAX_COLUMN_WIDTH_INCHES);

        foreach (array_keys($this->columns) as $index => $columnKey) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $width = $this->resolveColumnWidth($columnKey, $minWidth, $maxWidth);

            $sheet->getColumnDimension($columnLetter)->setAutoSize(false);
            $sheet->getColumnDimension($columnLetter)->setWidth($width);

            if ($columnKey === 'user') {
                $sheet->getStyle("{$columnLetter}{$headerRow}:{$columnLetter}" . max($headerRow, $lastDataRow))
                    ->getAlignment()
                    ->setWrapText(true);
            }
        }
    }

    protected function applyIndicatorStyles($sheet, int $headerRow): void
    {
        $columnIndexes = array_flip(array_keys($this->columns));

        $this->rows->each(function (array $row, int $index) use ($sheet, $headerRow, $columnIndexes) {
            $rowNumber = $headerRow + $index + 1;
            $savedPalette = $this->resolveSavedPalette((int) ($row['saved_seconds'] ?? 0));
            $efficiencyPalette = $this->resolveEfficiencyPalette((float) ($row['efficiency_percentage'] ?? 0));

            foreach (['spend_hours', 'saved_hours'] as $columnKey) {
                if (! isset($columnIndexes[$columnKey])) {
                    continue;
                }

                $column = Coordinate::stringFromColumnIndex($columnIndexes[$columnKey] + 1);

                $sheet->getStyle("{$column}{$rowNumber}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $savedPalette['text']],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $savedPalette['fill']],
                    ],
                ]);
            }

            if (isset($columnIndexes['efficiency'])) {
                $column = Coordinate::stringFromColumnIndex($columnIndexes['efficiency'] + 1);

                $sheet->getStyle("{$column}{$rowNumber}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $efficiencyPalette['text']],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $efficiencyPalette['fill']],
                    ],
                ]);
            }
        });
    }

    protected function resolveSavedPalette(int $savedSeconds): array
    {
        if ($savedSeconds === 0) {
            return [
                'fill' => 'E5E7EB',
                'text' => '475569',
            ];
        }

        return $savedSeconds > 0
            ? ['fill' => 'DCFCE7', 'text' => '16A34A']
            : ['fill' => 'FEE2E2', 'text' => 'DC2626'];
    }

    protected function resolveEfficiencyPalette(float $efficiency): array
    {
        if ($efficiency <= 0) {
            return [
                'fill' => 'E5E7EB',
                'text' => '475569',
            ];
        }

        return match (true) {
            $efficiency >= 120 => ['fill' => 'DCFCE7', 'text' => '16A34A'],
            $efficiency >= 100 => ['fill' => 'ECFDF5', 'text' => '22C55E'],
            $efficiency >= 80 => ['fill' => 'FFEDD5', 'text' => 'EA580C'],
            default => ['fill' => 'FEE2E2', 'text' => 'DC2626'],
        };
    }

    protected function buildFilterSummary(): array
    {
        $summary = [];

        $dateFrom = $this->parseDateInput($this->filters['from_date'] ?? null);
        $dateTo = $this->parseDateInput($this->filters['to_date'] ?? null);

        if ($dateFrom) {
            $summary['Date From'] = AppServiceProvider::formatAppDate($dateFrom->toDateString());
        }

        if ($dateTo) {
            $summary['Date To'] = AppServiceProvider::formatAppDate($dateTo->toDateString());
        }

        $userNames = $this->resolveUserFilterNames();

        if ($userNames !== []) {
            $summary['Users'] = implode(', ', $userNames);
        }

        return $summary;
    }

    protected function resolveUserFilterNames(): array
    {
        $selectedIds = collect($this->filters['user_id'] ?? [])
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            return [];
        }

        $namesById = User::query()
            ->whereIn('id', $selectedIds)
            ->pluck('name', 'id');

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
        $values = $this->rows->map(fn(array $row) => $this->resolveColumnValue($row, $columnKey))
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
