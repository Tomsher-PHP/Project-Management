<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\ServiceType;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectBulkImportValidator
{
    public const WORKSHEET_NAME = 'Projects Upload';

    public const MAX_FILE_SIZE_BYTES = 20 * 1024 * 1024; // 20 MB

    public const MAX_ROWS_LIMIT = 5000;

    public const EXPECTED_HEADERS = [
        'A' => 'Project Code',
        'B' => 'Project Name',
        'C' => 'Customer Code',
        'D' => 'Project Flow',
        'E' => 'Priority',
        'F' => 'Project Status Code',
        'G' => 'Project Stage Code',
        'H' => 'Service Type Codes',
        'I' => 'Category Codes',
        'J' => 'Timeline Name',
        'K' => 'Start Date',
        'L' => 'End Date',
        'M' => 'Customer End Date',
        'N' => 'Estimated Hours',
        'O' => 'Customer Estimated Hours',
        'P' => 'Technologies',
        'Q' => 'Sales Person Email',
        'R' => 'Domain',
        'S' => 'Default Billable',
    ];

    public function __construct(
        protected ?UserService $userService = null
    ) {
        $this->userService = $userService ?? app(UserService::class);
    }

    /**
     * Validate an uploaded Excel workbook for Bulk Projects Import.
     * Read-only validation: no database writes are performed.
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

        if (! $filePath || ! file_exists($filePath) || ! is_readable($filePath)) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = 'The uploaded file could not be read.';

            return $result;
        }

        $fileSize = @filesize($filePath);
        if ($fileSize === false || $fileSize <= 0) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = 'The uploaded Excel file is empty.';

            return $result;
        }

        if ($fileSize > self::MAX_FILE_SIZE_BYTES) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = 'The file size cannot exceed 20 MB.';

            return $result;
        }

        $extension = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = 'The uploaded file must be a valid .xlsx Excel workbook.';

            return $result;
        }

        try {
            $identifiedType = IOFactory::identify($filePath);
            if ($identifiedType !== 'Xlsx') {
                $result['is_valid_structure'] = false;
                $result['structure_error'] = 'The uploaded file must be a valid .xlsx Excel workbook.';

                return $result;
            }

            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($filePath);
        } catch (\Throwable) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = 'Unable to parse Excel file. Please ensure it is a valid .xlsx workbook.';

            return $result;
        }

        $sheet = $spreadsheet->getSheetByName(self::WORKSHEET_NAME);
        if (! $sheet) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = "The workbook must contain a '" . self::WORKSHEET_NAME . "' worksheet.";

            return $result;
        }

        $headerError = $this->validateHeaders($sheet);
        if ($headerError !== null) {
            $result['is_valid_structure'] = false;
            $result['structure_error'] = $headerError;

            return $result;
        }

        $masterData = $preloadedMaster ?? $this->loadMasterData($user);

        $highestDataRow = (int) $sheet->getHighestDataRow();
        $seenProjectCodes = [];

        for ($rowNumber = 2; $rowNumber <= $highestDataRow; $rowNumber++) {
            if ($this->isRowBlank($sheet, $rowNumber)) {
                continue;
            }

            if ($result['total_rows'] >= self::MAX_ROWS_LIMIT) {
                $result['is_valid_structure'] = false;
                $result['structure_error'] = 'The workbook exceeds the maximum limit of ' . number_format(self::MAX_ROWS_LIMIT) . ' data rows.';
                $result['rows'] = [];
                $result['total_rows'] = 0;
                $result['valid_rows_count'] = 0;
                $result['invalid_rows_count'] = 0;
                $result['is_all_valid'] = false;

                return $result;
            }

            $validatedRow = $this->validateRow($sheet, $rowNumber, $masterData, $user, $seenProjectCodes);

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
     * Validate exact headings on Row 1 (columns A through S).
     */
    protected function validateHeaders(Worksheet $sheet): ?string
    {
        foreach (self::EXPECTED_HEADERS as $col => $expected) {
            $cellValue = $sheet->getCell($col . '1')->getValue();
            $actual = is_string($cellValue) ? trim($cellValue) : trim((string) $cellValue);

            if ($actual !== $expected) {
                return "Invalid column header at column {$col}: expected '{$expected}', found '{$actual}'.";
            }
        }

        return null;
    }

    /**
     * Check if a data row is entirely blank across columns A to S.
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
     * Preload master data and accessible projects once for bulk validation.
     *
     * @return array<string, mixed>
     */
    protected function loadMasterData(User $user): array
    {
        $projects = Project::query()
            ->with([
                'projectTimelines',
                'serviceTypes:id',
                'technologies:id',
                'salesPerson:id,email,is_active,delete_status,deleted_at',
            ])
            ->where('is_system', false)
            ->whereNull('deleted_at')
            ->get();

        $customers = Customer::active()
            ->whereNull('deleted_at')
            ->get(['id', 'customer_code', 'name', 'sales_person_id']);

        $statuses = ProjectStatus::active()
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code', 'is_default']);

        $stages = ProjectStage::active()
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code', 'is_default']);

        $serviceTypes = ServiceType::active()
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code']);

        $categories = ProjectCategory::active()
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code']);

        $technologies = Technology::active()
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        $salesPeople = $this->userService
            ->getAccessibleUsers($user, [], [$user->id])
            ->filter(fn ($u) => ! empty($u->email) && (bool) $u->is_active);

        return [
            'projects' => $this->indexByField($projects, 'project_code'),
            'customers' => $this->indexByField($customers, 'customer_code'),
            'statuses' => $this->indexByField($statuses, 'code'),
            'stages' => $this->indexByField($stages, 'code'),
            'service_types' => $this->indexByField($serviceTypes, 'code'),
            'categories' => $this->indexByField($categories, 'code'),
            'technologies' => $this->indexByField($technologies, 'name'),
            'sales_people' => $this->indexByField($salesPeople, 'email'),
        ];
    }

    /**
     * Index a collection by normalized lowercase string value of a field, flagging ambiguous duplicates.
     *
     * @return array<string, mixed>
     */
    protected function indexByField(Collection $items, string $field): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $raw = $item->{$field} ?? null;
            if ($raw === null) {
                continue;
            }

            $normalized = mb_strtolower(trim((string) $raw));
            if ($normalized === '') {
                continue;
            }

            if (! isset($indexed[$normalized])) {
                $indexed[$normalized] = $item;
            } else {
                $indexed[$normalized] = 'AMBIGUOUS';
            }
        }

        return $indexed;
    }

    /**
     * Validate an individual Excel data row.
     *
     * @param  array<string, mixed>  $master
     * @param  array<string, int>  &$seenProjectCodes
     * @return array<string, mixed>
     */
    protected function validateRow(
        Worksheet $sheet,
        int $rowNumber,
        array $master,
        User $user,
        array &$seenProjectCodes
    ): array {
        $errors = [];
        $data = [];

        $getVal = function (string $col) use ($sheet, $rowNumber): ?string {
            $val = $sheet->getCell($col . $rowNumber)->getValue();
            if ($val === null) {
                return null;
            }
            $trimmed = trim((string) $val);

            return $trimmed === '' ? null : $trimmed;
        };

        // Raw values map for reporting
        $rawValues = [];
        foreach (self::EXPECTED_HEADERS as $col => $header) {
            $cell = $sheet->getCell($col . $rowNumber);
            $val = $cell->getValue();
            if ($val instanceof \DateTimeInterface) {
                $rawValues[$header] = $val->format('Y-m-d');
            } elseif (in_array($col, ['K', 'L', 'M'], true) && is_numeric($val) && (float) $val >= 1 && (float) $val <= 2958465) {
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

        $rawProjectCode = $getVal('A');
        $rawProjectName = $getVal('B');
        $rawCustomerCode = $getVal('C');
        $rawProjectFlow = $getVal('D');
        $rawPriority = $getVal('E');
        $rawStatusCode = $getVal('F');
        $rawStageCode = $getVal('G');
        $rawServiceTypeCodes = $getVal('H');
        $rawCategoryCodes = $getVal('I');
        $rawTimelineName = $getVal('J');
        $rawEstimatedHours = $getVal('N');
        $rawCustomerEstimatedHours = $getVal('O');
        $rawTechnologies = $getVal('P');
        $rawSalesPersonEmail = $getVal('Q');
        $rawDomain = $getVal('R');
        $rawDefaultBillable = $getVal('S');

        $isCreate = ($rawProjectCode === null);
        $operation = $isCreate ? 'create' : 'update';
        $existingProject = null;

        // 1. Operation Mode & Authorization
        if ($isCreate) {
            if (! $user->is_super_admin && ! $user->can('project.create')) {
                $errors[] = 'Permission: You do not have permission to create projects.';
            }
        } else {
            if (! $user->is_super_admin && ! $user->can('project.edit')) {
                $errors[] = 'Permission: You do not have permission to edit projects.';
            }

            $normalizedProjectCode = mb_strtolower($rawProjectCode);
            if (isset($seenProjectCodes[$normalizedProjectCode])) {
                $firstRow = $seenProjectCodes[$normalizedProjectCode];
                $errors[] = "Project Code: Duplicate Project Code '{$rawProjectCode}' found in this workbook (first appeared on Row {$firstRow}).";
            } else {
                $seenProjectCodes[$normalizedProjectCode] = $rowNumber;
            }

            if (! isset($master['projects'][$normalizedProjectCode])) {
                $errors[] = "Project Code: No matching project was found for code '{$rawProjectCode}'.";
            } elseif ($master['projects'][$normalizedProjectCode] === 'AMBIGUOUS') {
                $errors[] = "Project Code: Multiple projects matched code '{$rawProjectCode}'.";
            } else {
                /** @var \App\Models\Project $existingProject */
                $existingProject = $master['projects'][$normalizedProjectCode];

                if (! $user->is_super_admin && ! $user->can('update', $existingProject)) {
                    $errors[] = "Project Code: You are not authorized to update project '{$existingProject->project_code}'.";
                } else {
                    $data['project_id'] = $existingProject->id;
                    $data['project_code'] = $existingProject->project_code;
                }
            }
        }

        // 2. Project Name
        if ($rawProjectName === null) {
            if ($isCreate) {
                $errors[] = 'Project Name: Project Name is required when creating a project.';
            }
        } elseif (mb_strlen($rawProjectName) > 255) {
            $errors[] = 'Project Name: Project Name cannot exceed 255 characters.';
        } else {
            $data['name'] = $rawProjectName;
        }

        // 3. Customer Code
        if ($rawCustomerCode === null) {
            if ($isCreate) {
                $errors[] = 'Customer Code: Customer Code is required when creating a project.';
            }
        } else {
            $normalizedCustomer = mb_strtolower($rawCustomerCode);
            if (! isset($master['customers'][$normalizedCustomer])) {
                $errors[] = "Customer Code: Customer code '{$rawCustomerCode}' was not found or is inactive.";
            } elseif ($master['customers'][$normalizedCustomer] === 'AMBIGUOUS') {
                $errors[] = "Customer Code: Multiple active customers matched code '{$rawCustomerCode}'.";
            } else {
                $data['customer_id'] = $master['customers'][$normalizedCustomer]->id;
            }
        }

        // 4. Project Flow (Mandatory Business Rule)
        $allowedFlows = array_keys((array) config('project_constants.project_flows', ['agile' => 'Agile', 'linear' => 'Linear']));
        if ($isCreate) {
            if ($rawProjectFlow === null) {
                $errors[] = 'Project Flow: Project Flow is required when creating a project.';
            } else {
                $normalizedFlow = mb_strtolower($rawProjectFlow);
                if (! in_array($normalizedFlow, $allowedFlows, true)) {
                    $errors[] = 'Project Flow: Must be one of: ' . implode(', ', $allowedFlows) . '.';
                } else {
                    $data['project_flow'] = $normalizedFlow;
                }
            }
        } else {
            if ($rawProjectFlow !== null) {
                $errors[] = 'Project Flow: Project Flow must be blank when updating an existing project and cannot be changed.';
            }
        }

        // 5. Priority
        $allowedPriorities = array_keys((array) config('project_constants.project_priorities', []));
        if ($rawPriority === null) {
            if ($isCreate) {
                $errors[] = 'Priority: Priority is required when creating a project.';
            }
        } else {
            $normalizedPriority = mb_strtolower($rawPriority);
            if (! in_array($normalizedPriority, $allowedPriorities, true)) {
                $errors[] = 'Priority: Must be one of: ' . implode(', ', $allowedPriorities) . '.';
            } else {
                $data['priority'] = $normalizedPriority;
            }
        }

        // 6. Project Status Code
        if ($rawStatusCode !== null) {
            $normalizedStatus = mb_strtolower($rawStatusCode);
            if (! isset($master['statuses'][$normalizedStatus])) {
                $errors[] = "Project Status Code: Status code '{$rawStatusCode}' was not found or is inactive.";
            } elseif ($master['statuses'][$normalizedStatus] === 'AMBIGUOUS') {
                $errors[] = "Project Status Code: Multiple active statuses matched code '{$rawStatusCode}'.";
            } else {
                $data['status_id'] = $master['statuses'][$normalizedStatus]->id;
            }
        }

        // 7. Project Stage Code
        if ($rawStageCode !== null) {
            $normalizedStage = mb_strtolower($rawStageCode);
            if (! isset($master['stages'][$normalizedStage])) {
                $errors[] = "Project Stage Code: Stage code '{$rawStageCode}' was not found or is inactive.";
            } elseif ($master['stages'][$normalizedStage] === 'AMBIGUOUS') {
                $errors[] = "Project Stage Code: Multiple active stages matched code '{$rawStageCode}'.";
            } else {
                $data['project_stage_id'] = $master['stages'][$normalizedStage]->id;
            }
        }

        // 8. Service Type Codes (comma-separated)
        if ($rawServiceTypeCodes !== null) {
            $resolvedIds = $this->resolveCommaSeparatedCodes(
                $rawServiceTypeCodes,
                $master['service_types'],
                'Service Type Codes',
                $errors
            );
            if ($resolvedIds !== null) {
                $data['service_type_ids'] = $resolvedIds;
            }
        }

        // 9. Category Codes (comma-separated)
        if ($rawCategoryCodes !== null) {
            $resolvedCategoryIds = $this->resolveCommaSeparatedCodes(
                $rawCategoryCodes,
                $master['categories'],
                'Category Codes',
                $errors
            );
            if ($resolvedCategoryIds !== null) {
                $data['project_category_ids'] = $resolvedCategoryIds;
            }
        }

        // 10. Timeline Name
        if ($rawTimelineName !== null) {
            if (mb_strlen($rawTimelineName) > 255) {
                $errors[] = 'Timeline Name: Timeline Name cannot exceed 255 characters.';
            } else {
                $data['timeline_name'] = $rawTimelineName;
            }
        }

        // 11-13. Dates (Start Date, End Date, Customer End Date)
        $startDate = $this->parseAndValidateDate($sheet->getCell('K' . $rowNumber), 'Start Date', $errors);
        $endDate = $this->parseAndValidateDate($sheet->getCell('L' . $rowNumber), 'End Date', $errors);
        $customerEndDate = $this->parseAndValidateDate($sheet->getCell('M' . $rowNumber), 'Customer End Date', $errors);

        if ($startDate !== null) {
            $data['start_date'] = $startDate;
        }
        if ($endDate !== null) {
            $data['end_date'] = $endDate;
        }
        if ($customerEndDate !== null) {
            $data['customer_end_date'] = $customerEndDate;
        }

        // 14. Estimated Hours -> seconds
        $estimatedSeconds = $this->parseAndValidateHours($rawEstimatedHours, 'Estimated Hours', $errors);
        if ($estimatedSeconds !== null) {
            $data['estimated_time_seconds'] = $estimatedSeconds;
        }

        // 15. Customer Estimated Hours -> seconds
        $customerEstimatedSeconds = $this->parseAndValidateHours($rawCustomerEstimatedHours, 'Customer Estimated Hours', $errors);
        if ($customerEstimatedSeconds !== null) {
            $data['customer_estimate_seconds'] = $customerEstimatedSeconds;
        }

        // Validate timeline rules (date ordering, overlap, original timeline identity & completed protection)
        $this->validateTimelineRules($isCreate, $existingProject, $data, $errors);

        // 16. Technologies (comma-separated names)
        if ($rawTechnologies !== null) {
            $resolvedTechIds = $this->resolveCommaSeparatedCodes(
                $rawTechnologies,
                $master['technologies'],
                'Technologies',
                $errors
            );
            if ($resolvedTechIds !== null) {
                $data['project_technology_ids'] = $resolvedTechIds;
            }
        }

        // 17. Sales Person Email
        if ($rawSalesPersonEmail !== null) {
            if (! filter_var($rawSalesPersonEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Sales Person Email: '{$rawSalesPersonEmail}' is not a valid email address.";
            } else {
                $normalizedEmail = mb_strtolower($rawSalesPersonEmail);
                $matchedUser = $master['sales_people'][$normalizedEmail] ?? null;

                // On update, also allow keeping the project's currently assigned active salesperson
                if ($matchedUser === null && $existingProject && $existingProject->salesPerson) {
                    $existingSp = $existingProject->salesPerson;
                    if (
                        (bool) $existingSp->is_active
                        && ! (bool) $existingSp->delete_status
                        && $existingSp->deleted_at === null
                        && mb_strtolower(trim((string) $existingSp->email)) === $normalizedEmail
                    ) {
                        $matchedUser = $existingSp;
                    }
                }

                if ($matchedUser === null) {
                    $errors[] = "Sales Person Email: No active authorized user was found for email '{$rawSalesPersonEmail}'.";
                } elseif ($matchedUser === 'AMBIGUOUS') {
                    $errors[] = "Sales Person Email: Multiple active users matched email '{$rawSalesPersonEmail}'.";
                } else {
                    $data['sales_person_id'] = $matchedUser->id;
                }
            }
        }

        // 18. Domain
        if ($rawDomain !== null) {
            if (mb_strlen($rawDomain) > 255) {
                $errors[] = 'Domain: Domain cannot exceed 255 characters.';
            } else {
                $data['domain'] = $rawDomain;
            }
        }

        // 19. Default Billable
        if ($rawDefaultBillable !== null) {
            $parsedBillable = $this->parseBooleanYesNo($rawDefaultBillable);
            if ($parsedBillable === null) {
                $errors[] = "Default Billable: Must be 'Yes' or 'No'.";
            } else {
                $data['default_billable'] = $parsedBillable;
            }
        }

        $displayProjectName = $data['name'] ?? ($existingProject?->name ?? '-');

        return [
            'row_number' => $rowNumber,
            'operation' => $operation,
            'project_code' => $data['project_code'] ?? ($rawProjectCode ?? '-'),
            'project_name' => $displayProjectName,
            'status' => count($errors) === 0 ? 'Valid' : 'Invalid',
            'errors' => $errors,
            'data' => $data,
            'raw_values' => $rawValues,
        ];
    }

    /**
     * Resolve comma-separated codes/names against a preloaded lookup map.
     *
     * @param  array<string, mixed>  $lookupMap
     * @param  array<int, string>  &$errors
     * @return array<int, int>|null
     */
    protected function resolveCommaSeparatedCodes(
        string $rawValue,
        array $lookupMap,
        string $columnLabel,
        array &$errors
    ): ?array {
        $tokens = array_values(array_filter(
            array_map('trim', explode(',', $rawValue)),
            fn (string $token) => $token !== ''
        ));

        if (empty($tokens)) {
            $errors[] = "{$columnLabel}: Value cannot contain only commas or whitespace.";

            return null;
        }

        $resolvedIds = [];
        $hasTokenError = false;

        foreach ($tokens as $token) {
            $normalized = mb_strtolower($token);
            if (! isset($lookupMap[$normalized])) {
                $errors[] = "{$columnLabel}: '{$token}' was not found or is inactive.";
                $hasTokenError = true;
            } elseif ($lookupMap[$normalized] === 'AMBIGUOUS') {
                $errors[] = "{$columnLabel}: Multiple active records matched '{$token}'.";
                $hasTokenError = true;
            } else {
                $resolvedIds[] = (int) $lookupMap[$normalized]->id;
            }
        }

        if ($hasTokenError) {
            return null;
        }

        return array_values(array_unique($resolvedIds));
    }

    /**
     * Parse hours string into integer seconds (e.g. 12.5 -> 45000).
     *
     * @param  array<int, string>  &$errors
     */
    protected function parseAndValidateHours(?string $rawValue, string $columnLabel, array &$errors): ?int
    {
        if ($rawValue === null) {
            return null;
        }

        if (! is_numeric($rawValue)) {
            $errors[] = "{$columnLabel}: Must be a valid non-negative number of hours.";

            return null;
        }

        $hours = (float) $rawValue;
        if ($hours < 0 || ! is_finite($hours)) {
            $errors[] = "{$columnLabel}: Must be a non-negative number of hours.";

            return null;
        }

        if ($hours > 1000000) {
            $errors[] = "{$columnLabel}: Value is too large.";

            return null;
        }

        return (int) round($hours * 3600);
    }

    /**
     * Parse Yes/No (or boolean-equivalent) string.
     */
    protected function parseBooleanYesNo(string $value): ?bool
    {
        return match (mb_strtolower(trim($value))) {
            'yes', 'true', '1' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }

    /**
     * Validate date ordering, Original Timeline existence, completed timeline protection, and timeline overlaps.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  &$errors
     */
    protected function validateTimelineRules(bool $isCreate, ?Project $existingProject, array $data, array &$errors): void
    {
        if ($isCreate) {
            $effectiveStart = $data['start_date'] ?? now(config('constants.timezone'))->toDateString();
            $effectiveEnd = $data['end_date'] ?? Carbon::parse($effectiveStart)->addDays(30)->toDateString();
            $effectiveCustomerEnd = $data['customer_end_date'] ?? null;

            if ($effectiveEnd < $effectiveStart) {
                $errors[] = 'End Date: End Date must be on or after Start Date.';
            }

            if ($effectiveCustomerEnd !== null && $effectiveCustomerEnd < $effectiveEnd) {
                $errors[] = 'Customer End Date: Customer End Date must be on or after End Date.';
            }

            return;
        }

        if (! $existingProject) {
            return;
        }

        $hasTimelineField = array_key_exists('timeline_name', $data)
            || array_key_exists('start_date', $data)
            || array_key_exists('end_date', $data)
            || array_key_exists('customer_end_date', $data)
            || array_key_exists('estimated_time_seconds', $data)
            || array_key_exists('customer_estimate_seconds', $data);

        if (! $hasTimelineField) {
            return;
        }

        $originalTimelines = $existingProject->projectTimelines->where('type', 'original');
        if ($originalTimelines->count() !== 1) {
            $errors[] = 'Timeline: Original Timeline could not be uniquely identified for this project.';

            return;
        }

        /** @var ProjectTimeline $originalTimeline */
        $originalTimeline = $originalTimelines->first();

        $effectiveStart = $data['start_date'] ?? $originalTimeline->start_date?->toDateString() ?? $existingProject->start_date?->toDateString();
        $effectiveEnd = $data['end_date'] ?? $originalTimeline->end_date?->toDateString() ?? $existingProject->end_date?->toDateString();
        $effectiveCustomerEnd = $data['customer_end_date'] ?? $originalTimeline->customer_end_date?->toDateString() ?? $existingProject->customer_end_date?->toDateString();

        if ($effectiveStart !== null && $effectiveEnd !== null && $effectiveEnd < $effectiveStart) {
            $errors[] = 'End Date: End Date must be on or after Start Date.';
        }

        if ($effectiveEnd !== null && $effectiveCustomerEnd !== null && $effectiveCustomerEnd < $effectiveEnd) {
            $errors[] = 'Customer End Date: Customer End Date must be on or after End Date.';
        }

        $isModifyingCompletedOrCancelled = in_array((int) $originalTimeline->status, [
            ProjectTimeline::STATUS_COMPLETED,
            ProjectTimeline::STATUS_CANCELLED,
        ], true);

        if ($isModifyingCompletedOrCancelled) {
            $attemptsChange = (array_key_exists('timeline_name', $data) && $data['timeline_name'] !== $originalTimeline->name)
                || (array_key_exists('start_date', $data) && $data['start_date'] !== $originalTimeline->start_date?->toDateString())
                || (array_key_exists('end_date', $data) && $data['end_date'] !== $originalTimeline->end_date?->toDateString())
                || (array_key_exists('customer_end_date', $data) && $data['customer_end_date'] !== $originalTimeline->customer_end_date?->toDateString())
                || (array_key_exists('estimated_time_seconds', $data) && $data['estimated_time_seconds'] !== $originalTimeline->estimated_time_seconds)
                || (array_key_exists('customer_estimate_seconds', $data) && $data['customer_estimate_seconds'] !== $originalTimeline->customer_estimate_seconds);

            if ($attemptsChange) {
                $errors[] = 'Timeline: Completed or cancelled Original Timeline cannot be modified.';

                return;
            }
        }

        if ((array_key_exists('start_date', $data) || array_key_exists('end_date', $data)) && $effectiveStart !== null && $effectiveEnd !== null) {
            $overlapping = $existingProject->projectTimelines
                ->where('id', '!=', $originalTimeline->id)
                ->where('status', '!=', ProjectTimeline::STATUS_CANCELLED)
                ->first(function (ProjectTimeline $timeline) use ($effectiveStart, $effectiveEnd) {
                    $otherStart = $timeline->start_date?->toDateString();
                    $otherEnd = $timeline->end_date?->toDateString();

                    return $otherStart !== null
                        && $otherEnd !== null
                        && $otherStart <= $effectiveEnd
                        && $otherEnd >= $effectiveStart;
                });

            if ($overlapping) {
                $errors[] = 'Start Date: The date range overlaps with another existing project timeline.';
            }
        }
    }

    /**
     * Parse and validate a date cell value.
     * Supports Excel serial dates, DateTimeInterface, and YYYY-MM-DD / YYYY/MM/DD strings.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Cell\Cell|null  $cell
     * @param  array<int, string>  &$errors
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

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        if (ExcelDate::isDateTime($cell) && is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                $errors[] = "{$columnName}: The supplied date is invalid.";

                return null;
            }
        }

        if (is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                $errors[] = "{$columnName}: The supplied date is invalid.";

                return null;
            }
        }

        if (is_string($value)) {
            if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $value, $matches)) {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
                $day = (int) $matches[3];
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }

            if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $value, $matches)) {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }

        $errors[] = "{$columnName}: The supplied date is invalid (expected YYYY-MM-DD).";

        return null;
    }
}

