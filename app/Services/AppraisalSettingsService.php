<?php

namespace App\Services;

use App\Models\AppraisalCategory;
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
            $appraisalCategory = AppraisalCategory::create([
                'name' => $data['name'],
                'sort_order' => (int) (AppraisalCategory::max('sort_order') + 1),
                'is_active' => true,
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);

            $questions = collect($data['questions'] ?? [])
                ->map(fn (array $question) => [
                    'question' => trim($question['question']),
                    'is_active' => (bool) ($question['is_active'] ?? true),
                ])
                ->filter(fn (array $question) => filled($question['question']))
                ->values()
                ->map(fn (array $question, int $index) => [
                    'question' => $question['question'],
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

    public function toggleCategoryDefault(AppraisalCategory $appraisalCategory): AppraisalCategory
    {
        $appraisalCategory->is_default = ! $appraisalCategory->is_default;
        $appraisalCategory->save();

        return $appraisalCategory;
    }
}
