<?php

namespace App\Services;

use App\Models\AppraisalCategory;
use App\Models\AppraisalQuestionUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
                ->map(fn (array $question) => [
                    'question' => trim($question['question']),
                    'question_type' => $question['question_type'] ?? 'rating',
                    'measurement_type' => $question['measurement_type'] ?? null,
                    'target_value' => $question['target_value'] ?? null,
                    'unit' => $this->resolveQuestionUnit($question['unit'] ?? null),
                    'is_active' => (bool) ($question['is_active'] ?? true),
                ])
                ->filter(fn (array $question) => filled($question['question']))
                ->values()
                ->map(fn (array $question, int $index) => [
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
                ->map(fn (array $question) => [
                    'id' => $question['id'] ?? null,
                    'question' => trim($question['question']),
                    'question_type' => $question['question_type'] ?? 'rating',
                    'measurement_type' => $question['measurement_type'] ?? null,
                    'target_value' => $question['target_value'] ?? null,
                    'unit' => $this->resolveQuestionUnit($question['unit'] ?? null),
                    'is_active' => (bool) ($question['is_active'] ?? true),
                ])
                ->filter(fn (array $question) => filled($question['question']))
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
}
