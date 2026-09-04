<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserLeaveBalanceSampleExport implements FromArray, WithHeadings
{
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
        return [
            [
                'john@example.com',
                'Annual Leave',
                2026,
                '01-01-2026',
                '31-12-2026',
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
                'john@example.com',
                'Sick Leave',
                2026,
                '01-01-2026',
                '31-12-2026',
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
}