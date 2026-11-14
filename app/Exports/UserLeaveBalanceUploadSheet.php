<?php

namespace App\Exports;

use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserLeaveBalance;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class UserLeaveBalanceUploadSheet implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithColumnFormatting,
    WithEvents,
    WithColumnWidths
{
    protected int $year;

    protected array $rows = [];

    public function __construct()
    {
        $this->year = (int) now()->year;

        $users = User::query()
            ->where('delete_status', false)
            ->orderBy('email')
            ->get(['id', 'email']);

        $leaveTypes = LeaveType::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $balances = UserLeaveBalance::query()
            ->where('year', $this->year)
            ->get()
            ->keyBy(function ($balance) {
                return $balance->user_id . '_' . $balance->leave_type_id;
            });

        foreach ($users as $user) {
            foreach ($leaveTypes as $leaveType) {
                $balance = $balances->get(
                    $user->id . '_' . $leaveType->id
                );

                $this->rows[] = [
                    $user->email,
                    $leaveType->name,
                    $this->year,
                    $balance && $balance->is_carry_forward ? 'Yes' : 'No',
                    $balance ? ($balance->status ? 'Active' : 'Inactive') : 'Active',

                    $balance?->valid_from?->format('Y-m-d')
                        ?? "{$this->year}-01-01",

                    $balance?->valid_to?->format('Y-m-d')
                        ?? "{$this->year}-12-31",

                    $this->balanceValue($balance, 'yearly_entitlement'),
                    $this->balanceValue($balance, 'monthly_entitlement'),
                    $this->balanceValue($balance, 'opening_balance'),
                    $this->balanceValue($balance, 'current_balance'),
                    $this->balanceValue($balance, 'used_balance'),
                    $this->balanceValue($balance, 'paid_days_used'),
                    $this->balanceValue($balance, 'unpaid_days_used'),
                    $this->balanceValue($balance, 'cancelled_days_restored'),
                    $this->balanceValue($balance, 'carry_forward_balance'),
                ];
            }
        }
    }

    public function title(): string
    {
        return 'Leave Balance Upload';
    }

    public function headings(): array
    {
        return [
            'Employee Email',
            'Leave Type',
            'Year',
            'Is Carry Forward',
            'Status',
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
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => '0',
            'F' => 'yyyy-mm-dd',
            'G' => 'yyyy-mm-dd',

            'H' => NumberFormat::FORMAT_GENERAL,
            'I' => NumberFormat::FORMAT_GENERAL,
            'J' => NumberFormat::FORMAT_GENERAL,
            'K' => NumberFormat::FORMAT_GENERAL,
            'L' => NumberFormat::FORMAT_GENERAL,
            'M' => NumberFormat::FORMAT_GENERAL,
            'N' => NumberFormat::FORMAT_GENERAL,
            'O' => NumberFormat::FORMAT_GENERAL,
            'P' => NumberFormat::FORMAT_GENERAL,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 24,
            'C' => 12,
            'D' => 20,
            'E' => 17,
            'F' => 16,
            'G' => 16,
            'H' => 22,
            'I' => 22,
            'J' => 20,
            'K' => 20,
            'L' => 18,
            'M' => 20,
            'N' => 22,
            'O' => 27,
            'P' => 24,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastDataRow = count($this->rows) + 1;

                // Keep dropdowns available for future entries as well.
                $lastRow = max($lastDataRow, 500);

                // Freeze heading row and first two columns.
                $sheet->freezePane('C2');

                // Filters only for the first five columns.
                $sheet->setAutoFilter("A1:E{$lastDataRow}");

                // Excel zoom.
                $sheet->getSheetView()->setZoomScale(80);

                // Heading row height.
                $sheet->getRowDimension(1)->setRowHeight(42);

                // Heading formatting.
                $sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getDefaultRowDimension()->setRowHeight(22);

                // Explicitly write numeric balance values.
                // H:P contains numeric balance fields.
                for ($row = 2; $row <= $lastDataRow; $row++) {
                    foreach (range('H', 'P') as $column) {
                        $cell = $sheet->getCell("{$column}{$row}");

                        $value = $cell->getValue();

                        $numericValue = (
                            $value === null || $value === ''
                        ) ? 0 : (float) $value;

                        $cell->setValueExplicit(
                            $numericValue,
                            DataType::TYPE_NUMERIC
                        );

                        $cell->getStyle()
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_GENERAL);
                    }
                }

                // Data alignment.
                if ($lastDataRow >= 2) {
                    $sheet->getStyle("A2:P{$lastDataRow}")
                        ->getAlignment()
                        ->setVertical('center');

                    $sheet->getStyle("C2:P{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal('center');
                }

                // Dropdown validation.
                $dropdownOptions = [
                    'D' => [
                        'values' => 'Yes,No',
                        'error' => 'Please select Yes or No.',
                        'title' => 'Is Carry Forward',
                    ],
                    'E' => [
                        'values' => 'Active,Inactive',
                        'error' => 'Please select Active or Inactive.',
                        'title' => 'Status',
                    ],
                ];

                foreach ($dropdownOptions as $column => $options) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $validation = new DataValidation();

                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(
                            DataValidation::STYLE_STOP
                        );
                        $validation->setAllowBlank(false);

                        // False means the dropdown arrow is visible.
                        $validation->setShowDropDown(false);

                        // No input prompt when selecting the cell.
                        $validation->setShowInputMessage(false);

                        $validation->setShowErrorMessage(true);
                        $validation->setErrorTitle('Invalid Selection');
                        $validation->setError($options['error']);

                        $validation->setFormula1(
                            '"' . $options['values'] . '"'
                        );

                        $sheet->getCell("{$column}{$row}")
                            ->setDataValidation($validation);
                    }
                }
            },
        ];
    }

    private function balanceValue($balance, string $field): float
    {
        return (float) ($balance?->{$field} ?? 0);
    }
}
