<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserDetailsExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting,
    WithCustomValueBinder,
    WithStrictNullComparison,
    WithEvents
{
    protected const MIN_CONTACT_GROUPS = 2;

    protected int $emergencyContactGroups;

    protected int $nativeContactGroups;

    protected int $serialNumber = 0;

    /**
     * @var array<int, string>
     */
    protected array $textColumnLetters = [];

    public function __construct(
        protected Collection $users
    ) {
        $this->emergencyContactGroups = $this->resolveMaxContactGroups('emergency_contacts');
        $this->nativeContactGroups = $this->resolveMaxContactGroups('native_contacts');
        $this->textColumnLetters = $this->resolveTextColumnLetters();
    }

    public function title(): string
    {
        return 'User Details';
    }

    public function collection(): Collection
    {
        return $this->users;
    }

    public function headings(): array
    {
        $headings = [
            'Sl. No.',
            'Employee ID',
            'Name',
            'Email',
            'Gender',
            'Date of Birth',
            'Phone Number',
            'WhatsApp Number',
            'Status',
            'Department',
            'Designation',
            'Reporting To',
            'Manager',
            'Joining Date',
            'Leaving Date',
            'Marital Status',
            'Number of Children',
            'Favourite Colour',
            'Residence Address',
            'Native Address',
        ];

        for ($i = 1; $i <= $this->emergencyContactGroups; $i++) {
            $headings[] = "Emergency Contact {$i} - Name";
            $headings[] = "Emergency Contact {$i} - Number";
            $headings[] = "Emergency Contact {$i} - Relation";
        }

        for ($i = 1; $i <= $this->nativeContactGroups; $i++) {
            $headings[] = "Native Contact {$i} - Name";
            $headings[] = "Native Contact {$i} - Number";
            $headings[] = "Native Contact {$i} - Relation";
        }

        $headings[] = 'Casual Shirt Size';
        $headings[] = 'Formal Shirt Size';
        $headings[] = 'Casual Shoe Size';
        $headings[] = 'Formal Shoe Size';

        return $headings;
    }

    /**
     * @param  User  $user
     */
    public function map($user): array
    {
        $this->serialNumber++;
        $details = $user->details;

        $row = [
            $this->serialNumber,
            $this->stringifyValue($details?->employee_id),
            $this->stringifyValue($user->name),
            $this->stringifyValue($user->email),
            $this->formatGender($details?->gender),
            $this->formatDate($details?->dob),
            $this->stringifyValue($details?->phone),
            $this->stringifyValue($details?->whatsapp),
            $user->is_active ? 'Active' : 'Inactive',
            $this->stringifyValue($details?->department?->name),
            $this->stringifyValue($details?->designation?->name),
            $this->stringifyValue($details?->reporter?->name),
            $this->stringifyValue($details?->manager?->name),
            $this->formatDate($details?->joining_date),
            $this->formatDate($details?->leaving_date),
            $this->stringifyValue($details?->marital_status),
            $this->formatChildrenCount($details?->children_count),
            $this->stringifyValue($details?->favorite_color),
            $this->stringifyValue($details?->address),
            $this->stringifyValue($details?->native_address),
        ];

        $emergencyContacts = $this->normalizeContacts($details?->emergency_contacts);
        for ($i = 0; $i < $this->emergencyContactGroups; $i++) {
            $contact = $emergencyContacts[$i] ?? [];
            $row[] = $this->stringifyValue($contact['name'] ?? null);
            $row[] = $this->stringifyValue($contact['number'] ?? null);
            $row[] = $this->stringifyValue($contact['relation'] ?? null);
        }

        $nativeContacts = $this->normalizeContacts($details?->native_contacts);
        for ($i = 0; $i < $this->nativeContactGroups; $i++) {
            $contact = $nativeContacts[$i] ?? [];
            $row[] = $this->stringifyValue($contact['name'] ?? null);
            $row[] = $this->stringifyValue($contact['number'] ?? null);
            $row[] = $this->stringifyValue($contact['relation'] ?? null);
        }

        $shirtSizes = $this->normalizeKeyValueMap($details?->shirt_sizes);
        $shoeSizes = $this->normalizeKeyValueMap($details?->shoe_sizes);

        $row[] = $this->stringifyValue($shirtSizes['casual'] ?? null);
        $row[] = $this->stringifyValue($shirtSizes['formal'] ?? null);
        $row[] = $this->stringifyValue($shoeSizes['casual'] ?? null);
        $row[] = $this->stringifyValue($shoeSizes['formal'] ?? null);

        return $row;
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->textColumnLetters as $columnLetter) {
            $formats[$columnLetter] = NumberFormat::FORMAT_TEXT;
        }

        return $formats;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

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

        $sheet->getRowDimension(1)->setRowHeight(24);

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
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ]);

            // Wrap text for Residence Address (S) and Native Address (T)
            $sheet->getStyle('S2:T' . $highestRow)
                ->getAlignment()
                ->setWrapText(true);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:' . $highestColumn . '1');

                foreach (['S', 'T'] as $addressColumn) {
                    $sheet->getColumnDimension($addressColumn)->setAutoSize(false);
                    $sheet->getColumnDimension($addressColumn)->setWidth(35);
                }
            },
        ];
    }

    protected function resolveMaxContactGroups(string $field): int
    {
        $maxCount = $this->users->reduce(function (int $carry, User $user) use ($field): int {
            $contacts = $this->normalizeContacts($user->details?->{$field});

            return max($carry, count($contacts));
        }, 0);

        return max($maxCount, self::MIN_CONTACT_GROUPS);
    }

    /**
     * @return array<int, string>
     */
    protected function resolveTextColumnLetters(): array
    {
        // Column indexes (1-based): Employee ID (2), Phone Number (7), WhatsApp Number (8)
        $indexes = [2, 7, 8];

        $emergencyStartIndex = 21; // First Emergency Contact group starts at column U (21)
        for ($i = 0; $i < $this->emergencyContactGroups; $i++) {
            $indexes[] = $emergencyStartIndex + ($i * 3) + 1; // Number column
        }

        $nativeStartIndex = $emergencyStartIndex + ($this->emergencyContactGroups * 3);
        for ($i = 0; $i < $this->nativeContactGroups; $i++) {
            $indexes[] = $nativeStartIndex + ($i * 3) + 1; // Number column
        }

        return array_map(
            fn (int $index): string => Coordinate::stringFromColumnIndex($index),
            $indexes
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeContacts(mixed $value): array
    {
        $decoded = $this->decodeJson($value);
        if ($decoded === []) {
            return [];
        }

        if (
            array_key_exists('name', $decoded) ||
            array_key_exists('number', $decoded) ||
            array_key_exists('relation', $decoded)
        ) {
            return [$decoded];
        }

        return array_values(array_filter(
            $decoded,
            fn (mixed $item): bool => is_array($item)
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeKeyValueMap(mixed $value): array
    {
        return $this->decodeJson($value);
    }

    /**
     * @return array<mixed>
     */
    protected function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || $trimmed === 'null') {
                return [];
            }

            $decoded = json_decode($trimmed, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function formatDate(mixed $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($date)->format('d-m-Y');
        } catch (\Throwable) {
            return '';
        }
    }

    protected function formatGender(?string $gender): string
    {
        if ($gender === null || trim($gender) === '') {
            return '';
        }

        return ucfirst(strtolower(trim($gender)));
    }

    protected function formatChildrenCount(mixed $count): int|string
    {
        if ($count === null || $count === '') {
            return '';
        }

        return is_numeric($count) ? (int) $count : '';
    }

    protected function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
