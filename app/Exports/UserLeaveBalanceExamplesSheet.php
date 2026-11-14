<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserLeaveBalanceExamplesSheet implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths
{
    public function title(): string
    {
        return 'Examples';
    }

    public function headings(): array
    {
        return [
            'Employee Email',
            'Leave Type',
            'Year',
            'Valid From',
            'Valid To',
            'Yearly Entitlement',
            'Monthly Entitlement',
            'Opening Balance',
            'Current Balance',
            'Used Balance',
            'Paid Days Used',
            'Unpaid Days Used',
            'Cancelled Days Restored',
            'Carry Forward Balance',
            'Is Carry Forward',
            'Status',
        ];
    }

    public function array(): array
    {
        $year = now()->year;

        return [
            [
                'employee@example.com',
                'Annual Leave',
                $year,
                "{$year}-01-01",
                "{$year}-12-31",
                30,
                2.5,
                30,
                25,
                5,
                5,
                0,
                0,
                0,
                'No',
                'Active',
            ],
            [
                'employee@example.com',
                'Sick Leave',
                $year,
                "{$year}-01-01",
                "{$year}-12-31",
                12,
                1,
                12,
                10,
                2,
                2,
                0,
                0,
                0,
                'No',
                'Active',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

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
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => 'center',
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35, // Employee Email
            'B' => 25, // Leave Type
            'C' => 12, // Year
            'D' => 16, // Valid From
            'E' => 16, // Valid To
            'F' => 22, // Yearly Entitlement
            'G' => 22, // Monthly Entitlement
            'H' => 20, // Opening Balance
            'I' => 20, // Current Balance
            'J' => 18, // Used Balance
            'K' => 20, // Paid Days Used
            'L' => 22, // Unpaid Days Used
            'M' => 27, // Cancelled Days Restored
            'N' => 24, // Carry Forward Balance
            'O' => 20, // Is Carry Forward
            'P' => 17, // Status
        ];
    }
}
