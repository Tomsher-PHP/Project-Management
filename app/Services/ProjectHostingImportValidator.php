<?php

namespace App\Services;

use App\Models\HostingBillingCycle;
use App\Models\HostingEnvironment;
use App\Models\HostingProvider;
use App\Models\HostingService;
use App\Models\HostingServiceStatus;
use App\Models\Project;
use App\Models\ProjectHostingRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectHostingImportValidator
{
    public const WORKSHEET_NAME = 'Hosting Upload';

    public const EXPECTED_HEADERS = [
        'A' => 'Project Code',
        'B' => 'Service Name',
        'C' => 'Provider',
        'D' => 'Service Type',
        'E' => 'Status',
        'F' => 'Ownership Type',
        'G' => 'Domain',
        'H' => 'Environment',
        'I' => 'Start Date',
        'J' => 'Expiry Date',
        'K' => 'Next Renewal Date',
        'L' => 'Last Renewed Date',
        'M' => 'Billing Cycle',
        'N' => 'Billing Amount',
        'O' => 'Currency',
        'P' => 'Reference',
        'Q' => 'Description',
        'R' => 'Notes',
    ];

    public const MAX_ROWS_LIMIT = 5000;

    /**
     * Validate an uploaded Excel workbook for Project Hosting records.
     * Read-only validation: absolutely no database writes are performed.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file
     * @param  \App\Models\User  $user
     * @param  array<string, mixed>|null  $preloadedMaster
     * @return array<string, mixed>
     */
    public function validate(UploadedFile|string $file, User $user, ?array $preloadedMaster = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalFilename = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);

        $result = [
            'is_valid_structure' => true,
            'filename' => $originalFilename,
            'structure_error' => null,
            'total_rows' => 0,
            'valid_rows_count' => 0,
            'invalid_rows_count' => 0,
            'is_all_valid' => false,
            'rows' => [],
        ];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = 'The uploaded file could not be read.';
            return $result;
        }

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($filePath);
        } catch (\Throwable $e) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = 'Unable to parse Excel file. Please ensure it is a valid .xlsx workbook.';
            return $result;
        }

        $sheet = $spreadsheet->getSheetByName(self::WORKSHEET_NAME);
        if (!$sheet) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = "The workbook must contain a '" . self::WORKSHEET_NAME . "' worksheet.";
            return $result;
        }

        // Validate column headings on Row 1
        $headerError = $this->validateHeaders($sheet);
        if ($headerError !== null) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = $headerError;
            return $result;
        }

        // Preload master lookup data efficiently in a single query each (or use injected master data)
        $masterData = $preloadedMaster ?? $this->loadMasterData($user);

        $highestRow = min((int) $sheet->getHighestRow(), self::MAX_ROWS_LIMIT + 1);

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            if ($this->isRowBlank($sheet, $rowNumber)) {
                continue;
            }

            $validatedRow = $this->validateRow($sheet, $rowNumber, $masterData, $user);

            $result['total_rows']++;
            if ($validatedRow['status'] === 'Valid') {
                $result['valid_rows_count']++;
            } else {
                $result['invalid_rows_count']++;
            }

            $result['rows'][] = $validatedRow;
        }

        $result['is_all_valid'] = ($result['total_rows'] > 0 && $result['invalid_rows_count'] === 0);

        return $result;
    }

    /**
     * Validate exact headings on Row 1.
     */
    protected function validateHeaders(Worksheet $sheet): ?string
    {
        foreach (self::EXPECTED_HEADERS as $col => $expected) {
            $cellValue = $sheet->getCell($col . '1')->getValue();
            $actual = is_string($cellValue) ? trim($cellValue) : (string) $cellValue;

            if ($actual !== $expected) {
                return "Invalid column header at column {$col}: expected '{$expected}', found '{$actual}'.";
            }
        }

        return null;
    }

    /**
     * Check if a data row is entirely blank across columns A to R.
     */
    protected function isRowBlank(Worksheet $sheet, int $rowNumber): bool
    {
        foreach (array_keys(self::EXPECTED_HEADERS) as $col) {
            $val = $sheet->getCell($col . $rowNumber)->getValue();
            if ($val !== null && trim((string) $val) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Preload all eligible master data records once for bulk row validation.
     *
     * @param  \App\Models\User  $user
     * @return array<string, mixed>
     */
    protected function loadMasterData(User $user): array
    {
        // 1. Projects accessible by user
        $accessibleProjects = Project::accessibleBy($user)
            ->get(['id', 'project_code', 'name', 'is_system', 'added_by'])
            ->keyBy(fn ($p) => trim($p->project_code));

        // 2. Active, non-deleted Hosting Providers
        $providers = HostingProvider::where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code']);

        // 3. Active, non-deleted Hosting Services
        $services = HostingService::where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code']);

        // 4. Active, non-deleted Hosting Service Statuses
        $statuses = HostingServiceStatus::where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code', 'is_default']);

        // Default status: prefer is_default === true, fallback to name Active
        $defaultStatus = $statuses->firstWhere('is_default', true)
            ?? $statuses->first(fn ($s) => mb_strtolower(trim($s->name)) === 'active');

        // 5. Active, non-deleted Hosting Environments
        $environments = HostingEnvironment::where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code']);

        // 6. Active, non-deleted Hosting Billing Cycles
        $billingCycles = HostingBillingCycle::where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code']);

        return $this->buildMasterData(
            $accessibleProjects,
            $providers,
            $services,
            $statuses,
            $environments,
            $billingCycles,
            $defaultStatus
        );
    }

    /**
     * Build indexed master data structure from collections or arrays.
     *
     * @param  array|\Illuminate\Support\Collection  $accessibleProjects
     * @param  array|\Illuminate\Support\Collection  $providers
     * @param  array|\Illuminate\Support\Collection  $services
     * @param  array|\Illuminate\Support\Collection  $statuses
     * @param  array|\Illuminate\Support\Collection  $environments
     * @param  array|\Illuminate\Support\Collection  $billingCycles
     * @param  mixed  $defaultStatus
     * @return array<string, mixed>
     */
    public function buildMasterData(
        array|Collection $accessibleProjects,
        array|Collection $providers,
        array|Collection $services,
        array|Collection $statuses,
        array|Collection $environments = [],
        array|Collection $billingCycles = [],
        mixed $defaultStatus = null
    ): array {
        $statusesCol = collect($statuses);
        $resolvedDefault = $defaultStatus
            ?? ($statusesCol->firstWhere('is_default', true)
                ?? $statusesCol->first(fn ($s) => mb_strtolower(trim($s->name ?? '')) === 'active'));

        return [
            'accessible_projects' => collect($accessibleProjects)->keyBy(fn ($p) => trim($p->project_code ?? '')),
            'providers' => $this->indexByName(collect($providers)),
            'services' => $this->indexByName(collect($services)),
            'statuses' => $this->indexByName($statusesCol),
            'default_status' => $resolvedDefault,
            'environments' => $this->indexByName(collect($environments)),
            'billing_cycles' => $this->indexByName(collect($billingCycles)),
        ];
    }

    /**
     * Index a collection by lowercased, trimmed name, detecting ambiguous duplicates safely.
     *
     * @param  array|\Illuminate\Support\Collection  $items
     * @return array<string, mixed>
     */
    public function indexByName(array|Collection $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $name = $item->name;
            if ($name === null) {
                continue;
            }
            $normalized = mb_strtolower(trim($name));
            if (!isset($indexed[$normalized])) {
                $indexed[$normalized] = $item;
            } else {
                $indexed[$normalized] = 'AMBIGUOUS';
            }
        }

        return $indexed;
    }

    /**
     * Validate an individual data row against schema constraints and business rules.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     * @param  int  $rowNumber
     * @param  array<string, mixed>  $master
     * @param  \App\Models\User  $user
     * @return array<string, mixed>
     */
    protected function validateRow(Worksheet $sheet, int $rowNumber, array $master, User $user): array
    {
        $errors = [];
        $data = [];

        // Helper to get trimmed raw cell value
        $getVal = function (string $col) use ($sheet, $rowNumber): ?string {
            $val = $sheet->getCell($col . $rowNumber)->getValue();
            if ($val === null) {
                return null;
            }
            $trimmed = trim((string) $val);
            return $trimmed === '' ? null : $trimmed;
        };

        // Raw values map for reporting and re-import exports
        $rawValues = [];
        foreach (self::EXPECTED_HEADERS as $col => $header) {
            $cell = $sheet->getCell($col . $rowNumber);
            $val = $cell->getValue();
            if ($val instanceof \DateTimeInterface) {
                $rawValues[$header] = $val->format('Y-m-d');
            } elseif (in_array($col, ['I', 'J', 'K', 'L'], true) && is_numeric($val) && (float) $val >= 1 && (float) $val <= 2958465) {
                try {
                    $dt = ExcelDate::excelToDateTimeObject((float) $val);
                    $rawValues[$header] = $dt->format('Y-m-d');
                } catch (\Throwable) {
                    $rawValues[$header] = (string) $val;
                }
            } else {
                $rawValues[$header] = ($val !== null) ? (string) $val : '';
            }
        }

        // Raw values
        $rawProjectCode = $getVal('A');
        $rawServiceName = $getVal('B');
        $rawProvider = $getVal('C');
        $rawServiceType = $getVal('D');
        $rawStatus = $getVal('E');
        $rawOwnership = $getVal('F');
        $rawDomain = $getVal('G');
        $rawEnvironment = $getVal('H');
        $rawBillingCycle = $getVal('M');
        $rawBillingAmount = $getVal('N');
        $rawCurrency = $getVal('O');
        $rawReference = $getVal('P');
        $rawDescription = $getVal('Q');
        $rawNotes = $getVal('R');

        // A. Project Code
        if ($rawProjectCode === null) {
            $errors[] = 'Project Code: Project Code is required.';
        } else {
            if (!isset($master['accessible_projects'][$rawProjectCode])) {
                $errors[] = 'Project Code: No matching project was found.';
            } else {
                $project = $master['accessible_projects'][$rawProjectCode];
                if (!$user->is_super_admin && !$user->can('update', $project)) {
                    $errors[] = 'Project Code: No matching project was found.';
                } else {
                    $data['project_id'] = $project->id;
                    $data['project_code'] = $rawProjectCode;
                }
            }
        }

        // B. Service Name
        if ($rawServiceName === null) {
            $errors[] = 'Service Name: Service Name is required.';
        } elseif (mb_strlen($rawServiceName) > 255) {
            $errors[] = 'Service Name: Service Name cannot exceed 255 characters.';
        } else {
            $data['name'] = $rawServiceName;
        }

        // C. Provider
        if ($rawProvider === null) {
            $errors[] = 'Provider: Provider is required.';
        } else {
            $normalizedProvider = mb_strtolower($rawProvider);
            if (!isset($master['providers'][$normalizedProvider])) {
                $errors[] = "Provider: The selected provider '{$rawProvider}' is not available.";
            } elseif ($master['providers'][$normalizedProvider] === 'AMBIGUOUS') {
                $errors[] = "Provider: Multiple records matched the provider '{$rawProvider}'.";
            } else {
                $data['hosting_provider_id'] = $master['providers'][$normalizedProvider]->id;
            }
        }

        // D. Service Type
        if ($rawServiceType === null) {
            $errors[] = 'Service Type: Service Type is required.';
        } else {
            $normalizedService = mb_strtolower($rawServiceType);
            if (!isset($master['services'][$normalizedService])) {
                $errors[] = "Service Type: The selected service type '{$rawServiceType}' is not available.";
            } elseif ($master['services'][$normalizedService] === 'AMBIGUOUS') {
                $errors[] = "Service Type: Multiple records matched the service type '{$rawServiceType}'.";
            } else {
                $data['hosting_service_id'] = $master['services'][$normalizedService]->id;
            }
        }

        // E. Status
        if ($rawStatus === null) {
            if ($master['default_status']) {
                $data['hosting_service_status_id'] = $master['default_status']->id;
            } else {
                $errors[] = "Status: Default status 'Active' could not be resolved from system configuration.";
            }
        } else {
            $normalizedStatus = mb_strtolower($rawStatus);
            if (!isset($master['statuses'][$normalizedStatus])) {
                $errors[] = "Status: The selected status '{$rawStatus}' is not available.";
            } elseif ($master['statuses'][$normalizedStatus] === 'AMBIGUOUS') {
                $errors[] = "Status: Multiple records matched the status '{$rawStatus}'.";
            } else {
                $data['hosting_service_status_id'] = $master['statuses'][$normalizedStatus]->id;
            }
        }

        // F. Ownership Type
        if ($rawOwnership === null) {
            $data['ownership_type'] = ProjectHostingRecord::OWNERSHIP_COMPANY;
        } else {
            $normalizedOwnership = mb_strtolower($rawOwnership);
            if (in_array($normalizedOwnership, [ProjectHostingRecord::OWNERSHIP_COMPANY, ProjectHostingRecord::OWNERSHIP_CUSTOMER], true)) {
                $data['ownership_type'] = $normalizedOwnership;
            } else {
                $errors[] = "Ownership Type: Must be either 'company' or 'customer'.";
            }
        }

        // G. Domain
        if ($rawDomain !== null) {
            if (mb_strlen($rawDomain) > 255) {
                $errors[] = 'Domain: Domain cannot exceed 255 characters.';
            } else {
                $data['domain'] = $rawDomain;
            }
        }

        // H. Environment
        if ($rawEnvironment !== null) {
            $normalizedEnv = mb_strtolower($rawEnvironment);
            if (!isset($master['environments'][$normalizedEnv])) {
                $errors[] = "Environment: The selected environment '{$rawEnvironment}' is not available.";
            } elseif ($master['environments'][$normalizedEnv] === 'AMBIGUOUS') {
                $errors[] = "Environment: Multiple records matched the environment '{$rawEnvironment}'.";
            } else {
                $data['hosting_environment_id'] = $master['environments'][$normalizedEnv]->id;
            }
        }

        // I. Dates
        $startDate = $this->parseAndValidateDate($sheet->getCell('I' . $rowNumber), 'Start Date', $errors);
        $expiryDate = $this->parseAndValidateDate($sheet->getCell('J' . $rowNumber), 'Expiry Date', $errors);
        $nextRenewalDate = $this->parseAndValidateDate($sheet->getCell('K' . $rowNumber), 'Next Renewal Date', $errors);
        $lastRenewedDate = $this->parseAndValidateDate($sheet->getCell('L' . $rowNumber), 'Last Renewed Date', $errors);

        if ($startDate !== null) {
            $data['start_date'] = $startDate;
        }
        if ($expiryDate !== null) {
            $data['expiry_date'] = $expiryDate;
        }
        if ($nextRenewalDate !== null) {
            $data['next_renewal_date'] = $nextRenewalDate;
        }
        if ($lastRenewedDate !== null) {
            $data['last_renewed_date'] = $lastRenewedDate;
        }

        // Expiry Date >= Start Date if both present
        if ($startDate !== null && $expiryDate !== null && $expiryDate < $startDate) {
            $errors[] = 'Expiry Date: Expiry Date must be on or after Start Date.';
        }

        // J. Billing Cycle
        if ($rawBillingCycle !== null) {
            $normalizedCycle = mb_strtolower($rawBillingCycle);
            if (!isset($master['billing_cycles'][$normalizedCycle])) {
                $errors[] = "Billing Cycle: The selected billing cycle '{$rawBillingCycle}' is not available.";
            } elseif ($master['billing_cycles'][$normalizedCycle] === 'AMBIGUOUS') {
                $errors[] = "Billing Cycle: Multiple records matched the billing cycle '{$rawBillingCycle}'.";
            } else {
                $data['hosting_billing_cycle_id'] = $master['billing_cycles'][$normalizedCycle]->id;
            }
        }

        // K. Billing Amount
        if ($rawBillingAmount !== null) {
            if (!is_numeric($rawBillingAmount)) {
                $errors[] = 'Billing Amount: The supplied billing amount must be a valid number.';
            } else {
                $amount = (float) $rawBillingAmount;
                if ($amount < 0 || $amount > 9999999999.99) {
                    $errors[] = 'Billing Amount: Billing Amount must be between 0 and 9,999,999,999.99.';
                } else {
                    $data['billing_amount'] = number_format($amount, 2, '.', '');
                }
            }
        }

        // L. Currency
        if ($rawCurrency !== null) {
            if (!preg_match('/^[a-zA-Z]{3}$/', $rawCurrency)) {
                $errors[] = 'Currency: Currency must be a 3-letter currency code (e.g. USD, AED).';
            } else {
                $data['currency_code'] = strtoupper($rawCurrency);
            }
        }

        // M. Reference
        if ($rawReference !== null) {
            if (mb_strlen($rawReference) > 255) {
                $errors[] = 'Reference: Reference cannot exceed 255 characters.';
            } else {
                $data['reference'] = $rawReference;
            }
        }

        // N. Description & Notes
        if ($rawDescription !== null) {
            $data['description'] = $rawDescription;
        }
        if ($rawNotes !== null) {
            $data['notes'] = $rawNotes;
        }

        return [
            'row_number' => $rowNumber,
            'project_code' => $rawProjectCode ?? '-',
            'service_name' => $rawServiceName ?? '-',
            'status' => count($errors) === 0 ? 'Valid' : 'Invalid',
            'errors' => $errors,
            'data' => $data,
            'raw_values' => $rawValues,
        ];
    }

    /**
     * Parse and validate a date cell value.
     * Handles Excel numeric date serials, DateTimeInterface, and standard calendar date strings.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Cell\Cell  $cell
     * @param  string  $columnName
     * @param  array<int, string>  &$errors
     * @return string|null Normalized Y-m-d string or null
     */
    protected function parseAndValidateDate($cell, string $columnName, array &$errors): ?string
    {
        if ($cell === null) {
            return null;
        }

        $value = $cell->getValue();
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        // 1. PhpSpreadsheet DateTime formatting detection
        if (ExcelDate::isDateTime($cell)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                $errors[] = "{$columnName}: The supplied date is invalid.";
                return null;
            }
        }

        // 2. Numeric date serial (between 1 = 1900-01-01 and 2958465 = 9999-12-31)
        if (is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                $errors[] = "{$columnName}: The supplied date is invalid.";
                return null;
            }
        }

        // 3. String representation
        if (is_string($value)) {
            // ISO format YYYY-MM-DD or YYYY/MM/DD
            if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $value, $matches)) {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
                $day = (int) $matches[3];
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
                $errors[] = "{$columnName}: The supplied date is invalid.";
                return null;
            }

            // Common DD-MM-YYYY or DD/MM/YYYY
            if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $value, $matches)) {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
                $errors[] = "{$columnName}: The supplied date is invalid.";
                return null;
            }

            $errors[] = "{$columnName}: The supplied date is invalid.";
            return null;
        }

        $errors[] = "{$columnName}: The supplied date is invalid.";
        return null;
    }
}
