<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\ProjectCategory;
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

class ProjectListSheet extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting,
    WithCustomValueBinder
{
    protected array $categoriesMap;
    protected array $priorities;

    public function __construct(
        protected Collection $projects,
        protected array $filters = []
    ) {
        $this->categoriesMap = ProjectCategory::pluck('name', 'id')->all();
        $this->priorities = (array) config('project_constants.project_priorities', []);
    }

    public function title(): string
    {
        return 'Projects';
    }

    public function collection(): Collection
    {
        return $this->projects;
    }

    public function headings(): array
    {
        return [
            'Project Code',
            'Project Name',
            'Customer Code',
            'Customer Name',
            'Project Flow',
            'Priority',
            'Status',
            'Stage',
            'Service Types',
            'Categories',
            'Timeline Name',
            'Start Date',
            'End Date',
            'Customer End Date',
            'Estimated Hours',
            'Technologies',
            'Sales Person',
            'Parent Project',
        ];
    }

    /**
     * @param Project $project
     */
    public function map($project): array
    {
        $displayTimeline = $project->display_timeline;

        $serviceTypes = $project->serviceTypes->pluck('name')->filter()->implode(', ');

        $categories = collect($project->project_category_ids ?? [])
            ->map(fn ($id) => $this->categoriesMap[$id] ?? null)
            ->filter()
            ->implode(', ');

        $technologies = $project->technologies->pluck('name')->filter()->implode(', ');

        $priorityLabel = $this->priorities[$project->priority]['label'] ?? ucfirst((string) $project->priority);

        return [
            (string) ($project->project_code ?? '-'),
            $project->name ?? '-',
            (string) ($project->customer?->customer_code ?? '-'),
            $project->customer?->name ?? '-',
            ucfirst((string) ($project->project_flow ?? 'linear')),
            $priorityLabel,
            $project->projectStatus?->name ?? '-',
            $project->projectStage?->name ?? '-',
            $serviceTypes ?: '-',
            $categories ?: '-',
            $displayTimeline?->name ?? '-',
            AppServiceProvider::formatAppDate($displayTimeline?->start_date, '-'),
            AppServiceProvider::formatAppDate($displayTimeline?->end_date, '-'),
            AppServiceProvider::formatAppDate($project->customer_end_date, '-'),
            $this->formatEstimatedHours($displayTimeline?->estimated_time_seconds),
            $technologies ?: '-',
            $project->salesPerson?->name ?? '-',
            $project->parentProject?->name ?? '-',
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
            'C' => NumberFormat::FORMAT_TEXT,
            'O' => NumberFormat::FORMAT_GENERAL,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && in_array($cell->getColumn(), ['A', 'C'], true)) {
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
        $centeredColumns = ['A', 'C', 'E', 'F', 'G', 'H', 'L', 'M', 'N'];
        foreach ($centeredColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . max($highestRow, 2))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle('O2:O' . max($highestRow, 2))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $highestColumn . max($highestRow, 1));

        return [];
    }
}

