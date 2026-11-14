<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserLeaveBalanceInstructionsSheet implements
    FromArray,
    WithTitle,
    WithStyles,
    WithColumnWidths
{
    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        return [
            ['USER LEAVE BALANCE IMPORT - INSTRUCTIONS'],
            ['1. Use the Leave Balance Upload sheet to prepare your data.'],
            ['2. The upload sheet is automatically populated with active employees and active leave types.'],
            ['3. Existing leave balances for the current year are displayed where available.'],
            ['4. For new balances, the numeric balance fields are initially set to zero. Enter the correct values before importing.'],
            ['5. Employee Email must match an existing employee email address.'],
            ['6. Leave Type must match an existing leave type name.'],
            ['7. Year must contain a valid year.'],
            ['8. Valid From and Valid To must be valid dates in YYYY-MM-DD format.'],
            ['9. Valid To cannot be earlier than Valid From.'],
            ['10. Balance and entitlement fields must contain numeric values.'],
            ['11. Is Carry Forward must be Yes or No.'],
            ['12. Status must be Active or Inactive.'],
            ['13. To update an existing balance, retain the same Employee Email, Leave Type and Year.'],
            ['14. A matching Employee Email + Leave Type + Year updates the existing record. Otherwise, a new record is created.'],
            ['15. Do not rename or remove the column headings.'],
            ['16. Do not upload the Examples, Instructions or Column Guide sheets as data. The importer reads only Leave Balance Upload.'],
            ['17. Remove any example or test rows before importing.'],
            ['18. Blank rows are ignored during import.'],
            ['19. Save the workbook as XLSX before uploading.'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 110,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->getStyle('A1:A19')
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical('center');

        return [];
    }
}
