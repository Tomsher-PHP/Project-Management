<?php

namespace App\Services;

use App\Models\AppraisalCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AppraisalSettingsService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAppraisalCategories(): Collection
    {
        return AppraisalCategory::query()
            ->with(['questions' => fn ($query) => $query->active()])
            ->withCount(['questions' => fn ($query) => $query->active()])
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
            ]);

            $questions = collect($data['questions'] ?? [])
                ->map(fn (array $question) => trim($question['question']))
                ->filter(fn ($question) => filled($question))
                ->values()
                ->map(fn (string $question, int $index) => [
                    'question' => $question,
                    'sort_order' => $index + 1,
                    'is_active' => true,
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
            ]);

            $submittedQuestions = collect($data['questions'] ?? [])
                ->map(fn (array $question) => [
                    'id' => $question['id'] ?? null,
                    'question' => trim($question['question']),
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
                    'is_active' => true,
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
}
