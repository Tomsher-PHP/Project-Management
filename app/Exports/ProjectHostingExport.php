<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\ProjectHostingRecord;
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

class ProjectHostingExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting,
    WithCustomValueBinder
{
    public function __construct(
        protected Project $project,
        protected Collection $records
    ) {}

    public function title(): string
    {
        return 'Project Hosting';
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Project Code',
            'Project Name',
            'Service Name',
            'Domain',
            'Reference',
            'Provider',
            'Service Type',
            'Environment',
            'Ownership',
            'Status',
            'Billing Cycle',
            'Billing Amount',
            'Currency',
            'Start Date',
            'Expiry Date',
            'Next Renewal Date',
            'Last Renewed Date',
            'Description',
            'Notes',
            'Created By',
            'Updated By',
        ];
    }

    /**
     * @param ProjectHostingRecord $record
     */
    public function map($record): array
    {
        $ownershipLabel = ProjectHostingRecord::OWNERSHIP_TYPES[$record->ownership_type]
            ?? ucfirst((string) ($record->ownership_type ?? '-'));

        return [
            (string) ($this->project->project_code ?? '-'),
            $this->project->name ?? '-',
            $record->name ?? '-',
            $record->domain ?: '-',
            (string) ($record->reference ?: '-'),
            $record->hostingProvider?->name ?? '-',
            $record->hostingService?->name ?? '-',
            $record->hostingEnvironment?->name ?? '-',
            $ownershipLabel,
            $record->hostingServiceStatus?->name ?? '-',
            $record->hostingBillingCycle?->name ?? '-',
            $record->billing_amount !== null ? (float) $record->billing_amount : null,
            $record->currency_code ?: '-',
            AppServiceProvider::formatAppDate($record->start_date, '-'),
            AppServiceProvider::formatAppDate($record->expiry_date, '-'),
            AppServiceProvider::formatAppDate($record->next_renewal_date, '-'),
            AppServiceProvider::formatAppDate($record->last_renewed_date, '-'),
            $record->description ?: '-',
            $record->notes ?: '-',
            $record->creator?->name ?? '-',
            $record->updater?->name ?? '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && in_array($cell->getColumn(), ['A', 'E'], true)) {
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

        $centeredColumns = ['A', 'E', 'H', 'I', 'J', 'K', 'M', 'N', 'O', 'P', 'Q'];
        foreach ($centeredColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . max($highestRow, 2))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle('L2:L' . max($highestRow, 2))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $highestColumn . max($highestRow, 1));

        return [];
    }
}

