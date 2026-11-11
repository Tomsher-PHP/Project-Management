<?php

namespace App\Services;

use App\Services\ProjectHostingImportValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ProjectImportTrackingManager
{
    public const DISK = 'local';
    public const BASE_DIR = 'project-imports-data';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
    public const STATUS_FAILED = 'failed';
    public const STATUS_INTERRUPTED = 'interrupted';

    protected int $expirySeconds;

    public function __construct()
    {
        $this->expirySeconds = (int) config('project_constants.import_tracking_expiry_seconds', 86400);
    }

    /**
     * Get the relative storage directory path for an import.
     */
    public function getImportDirectory(string $importId): string
    {
        return self::BASE_DIR . '/' . $importId;
    }

    /**
     * Get the relative meta.json path for an import.
     */
    public function getMetaFilePath(string $importId): string
    {
        return $this->getImportDirectory($importId) . '/meta.json';
    }

    /**
     * Initialize non-database tracking state for an uploaded import.
     *
     * @param  string  $importId
     * @param  string  $moduleKey
     * @param  string  $fileName
     * @param  string  $filePath
     * @param  int  $userId
     * @return array<string, mixed>
     */
    public function initTracking(string $importId, string $moduleKey, string $fileName, string $filePath, int $userId): array
    {
        // Trigger lightweight cleanup of expired imports periodically
        try {
            $this->cleanupExpired();
        } catch (Throwable $e) {
            Log::warning('Import cleanup failed during initialization', ['error' => $e->getMessage()]);
        }

        $meta = [
            'id' => $importId,
            'module_key' => $moduleKey,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'user_id' => $userId,
            'status' => self::STATUS_PENDING,
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'created_rows' => 0,
            'updated_rows' => 0,
            'unchanged_rows' => 0,
            'failed_rows' => 0,
            'error_message' => null,
            'row_results' => [],
            'created_at' => now()->toIso8601String(),
            'started_at' => null,
            'completed_at' => null,
            'expires_at' => now()->addSeconds($this->expirySeconds)->timestamp,
        ];

        $metaPath = $this->getMetaFilePath($importId);
        Storage::disk(self::DISK)->put($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $meta;
    }

    /**
     * Retrieve tracking metadata if present and not expired.
     *
     * @param  string  $importId
     * @return array<string, mixed>|null
     */
    public function getTracking(string $importId): ?array
    {
        $metaPath = $this->getMetaFilePath($importId);

        if (!Storage::disk(self::DISK)->exists($metaPath)) {
            return null;
        }

        try {
            $contents = Storage::disk(self::DISK)->get($metaPath);
            $meta = json_decode($contents, true);

            if (!is_array($meta)) {
                return null;
            }

            // Expiry check
            $expiresAt = $meta['expires_at'] ?? 0;
            if ($expiresAt > 0 && now()->timestamp > $expiresAt) {
                $this->deleteTrackingDirectory($importId);
                return null;
            }

            return $meta;
        } catch (Throwable $e) {
            Log::warning('Failed reading import tracking meta', ['import_id' => $importId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Atomically update tracking metadata.
     *
     * @param  string  $importId
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>|null
     */
    public function updateTracking(string $importId, array $updates): ?array
    {
        $metaPath = $this->getMetaFilePath($importId);

        if (!Storage::disk(self::DISK)->exists($metaPath)) {
            return null;
        }

        try {
            $contents = Storage::disk(self::DISK)->get($metaPath);
            $meta = json_decode($contents, true);

            if (!is_array($meta)) {
                return null;
            }

            $merged = array_merge($meta, $updates);
            Storage::disk(self::DISK)->put($metaPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $merged;
        } catch (Throwable $e) {
            Log::error('Failed updating import tracking meta', ['import_id' => $importId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Mark an import as interrupted, recording any unprocessed rows explicitly.
     *
     * @param  string  $importId
     * @param  string  $errorMessage
     * @return void
     */
    public function markInterrupted(string $importId, string $errorMessage): void
    {
        $meta = $this->getTracking($importId);
        if (!$meta) {
            return;
        }

        $rowResults = $meta['row_results'] ?? [];
        $totalRows = (int) ($meta['total_rows'] ?? 0);
        $processedRows = count($rowResults);

        $schema = $this->resolveModuleSchema((string) ($meta['module_key'] ?? 'project_hosting'));
        $resultColumns = $schema['result_columns'];

        // If total rows was determined and is greater than processed rows, fill remainder as unprocessed
        if ($totalRows > $processedRows) {
            for ($i = $processedRows + 1; $i <= $totalRows; $i++) {
                $unprocessed = [
                    'excel_row' => $i + 1, // Excel row numbering
                    'project_code' => '-',
                    'status' => 'unprocessed',
                    'message' => 'Processing was interrupted before this row could be processed.',
                    'raw_values' => [],
                ];

                if (! empty($resultColumns)) {
                    foreach ($resultColumns as $fieldKey) {
                        $unprocessed[$fieldKey] = '-';
                    }
                } else {
                    $unprocessed['service_name'] = '-';
                }

                $rowResults[] = $unprocessed;
            }
        }

        $this->updateTracking($importId, [
            'status' => self::STATUS_INTERRUPTED,
            'error_message' => $errorMessage,
            'completed_at' => now()->toIso8601String(),
            'row_results' => $rowResults,
        ]);
    }

    /**
     * Safely delete the uploaded source workbook.
     *
     * @param  string  $importId
     * @return void
     */
    public function deleteSourceFile(string $importId): void
    {
        $meta = $this->getTracking($importId);
        if ($meta && !empty($meta['file_path'])) {
            if (Storage::disk(self::DISK)->exists($meta['file_path'])) {
                Storage::disk(self::DISK)->delete($meta['file_path']);
            }
        }
    }

    /**
     * Delete the entire tracking directory and all generated files for an import.
     *
     * @param  string  $importId
     * @return void
     */
    public function deleteTrackingDirectory(string $importId): void
    {
        $dir = $this->getImportDirectory($importId);
        if (Storage::disk(self::DISK)->exists($dir)) {
            Storage::disk(self::DISK)->deleteDirectory($dir);
        }
    }

    /**
     * Clean up expired import tracking directories.
     *
     * @return int Number of directories deleted
     */
    public function cleanupExpired(): int
    {
        $deletedCount = 0;

        try {
            $directories = Storage::disk(self::DISK)->directories(self::BASE_DIR);
        } catch (Throwable $e) {
            Log::warning('Failed listing import tracking directories for cleanup', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }

        foreach ($directories as $dir) {
            try {
                $metaPath = $dir . '/meta.json';
                if (Storage::disk(self::DISK)->exists($metaPath)) {
                    $meta = json_decode(Storage::disk(self::DISK)->get($metaPath), true);
                    $expiresAt = is_array($meta) ? ($meta['expires_at'] ?? 0) : 0;
                    if ($expiresAt > 0 && now()->timestamp > $expiresAt) {
                        Storage::disk(self::DISK)->deleteDirectory($dir);
                        $deletedCount++;
                    }
                } else {
                    // If no meta.json, delete stale orphaned directory after expirySeconds
                    $lastModified = Storage::disk(self::DISK)->lastModified($dir);
                    if ($lastModified && (now()->timestamp - $lastModified) > $this->expirySeconds) {
                        Storage::disk(self::DISK)->deleteDirectory($dir);
                        $deletedCount++;
                    }
                }
            } catch (Throwable $e) {
                // If a single directory cannot be read or deleted (e.g. permission or lock issue), log and continue
                Log::warning('Failed cleaning up expired import tracking directory', [
                    'directory' => $dir,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $deletedCount;
    }

    /**
     * Resolve worksheet title, expected headers, and extra result columns for a module.
     *
     * @return array{worksheet_name: string, expected_headers: array<string, string>, result_columns: array<string, string>}
     */
    public function resolveModuleSchema(string $moduleKey): array
    {
        $moduleConfig = config("project_constants.project_import_modules.{$moduleKey}", []);
        $validatorClass = $moduleConfig['validator_class'] ?? null;

        if (! $validatorClass || ! class_exists($validatorClass)) {
            $validatorClass = match ($moduleKey) {
                'projects' => ProjectBulkImportValidator::class,
                default => ProjectHostingImportValidator::class,
            };
        }

        $worksheetName = defined("{$validatorClass}::WORKSHEET_NAME")
            ? $validatorClass::WORKSHEET_NAME
            : 'Import Data';

        $expectedHeaders = defined("{$validatorClass}::EXPECTED_HEADERS")
            ? $validatorClass::EXPECTED_HEADERS
            : [];

        $resultColumns = is_array($moduleConfig['result_columns'] ?? null)
            ? $moduleConfig['result_columns']
            : [];

        return [
            'worksheet_name' => $worksheetName,
            'expected_headers' => $expectedHeaders,
            'result_columns' => $resultColumns,
        ];
    }

    /**
     * Generate the "Download Failed Rows" Excel workbook containing only failed rows.
     * Preserves all module template columns + trailing "Import Error" column and module worksheet name.
     *
     * @param  string  $importId
     * @return string|null Absolute path to generated file
     */
    public function generateFailedRowsFile(string $importId): ?string
    {
        $meta = $this->getTracking($importId);
        if (!$meta) {
            return null;
        }

        $failedFilePath = $this->getImportDirectory($importId) . '/failed_rows.xlsx';
        $fullPath = Storage::disk(self::DISK)->path($failedFilePath);

        if (file_exists($fullPath)) {
            return $fullPath;
        }

        $rowResults = $meta['row_results'] ?? [];
        $failedRows = array_filter($rowResults, fn($r) => ($r['status'] ?? '') === 'failed');

        if (empty($failedRows)) {
            return null;
        }

        $schema = $this->resolveModuleSchema((string) ($meta['module_key'] ?? 'project_hosting'));
        $expectedHeaders = $schema['expected_headers'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($schema['worksheet_name']);

        // Header Row: module's expected columns
        $colIndex = 1;
        $headerColumnMap = [];
        foreach ($expectedHeaders as $col => $header) {
            $targetCol = is_string($col) && ctype_alpha($col)
                ? $col
                : \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValueExplicit($targetCol . '1', $header, DataType::TYPE_STRING);
            $headerColumnMap[$header] = $targetCol;
            $colIndex++;
        }

        // Trailing Import Error column
        $errorCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($expectedHeaders) + 1);
        $sheet->setCellValueExplicit($errorCol . '1', 'Import Error', DataType::TYPE_STRING);

        // Populate Data Rows
        $currentRow = 2;
        foreach ($failedRows as $failedItem) {
            $raw = $failedItem['raw_values'] ?? [];

            foreach ($expectedHeaders as $header) {
                $targetCol = $headerColumnMap[$header];
                $val = $raw[$header] ?? '';
                $sheet->setCellValueExplicit($targetCol . $currentRow, (string) $val, DataType::TYPE_STRING);
            }

            $errorMessage = $failedItem['message'] ?? 'Validation or import error';
            $sheet->setCellValueExplicit($errorCol . $currentRow, (string) $errorMessage, DataType::TYPE_STRING);

            $currentRow++;
        }

        $this->applyStandardSheetStyles($sheet, $errorCol, $currentRow - 1);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    /**
     * Generate the "Download All Results" Excel workbook containing all processed rows.
     * Columns: Excel Row, optional module result columns, Result, Details, followed by original template columns.
     *
     * @param  string  $importId
     * @return string|null Absolute path to generated file
     */
    public function generateAllResultsFile(string $importId): ?string
    {
        $meta = $this->getTracking($importId);
        if (!$meta) {
            return null;
        }

        $allFilePath = $this->getImportDirectory($importId) . '/all_results.xlsx';
        $fullPath = Storage::disk(self::DISK)->path($allFilePath);

        if (file_exists($fullPath)) {
            return $fullPath;
        }

        $rowResults = $meta['row_results'] ?? [];
        $schema = $this->resolveModuleSchema((string) ($meta['module_key'] ?? 'project_hosting'));
        $expectedHeaders = $schema['expected_headers'];
        $resultColumns = $schema['result_columns'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Results');

        // Build leading summary/identification headers dynamically
        $headers = [];
        $colPos = 1;

        $excelRowCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colPos++);
        $headers[$excelRowCol] = 'Excel Row';

        $extraColMap = [];
        foreach ($resultColumns as $label => $fieldKey) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colPos++);
            $headers[$letter] = $label;
            $extraColMap[$fieldKey] = $letter;
        }

        $resultCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colPos++);
        $headers[$resultCol] = 'Result';

        $detailsCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colPos++);
        $headers[$detailsCol] = 'Details';

        // Append original template headers
        $colMapping = [];
        foreach ($expectedHeaders as $headerName) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colPos++);
            $headers[$letter] = $headerName;
            $colMapping[$headerName] = $letter;
        }

        foreach ($headers as $col => $header) {
            $sheet->setCellValueExplicit($col . '1', $header, DataType::TYPE_STRING);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colPos - 1);

        $currentRow = 2;
        foreach ($rowResults as $resultItem) {
            $excelRowLabel = isset($resultItem['excel_row']) ? 'Row ' . $resultItem['excel_row'] : '-';
            $statusLabel = ucfirst($resultItem['status'] ?? 'Unknown');
            $details = $resultItem['message'] ?? '';
            $raw = $resultItem['raw_values'] ?? [];

            $sheet->setCellValueExplicit($excelRowCol . $currentRow, $excelRowLabel, DataType::TYPE_STRING);

            foreach ($extraColMap as $fieldKey => $letter) {
                $sheet->setCellValueExplicit($letter . $currentRow, (string) ($resultItem[$fieldKey] ?? '-'), DataType::TYPE_STRING);
            }

            $sheet->setCellValueExplicit($resultCol . $currentRow, $statusLabel, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($detailsCol . $currentRow, (string) $details, DataType::TYPE_STRING);

            foreach ($expectedHeaders as $headerName) {
                $targetCol = $colMapping[$headerName];
                $val = $raw[$headerName] ?? '';
                $sheet->setCellValueExplicit($targetCol . $currentRow, (string) $val, DataType::TYPE_STRING);
            }

            $currentRow++;
        }

        $this->applyStandardSheetStyles($sheet, $lastCol, max(2, $currentRow - 1));

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    /**
     * Apply readable styling, borders, and column auto-sizing to a worksheet.
     */
    protected function applyStandardSheetStyles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $lastCol, int $highestRow): void
    {
        // Header styling
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1E293B'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F1F5F9'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Grid borders for all populated cells
        $tableRange = 'A1:' . $lastCol . $highestRow;
        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // Auto column dimensions
        $colCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);
        for ($i = 1; $i <= $colCount; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }
}
