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
    public function initTracking(
        string $importId,
        string $moduleKey,
        string $fileName,
        string $filePath,
        int $userId
    ): array {
        // Trigger lightweight cleanup of expired imports periodically
        $this->cleanupExpired();

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

        // If total rows was determined and is greater than processed rows, fill remainder as unprocessed
        if ($totalRows > $processedRows) {
            for ($i = $processedRows + 1; $i <= $totalRows; $i++) {
                $rowResults[] = [
                    'excel_row' => $i + 1, // Excel row numbering
                    'project_code' => '-',
                    'service_name' => '-',
                    'status' => 'unprocessed',
                    'message' => 'Processing was interrupted before this row could be processed.',
                    'raw_values' => [],
                ];
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
        $directories = Storage::disk(self::DISK)->directories(self::BASE_DIR);

        foreach ($directories as $dir) {
            $metaPath = $dir . '/meta.json';
            if (Storage::disk(self::DISK)->exists($metaPath)) {
                try {
                    $meta = json_decode(Storage::disk(self::DISK)->get($metaPath), true);
                    $expiresAt = $meta['expires_at'] ?? 0;
                    if ($expiresAt > 0 && now()->timestamp > $expiresAt) {
                        Storage::disk(self::DISK)->deleteDirectory($dir);
                        $deletedCount++;
                    }
                } catch (Throwable) {
                    // If corrupt, check last modified time
                    $lastModified = Storage::disk(self::DISK)->lastModified($metaPath);
                    if ($lastModified && (now()->timestamp - $lastModified) > $this->expirySeconds) {
                        Storage::disk(self::DISK)->deleteDirectory($dir);
                        $deletedCount++;
                    }
                }
            } else {
                // If no meta.json, delete stale orphaned directory after 24h
                $lastModified = Storage::disk(self::DISK)->lastModified($dir);
                if ($lastModified && (now()->timestamp - $lastModified) > $this->expirySeconds) {
                    Storage::disk(self::DISK)->deleteDirectory($dir);
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Generate the "Download Failed Rows" Excel workbook containing only failed rows.
     * Preserves original 18 columns + Column S "Import Error" with sheet "Hosting Upload".
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
        $failedRows = array_filter($rowResults, fn ($r) => ($r['status'] ?? '') === 'failed');

        if (empty($failedRows)) {
            return null;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ProjectHostingImportValidator::WORKSHEET_NAME);

        // Header Row: Columns A through R
        foreach (ProjectHostingImportValidator::EXPECTED_HEADERS as $col => $header) {
            $sheet->setCellValueExplicit($col . '1', $header, DataType::TYPE_STRING);
        }
        // Column S: Import Error
        $sheet->setCellValueExplicit('S1', 'Import Error', DataType::TYPE_STRING);

        // Populate Data Rows
        $currentRow = 2;
        foreach ($failedRows as $failedItem) {
            $raw = $failedItem['raw_values'] ?? [];

            foreach (ProjectHostingImportValidator::EXPECTED_HEADERS as $col => $header) {
                $val = $raw[$header] ?? '';
                $sheet->setCellValueExplicit($col . $currentRow, (string) $val, DataType::TYPE_STRING);
            }

            // Column S
            $errorMessage = $failedItem['message'] ?? 'Validation or import error';
            $sheet->setCellValueExplicit('S' . $currentRow, (string) $errorMessage, DataType::TYPE_STRING);

            $currentRow++;
        }

        $this->applyStandardSheetStyles($sheet, 'S', $currentRow - 1);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    /**
     * Generate the "Download All Results" Excel workbook containing all processed rows.
     * Columns: Excel Row, Result, Details, followed by original 18 template columns.
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Results');

        // Headers
        $headers = [
            'A' => 'Excel Row',
            'B' => 'Result',
            'C' => 'Details',
        ];

        // Shift original 18 headers to columns D through U
        $templateCols = array_keys(ProjectHostingImportValidator::EXPECTED_HEADERS);
        $index = 3; // start from D (0=A, 1=B, 2=C, 3=D)

        $colMapping = [];
        foreach (ProjectHostingImportValidator::EXPECTED_HEADERS as $tCol => $headerName) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $headers[$letter] = $headerName;
            $colMapping[$headerName] = $letter;
            $index++;
        }

        foreach ($headers as $col => $header) {
            $sheet->setCellValueExplicit($col . '1', $header, DataType::TYPE_STRING);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);

        $currentRow = 2;
        foreach ($rowResults as $resultItem) {
            $excelRowLabel = isset($resultItem['excel_row']) ? 'Row ' . $resultItem['excel_row'] : '-';
            $statusLabel = ucfirst($resultItem['status'] ?? 'Unknown');
            $details = $resultItem['message'] ?? '';
            $raw = $resultItem['raw_values'] ?? [];

            $sheet->setCellValueExplicit('A' . $currentRow, $excelRowLabel, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $currentRow, $statusLabel, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $currentRow, (string) $details, DataType::TYPE_STRING);

            foreach (ProjectHostingImportValidator::EXPECTED_HEADERS as $tCol => $headerName) {
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
