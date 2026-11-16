<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserLeaveBalanceColumnGuideSheet implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths
{
    public function title(): string
    {
        return 'Column Guide';
    }

    public function headings(): array
    {
        return [
            'Column Name',
            'Description',
            'Expected Value',
        ];
    }

    public function array(): array
    {
        return [
            ['Employee Email', 'Employee email address.', 'Existing employee email'],
            ['Leave Type', 'Name of the leave type.', 'Existing leave type name'],
            ['Year', 'Leave balance year.', 'Valid year'],
            ['Valid From', 'Start date of the leave balance validity.', 'YYYY-MM-DD'],
            ['Valid To', 'End date of the leave balance validity.', 'YYYY-MM-DD'],
            ['Yearly Entitlement', 'Total leave entitlement for the year.', 'Numeric value'],
            ['Monthly Entitlement', 'Monthly leave entitlement.', 'Numeric value'],
            ['Opening Balance', 'Leave balance at the beginning of the period.', 'Numeric value'],
            ['Current Balance', 'Current available leave balance.', 'Numeric value'],
            ['Used Balance', 'Total leave used.', 'Numeric value'],
            ['Paid Days Used', 'Total paid leave days used.', 'Numeric value'],
            ['Unpaid Days Used', 'Total unpaid leave days used.', 'Numeric value'],
            ['Cancelled Days Restored', 'Leave days restored after cancellation.', 'Numeric value'],
            ['Carry Forward Balance', 'Balance carried forward from the previous period.', 'Numeric value'],
            ['Is Carry Forward', 'Indicates whether the balance is carried forward.', 'Yes / No'],
            ['Status', 'Indicates whether the balance is active.', 'Active / Inactive'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 65,
            'C' => 30,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }
}
