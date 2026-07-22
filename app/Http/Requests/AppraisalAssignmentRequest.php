<?php

namespace App\Http\Requests;

use App\Models\AppraisalQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AppraisalAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'status' => ['required', 'in:draft,published'],
            'kpi_id' => ['required', 'integer', 'exists:kpis,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.questions' => ['required', 'array', 'min:1'],
            'categories.*.questions.*.question' => ['required', 'string', 'max:500'],
            'categories.*.questions.*.question_type' => [
                'required',
                'string',
                Rule::in(array_keys(AppraisalQuestion::QUESTION_TYPES)),
            ],
        ];

        foreach ($this->input('categories', []) as $categoryIndex => $category) {
            foreach ($category['questions'] ?? [] as $questionIndex => $question) {
                $isTarget = ($question['question_type'] ?? null) === AppraisalQuestion::QUESTION_TYPE_TARGET;
                $presenceRule = $isTarget ? 'required' : 'nullable';
                $prefix = "categories.{$categoryIndex}.questions.{$questionIndex}";

                $rules["{$prefix}.measurement_type"] = [
                    $presenceRule,
                    'string',
                    Rule::in(array_keys(AppraisalQuestion::MEASUREMENT_TYPES)),
                ];
                $rules["{$prefix}.target_value"] = [$presenceRule, 'numeric'];
                $rules["{$prefix}.unit"] = [
                    $presenceRule,
                    'string',
                    'max:255',
                    Rule::exists('appraisal_question_units', 'name')
                        ->where(fn ($query) => $query
                            ->where('is_active', true)
                            ->whereNull('deleted_at')),
                ];
            }
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $categories = collect($this->input('categories', []))
            ->map(function ($category) {
                $questions = collect($category['questions'] ?? [])
                    ->map(function ($question) {
                        $questionType = is_string($question['question_type'] ?? null)
                            ? trim($question['question_type'])
                            : AppraisalQuestion::QUESTION_TYPE_RATING;
                        $isTarget = $questionType === AppraisalQuestion::QUESTION_TYPE_TARGET;

                        return [
                            'question' => is_string($question['question'] ?? null) ? trim($question['question']) : ($question['question'] ?? null),
                            'question_type' => $questionType,
                            'measurement_type' => $isTarget && is_string($question['measurement_type'] ?? null)
                                ? trim($question['measurement_type'])
                                : ($isTarget ? ($question['measurement_type'] ?? null) : null),
                            'target_value' => $isTarget ? ($question['target_value'] ?? null) : null,
                            'unit' => $isTarget && is_string($question['unit'] ?? null)
                                ? trim($question['unit'])
                                : ($isTarget ? ($question['unit'] ?? null) : null),
                        ];
                    })
                    ->filter(fn ($question) => filled($question['question'] ?? null))
                    ->values()
                    ->all();

                return [
                    'name' => is_string($category['name'] ?? null) ? trim($category['name']) : ($category['name'] ?? null),
                    'questions' => $questions,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'month' => (int) $this->input('month'),
            'year' => (int) $this->input('year'),
            'kpi_id' => filled($this->input('kpi_id')) ? (int) $this->input('kpi_id') : null,
            'user_ids' => collect($this->input('user_ids', []))->filter(fn ($id) => filled($id))->map(fn ($id) => (int) $id)->values()->all(),
            'categories' => $categories,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $normalizedCategoryNames = collect($this->input('categories', []))
                ->map(fn ($category) => mb_strtolower(trim((string) ($category['name'] ?? ''))))
                ->filter();

            if ($normalizedCategoryNames->count() !== $normalizedCategoryNames->unique()->count()) {
                $validator->errors()->add('categories', 'Duplicate category names are not allowed.');
            }

            collect($this->input('categories', []))->each(function ($category, $index) use ($validator) {
                $normalizedQuestions = collect($category['questions'] ?? [])
                    ->map(fn ($question) => mb_strtolower(trim((string) ($question['question'] ?? ''))))
                    ->filter();

                if ($normalizedQuestions->count() !== $normalizedQuestions->unique()->count()) {
                    $validator->errors()->add("categories.{$index}.questions", 'Duplicate questions are not allowed within the same category.');
                }
            });
        });
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Select at least one user.',
            'user_ids.min' => 'Select at least one user.',
            'kpi_id.required' => 'Select a KPI.',
            'kpi_id.exists' => 'The selected KPI is not available.',
            'categories.required' => 'Select at least one appraisal category.',
            'categories.min' => 'Select at least one appraisal category.',
            'categories.*.name.required' => 'Category name is required.',
            'categories.*.questions.required' => 'Each selected category must have at least one question.',
            'categories.*.questions.min' => 'Each selected category must have at least one question.',
            'categories.*.questions.*.measurement_type.required' => 'Measurement type is required for target questions.',
            'categories.*.questions.*.target_value.required' => 'Target value is required for target questions.',
            'categories.*.questions.*.unit.required' => 'Unit is required for target questions.',
            'categories.*.questions.*.unit.exists' => 'The selected unit is not available.',
        ];
    }
}
