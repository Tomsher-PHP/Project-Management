<?php

namespace App\Imports;

use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserLeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class UserLeaveBalanceImport implements
    ToCollection,
    WithHeadingRow,
    WithMultipleSheets
{
    /**
     * Number of successfully created records.
     */
    public int $created = 0;

    /**
     * Number of successfully updated records.
     */
    public int $updated = 0;

    /**
     * Number of failed rows.
     */
    public int $failed = 0;

    /**
     * Import errors.
     */
    public array $errors = [];

    /**
     * Current logged-in user.
     */
    protected int $authUserId;

    public function __construct()
    {
        $this->authUserId = (int) auth()->id();
    }

    /**
     * Import only the main Leave Balance Upload sheet.
     *
     * Other sheets contain examples and instructions,
     * not actual import records.
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    /**
     * Process the uploaded spreadsheet.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {

            /*
             * Excel row number.
             *
             * Row 1 is the heading row, therefore
             * actual Excel data starts from row 2.
             */
            $rowNumber = $index + 2;

            /*
             * Convert the row to a normal array.
             */
            $data = $row->toArray();

            /*
             * Skip completely empty rows.
             */
            if ($this->isEmptyRow($data)) {
                continue;
            }

            try {

                DB::transaction(function () use ($data) {

                    /*
                     * Clean values.
                     */
                    $email = strtolower(
                        trim((string) ($data['employee_email'] ?? ''))
                    );

                    $leaveTypeName = trim(
                        (string) ($data['leave_type'] ?? '')
                    );

                    /*
                     * Basic validation.
                     */
                    if ($email === '') {
                        throw new \Exception(
                            'Employee Email is required.'
                        );
                    }

                    if ($leaveTypeName === '') {
                        throw new \Exception(
                            'Leave Type is required.'
                        );
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new \Exception(
                            'Invalid Employee Email.'
                        );
                    }

                    /*
                     * Find employee.
                     */
                    $user = User::query()
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->first();

                    if (!$user) {
                        throw new \Exception(
                            "Employee not found for email: {$email}"
                        );
                    }

                    /*
                     * Find leave type.
                     *
                     * Matching is case-insensitive.
                     */
                    $leaveType = LeaveType::query()
                        ->whereRaw(
                            'LOWER(name) = ?',
                            [strtolower($leaveTypeName)]
                        )
                        ->first();

                    if (!$leaveType) {
                        throw new \Exception(
                            "Leave Type not found: {$leaveTypeName}"
                        );
                    }

                    /*
                     * Year.
                     */
                    $year = $this->parseInteger(
                        $data['year'] ?? null
                    );

                    if (!$year) {
                        throw new \Exception(
                            'Year is required and must be a valid number.'
                        );
                    }

                    /*
                     * Dates.
                     */
                    $validFrom = $this->parseDate(
                        $data['valid_from'] ?? null
                    );

                    $validTo = $this->parseDate(
                        $data['valid_to'] ?? null
                    );

                    if (!$validFrom) {
                        throw new \Exception(
                            'Valid From is required and must be a valid date.'
                        );
                    }

                    if (!$validTo) {
                        throw new \Exception(
                            'Valid To is required and must be a valid date.'
                        );
                    }

                    if ($validTo->lt($validFrom)) {
                        throw new \Exception(
                            'Valid To cannot be before Valid From.'
                        );
                    }

                    /*
                     * Prepare balance data.
                     */
                    $balanceData = [
                        'user_id' => $user->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $year,

                        'valid_from' => $validFrom->toDateString(),
                        'valid_to' => $validTo->toDateString(),

                        'yearly_entitlement' => $this->decimal(
                            $data['yearly_entitlement'] ?? 0
                        ),

                        'monthly_entitlement' => $this->decimal(
                            $data['monthly_entitlement'] ?? 0
                        ),

                        'opening_balance' => $this->decimal(
                            $data['opening_balance'] ?? 0
                        ),

                        'current_balance' => $this->decimal(
                            $data['current_balance'] ?? 0
                        ),

                        'used_balance' => $this->decimal(
                            $data['used_balance'] ?? 0
                        ),

                        'paid_days_used' => $this->decimal(
                            $data['paid_days_used'] ?? 0
                        ),

                        'unpaid_days_used' => $this->decimal(
                            $data['unpaid_days_used'] ?? 0
                        ),

                        'cancelled_days_restored' => $this->decimal(
                            $data['cancelled_days_restored'] ?? 0
                        ),

                        'carry_forward_balance' => $this->decimal(
                            $data['carry_forward_balance'] ?? 0
                        ),

                        'is_carry_forward' => $this->parseBoolean(
                            $data['is_carry_forward'] ?? false
                        ),

                        'status' => $this->parseBoolean(
                            $data['status'] ?? true
                        ),

                        'updated_by' => $this->authUserId,
                    ];

                    /*
                     * Find existing balance.
                     *
                     * One user + one leave type + one year
                     * should represent one balance record.
                     */
                    $balance = UserLeaveBalance::query()
                        ->where('user_id', $user->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $year)
                        ->first();

                    if ($balance) {

                        /*
                         * Existing balance:
                         * update it.
                         */
                        $balance->update($balanceData);

                        $this->updated++;

                    } else {

                        /*
                         * New balance:
                         * create it.
                         */
                        $balanceData['created_by'] = $this->authUserId;

                        UserLeaveBalance::create($balanceData);

                        $this->created++;
                    }
                });

            } catch (\Throwable $e) {

                /*
                 * DB::transaction() automatically rolls back
                 * if an exception occurs.
                 */
                $this->failed++;

                $this->errors[] = [
                    'row' => $rowNumber,
                    'employee_email' => $data['employee_email'] ?? '',
                    'leave_type' => $data['leave_type'] ?? '',
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Determine whether the entire row is empty.
     *
     * Completely blank rows are ignored during import.
     */
    protected function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {

            if ($value instanceof \DateTimeInterface) {
                return false;
            }

            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert value to decimal.
     */
    protected function decimal($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (!is_numeric($value)) {
            throw new \Exception(
                "Invalid numeric value: {$value}"
            );
        }

        return round((float) $value, 2);
    }

    /**
     * Convert value to integer.
     */
    protected function parseInteger($value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Parse Excel date.
     *
     * Always returns Carbon so Carbon methods such as
     * lt(), gt(), lte(), gte(), etc. can safely be used.
     */
    protected function parseDate($value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {

            /*
             * Excel serial date.
             */
            if (is_numeric($value)) {

                $dateTime = ExcelDate::excelToDateTimeObject(
                    (float) $value
                );

                return Carbon::instance($dateTime);
            }

            /*
             * If PhpSpreadsheet already returned a DateTime object,
             * convert it to Carbon.
             */
            if ($value instanceof \DateTimeInterface) {

                return Carbon::instance(
                    \DateTime::createFromInterface($value)
                );
            }

            /*
             * Normal string date.
             */
            return Carbon::parse($value);

        } catch (\Throwable $e) {

            return null;
        }
    }

    /**
     * Convert Yes/No, True/False, 1/0 to boolean.
     */
    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'y',
                'active',
                'enabled',
            ],
            true
        );
    }
}
