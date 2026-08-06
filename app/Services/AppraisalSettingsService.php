<?php

namespace App\Services;

use App\Imports\AppraisalQuestionsImport;
use App\Models\AppraisalCategory;
use App\Models\AppraisalQuestionImport;
use App\Models\AppraisalQuestionUnit;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AppraisalSettingsService
{
    public function getAppraisalCategories(): Collection
    {
        return AppraisalCategory::query()
            ->with('questions')
            ->withCount('questions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(array $data): AppraisalCategory
    {
        return DB::transaction(function () use ($data) {
            $attempts = 0;
            $maxAttempts = 3;
            $appraisalCategory = null;

            while ($attempts < $maxAttempts) {
                try {
                    $attempts++;
                    $appraisalCategory = new AppraisalCategory([
                        'name' => $data['name'],
                        'sort_order' => (int) (AppraisalCategory::max('sort_order') + 1),
                        'is_active' => true,
                        'is_default' => (bool) ($data['is_default'] ?? false),
                    ]);
                    $appraisalCategory->save();
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($attempts >= $maxAttempts || ! $this->isDuplicateKeyException($e)) {
                        throw $e;
                    }
                    $appraisalCategory->code = null;
                }
            }

            $questions = collect($data['questions'] ?? [])
                ->map(fn(array $question) => [
                    'question' => trim($question['question']),
                    'question_type' => $question['question_type'] ?? 'rating',
                    'measurement_type' => $question['measurement_type'] ?? null,
                    'target_value' => $question['target_value'] ?? null,
                    'unit' => $this->resolveQuestionUnit($question['unit'] ?? null),
                    'is_active' => (bool) ($question['is_active'] ?? true),
                ])
                ->filter(fn(array $question) => filled($question['question']))
                ->values()
                ->map(fn(array $question, int $index) => [
                    'question' => $question['question'],
                    'question_type' => $question['question_type'],
                    'measurement_type' => $question['measurement_type'],
                    'target_value' => $question['target_value'],
                    'unit' => $question['unit'],
                    'sort_order' => $index + 1,
                    'is_active' => $question['is_active'],
                ])
                ->all();

            $appraisalCategory->questions()->createMany($questions);

            return $appraisalCategory->load('questions')->loadCount('questions');
        });
    }

    public function updateCategory(AppraisalCategory $appraisalCategory, array $data): AppraisalCategory
    {
        return DB::transaction(function () use ($appraisalCategory, $data) {
            $appraisalCategory->update([
                'name' => $data['name'],
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);

            $submittedQuestions = collect($data['questions'] ?? [])
                ->map(fn(array $question) => [
                    'id' => $question['id'] ?? null,
                    'question' => trim($question['question']),
                    'question_type' => $question['question_type'] ?? 'rating',
                    'measurement_type' => $question['measurement_type'] ?? null,
                    'target_value' => $question['target_value'] ?? null,
                    'unit' => $this->resolveQuestionUnit($question['unit'] ?? null),
                    'is_active' => (bool) ($question['is_active'] ?? true),
                ])
                ->filter(fn(array $question) => filled($question['question']))
                ->values();

            $existingQuestions = $appraisalCategory->questions()->get()->keyBy('id');
            $keptQuestionIds = [];

            $submittedQuestions->each(function (array $question, int $index) use ($appraisalCategory, $existingQuestions, &$keptQuestionIds) {
                $questionId = $question['id'];
                $attributes = [
                    'question' => $question['question'],
                    'question_type' => $question['question_type'],
                    'measurement_type' => $question['measurement_type'],
                    'target_value' => $question['target_value'],
                    'unit' => $question['unit'],
                    'sort_order' => $index + 1,
                    'is_active' => $question['is_active'],
                ];

                if ($questionId && $existingQuestions->has($questionId)) {
                    $existingQuestions->get($questionId)->update($attributes);
                    $keptQuestionIds[] = $questionId;

                    return;
                }

                $createdQuestion = $appraisalCategory->questions()->create($attributes);
                $keptQuestionIds[] = $createdQuestion->id;
            });

            $appraisalCategory->questions()
                ->whereNotIn('id', $keptQuestionIds)
                ->delete();

            return $appraisalCategory->fresh(['questions'])->loadCount('questions');
        });
    }

    public function toggleCategoryStatus(AppraisalCategory $appraisalCategory): AppraisalCategory
    {
        $appraisalCategory->is_active = ! $appraisalCategory->is_active;
        $appraisalCategory->save();

        return $appraisalCategory;
    }

    private function resolveQuestionUnit(?string $unit): ?string
    {
        $name = trim((string) $unit);

        if ($name === '') {
            return null;
        }

        $existingUnit = AppraisalQuestionUnit::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->lockForUpdate()
            ->first();

        if ($existingUnit) {
            if (! $existingUnit->is_active || $existingUnit->deleted_at !== null) {
                $existingUnit->is_active = true;
                $existingUnit->deleted_at = null;
                $existingUnit->save();
            }

            return $existingUnit->name;
        }

        $createdUnit = AppraisalQuestionUnit::create([
            'name' => $name,
            'sort_order' => (int) AppraisalQuestionUnit::max('sort_order') + 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        return $createdUnit->name;
    }

    public function toggleCategoryDefault(AppraisalCategory $appraisalCategory): AppraisalCategory
    {
        $appraisalCategory->is_default = ! $appraisalCategory->is_default;
        $appraisalCategory->save();

        return $appraisalCategory;
    }

    private function isDuplicateKeyException(\Illuminate\Database\QueryException $e): bool
    {
        $errorCode = $e->errorInfo[1] ?? null;
        $sqlState = $e->errorInfo[0] ?? null;
        $message = $e->getMessage();

        return $sqlState === '23000'
            || in_array($errorCode, [1062, 19, 2067, 23505], true)
            || str_contains(strtolower($message), 'unique constraint failed')
            || str_contains(strtolower($message), 'duplicate entry');
    }

    public function createQuestionImportRecord(string $fileName, string $filePath, ?int $uploadedBy = null): AppraisalQuestionImport
    {
        return AppraisalQuestionImport::create([
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => AppraisalQuestionImport::STATUS_PENDING,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function processQuestionImport(int $importId): AppraisalQuestionImport
    {
        $import = AppraisalQuestionImport::findOrFail($importId);

        if (! Storage::disk('local')->exists($import->file_path)) {
            throw new FileNotFoundException(
                sprintf(
                    'Appraisal question import file does not exist. Import ID: %d, disk: local, path: %s',
                    $import->id,
                    $import->file_path
                )
            );
        }

        Log::info('Starting appraisal question import', [
            'import_id' => $import->id,
            'disk' => 'local',
            'relative_path' => $import->file_path,
            'disk_root' => config('filesystems.disks.local.root'),
            'resolved_path' => Storage::disk('local')->path($import->file_path),
            'exists' => Storage::disk('local')->exists($import->file_path),
        ]);

        $import->update([
            'status' => AppraisalQuestionImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            Excel::import(
                new AppraisalQuestionsImport($import, $this),
                $import->file_path,
                'local'
            );

            $import->refresh();

            $finalStatus = $import->failed_rows > 0
                ? AppraisalQuestionImport::STATUS_COMPLETED_WITH_ERRORS
                : AppraisalQuestionImport::STATUS_COMPLETED;

            $import->update([
                'status' => $finalStatus,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->markImportFailed($importId, $e->getMessage());
            throw $e;
        }

        return $import->fresh();
    }

    public function processQuestionChunk(AppraisalQuestionImport $import, array $rows, int $startRowNumber): void
    {
        $nonEmptyRows = [];

        foreach ($rows as $index => $row) {
            $excelRowNumber = $startRowNumber + $index;
            $filteredValues = array_filter($row, fn($val) => $val !== null && trim((string) $val) !== '');

            if (empty($filteredValues)) {
                continue;
            }

            $nonEmptyRows[] = [
                'excel_row' => $excelRowNumber,
                'data' => $row,
            ];
        }

        if (empty($nonEmptyRows)) {
            return;
        }

        $rawCategoryCodes = collect($nonEmptyRows)
            ->map(fn($r) => trim((string) ($r['data']['category_code'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $categoriesMap = AppraisalCategory::query()
            ->whereIn('code', $rawCategoryCodes)
            ->get(['id', 'code'])
            ->keyBy(fn($c) => strtoupper(trim($c->code)));

        $validQuestionRows = [];
        $errors = [];
        $insertedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($nonEmptyRows as $rowItem) {
            $excelRowNumber = $rowItem['excel_row'];
            $data = $rowItem['data'];

            $rawCode = trim((string) ($data['category_code'] ?? ''));
            $questionText = trim((string) ($data['question'] ?? ''));
            $rawType = strtolower(trim((string) ($data['question_type'] ?? 'rating')));
            $rawMeasurementType = strtolower(trim((string) ($data['measurement_type'] ?? '')));
            $rawTargetValue = $data['target_value'] ?? null;
            $rawUnit = trim((string) ($data['unit'] ?? ''));
            $rawSortOrder = $data['sort_order'] ?? null;
            $rawIsActive = $data['is_active'] ?? null;

            if ($rawCode === '') {
                $errors[] = "Row {$excelRowNumber}: Category code is required.";
                $failedCount++;
                continue;
            }

            $category = $categoriesMap->get(strtoupper($rawCode));

            if (! $category) {
                $errors[] = "Row {$excelRowNumber}: Category code '{$rawCode}' does not exist.";
                $failedCount++;
                continue;
            }

            if ($questionText === '') {
                $errors[] = "Row {$excelRowNumber}: Question text is required.";
                $failedCount++;
                continue;
            }

            if (mb_strlen($questionText) > 500) {
                $errors[] = "Row {$excelRowNumber}: Question text exceeds maximum length of 500 characters.";
                $failedCount++;
                continue;
            }

            $questionType = $rawType !== '' ? $rawType : \App\Models\AppraisalQuestion::QUESTION_TYPE_RATING;

            if (! in_array($questionType, array_keys(\App\Models\AppraisalQuestion::QUESTION_TYPES), true)) {
                $errors[] = "Row {$excelRowNumber}: Invalid question type '{$rawType}'. Must be rating, answer, or target.";
                $failedCount++;
                continue;
            }

            $measurementType = null;
            $targetValue = null;

            if ($questionType === \App\Models\AppraisalQuestion::QUESTION_TYPE_TARGET) {
                $measurementType = $rawMeasurementType !== '' ? $rawMeasurementType : null;

                if (! $measurementType || ! in_array($measurementType, array_keys(\App\Models\AppraisalQuestion::MEASUREMENT_TYPES), true)) {
                    $errors[] = "Row {$excelRowNumber}: Target questions require measurement type (number, currency, or percentage).";
                    $failedCount++;
                    continue;
                }

                if ($rawTargetValue !== null && $rawTargetValue !== '') {
                    if (! is_numeric($rawTargetValue)) {
                        $errors[] = "Row {$excelRowNumber}: Target value must be a valid number.";
                        $failedCount++;
                        continue;
                    }
                    $targetValue = (float) $rawTargetValue;
                } else {
                    $errors[] = "Row {$excelRowNumber}: Target value is required for target questions.";
                    $failedCount++;
                    continue;
                }
            }

            $unit = $rawUnit !== '' ? mb_substr($rawUnit, 0, 255) : null;
            if ($unit !== null && $questionType === \App\Models\AppraisalQuestion::QUESTION_TYPE_TARGET) {
                $unit = $this->resolveQuestionUnit($unit);
            }

            $sortOrder = 1;
            if (is_numeric($rawSortOrder) && (int) $rawSortOrder >= 0) {
                $sortOrder = (int) $rawSortOrder;
            } else {
                $sortOrder = (int) (\App\Models\AppraisalQuestion::where('appraisal_category_id', $category->id)->max('sort_order') ?? 0) + count($validQuestionRows) + 1;
            }

            $isActive = true;
            if ($rawIsActive !== null && $rawIsActive !== '') {
                $isActive = filter_var($rawIsActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            }

            // Check duplicate question in same category
            $isDuplicateInDb = \App\Models\AppraisalQuestion::where('appraisal_category_id', $category->id)
                ->whereRaw('LOWER(question) = ?', [mb_strtolower($questionText)])
                ->exists();

            $isDuplicateInBatch = collect($validQuestionRows)->contains(
                fn($q) => $q['appraisal_category_id'] === $category->id && mb_strtolower($q['question']) === mb_strtolower($questionText)
            );

            if ($isDuplicateInDb || $isDuplicateInBatch) {
                $skippedCount++;
                continue;
            }

            $validQuestionRows[] = [
                'appraisal_category_id' => $category->id,
                'question' => $questionText,
                'question_type' => $questionType,
                'measurement_type' => $measurementType,
                'target_value' => $targetValue,
                'unit' => $unit,
                'sort_order' => $sortOrder,
                'is_active' => $isActive ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $insertedCount++;
        }

        if (! empty($validQuestionRows)) {
            DB::transaction(function () use ($validQuestionRows) {
                \App\Models\AppraisalQuestion::insert($validQuestionRows);
            });
        }

        $errorReportPath = $import->error_report_path;

        if (! empty($errors)) {
            $errorReportPath = $this->writeImportErrorsReport($import->id, $errors, $import->error_report_path);
        }

        $import->update([
            'total_rows' => $import->total_rows + count($nonEmptyRows),
            'processed_rows' => $import->processed_rows + count($nonEmptyRows),
            'inserted_rows' => $import->inserted_rows + $insertedCount,
            'skipped_rows' => $import->skipped_rows + $skippedCount,
            'failed_rows' => $import->failed_rows + $failedCount,
            'error_report_path' => $errorReportPath,
        ]);
    }

    private function writeImportErrorsReport(int $importId, array $errors, ?string $existingPath = null): string
    {
        $relativePath = $existingPath ?? "appraisal-question-imports/errors/import_{$importId}_errors.csv";
        $fullPath = storage_path('app/' . $relativePath);

        $directory = dirname($fullPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = fopen($fullPath, 'a');

        if (! $existingPath || filesize($fullPath) === 0) {
            fputcsv($file, ['error_details']);
        }

        foreach ($errors as $error) {
            fputcsv($file, [$error]);
        }

        fclose($file);

        return $relativePath;
    }

    public function markImportFailed(int $importId, string $errorMessage): void
    {
        $import = AppraisalQuestionImport::find($importId);

        if ($import) {
            $import->update([
                'status' => AppraisalQuestionImport::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => mb_substr($errorMessage, 0, 1000),
            ]);
        }
    }

    public function deleteQuestionImportFile(int $importId): void
    {
        $import = AppraisalQuestionImport::find($importId);

        if ($import && $import->file_path) {
            Storage::disk('local')->delete($import->file_path);
        }
    }
}
