<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AppraisalAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'status' => ['required', 'in:draft,published'],
            'kpi_name' => ['required', 'string', 'max:255'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.questions' => ['required', 'array', 'min:1'],
            'categories.*.questions.*.question' => ['required', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $categories = collect($this->input('categories', []))
            ->map(function ($category) {
                $questions = collect($category['questions'] ?? [])
                    ->map(fn ($question) => [
                        'question' => is_string($question['question'] ?? null) ? trim($question['question']) : ($question['question'] ?? null),
                    ])
                    ->filter(fn ($question) => filled($question['question'] ?? null))
                    ->values()
                    ->all();

                return [
                    'name' => is_string($category['name'] ?? null) ? trim($category['name']) : ($category['name'] ?? null),
                    'questions' => $questions,
                ];
            })
            ->filter(fn ($category) => filled($category['name'] ?? null) && count($category['questions'] ?? []) > 0)
            ->values()
            ->all();

        $this->merge([
            'month' => (int) $this->input('month'),
            'year' => (int) $this->input('year'),
            'kpi_name' => is_string($this->input('kpi_name')) ? trim($this->input('kpi_name')) : $this->input('kpi_name'),
            'user_ids' => collect($this->input('user_ids', []))->filter(fn ($id) => filled($id))->map(fn ($id) => (int) $id)->values()->all(),
            'categories' => $categories,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
            'kpi_name.required' => 'Enter a KPI title.',
            'categories.required' => 'Select at least one appraisal category.',
            'categories.min' => 'Select at least one appraisal category.',
            'categories.*.questions.required' => 'Each selected category must have at least one question.',
            'categories.*.questions.min' => 'Each selected category must have at least one question.',
        ];
    }
}
