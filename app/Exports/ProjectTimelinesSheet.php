<?php

namespace App\Exports;

use App\Models\ProjectTimeline;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectTimelinesSheet extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting,
    WithCustomValueBinder
{
    protected array $timelineStatuses;

    public function __construct(
        protected Collection $projects
    ) {
        $this->timelineStatuses = (array) config('project_constants.project_timeline_statuses', []);
    }

    public function title(): string
    {
        return 'Project Timelines';
    }

    public function collection(): Collection
    {
        return $this->projects->flatMap(function ($project) {
            return $project->projectTimelines->map(function ($timeline) use ($project) {
                $timeline->setRelation('project', $project);
                return $timeline;
            });
        });
    }

    public function headings(): array
    {
        return [
            'Project Code',
            'Project Name',
            'Timeline Name',
            'Timeline Status',
            'Start Date',
            'End Date',
            'Estimated Hours',
        ];
    }

    /**
     * @param ProjectTimeline $timeline
     */
    public function map($timeline): array
    {
        $statusKey = $timeline->status;
        $statusLabel = isset($this->timelineStatuses[$statusKey])
            ? ucfirst((string) $this->timelineStatuses[$statusKey])
            : '-';

        return [
            (string) ($timeline->project?->project_code ?? '-'),
            $timeline->project?->name ?? '-',
            $timeline->name ?? '-',
            $statusLabel,
            AppServiceProvider::formatAppDate($timeline->start_date, '-'),
            AppServiceProvider::formatAppDate($timeline->end_date, '-'),
            $this->formatEstimatedHours($timeline->estimated_time_seconds),
        ];
    }

    protected function formatEstimatedHours(?int $seconds): float|int
    {
        if ($seconds === null || $seconds <= 0) {
            return 0;
        }

        $hours = round($seconds / 3600, 2);

        return (floor($hours) == $hours) ? (int) $hours : $hours;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_GENERAL,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && $cell->getColumn() === 'A') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Header styling
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
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
        $sheet->getRowDimension(1)->setRowHeight(26);

        if ($highestRow >= 2) {
            $sheet->getStyle('A2:' . $highestColumn . $highestRow)->applyFromArray([
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

            for ($row = 2; $row <= $highestRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(20);
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('F8FAFC');
                }
            }
        }

        // Alignments
        $centeredColumns = ['A', 'D', 'E', 'F'];
        foreach ($centeredColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . max($highestRow, 2))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle('G2:G' . max($highestRow, 2))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $highestColumn . max($highestRow, 1));

        return [];
    }
}

