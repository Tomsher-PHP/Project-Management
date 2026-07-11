<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AppraisalCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['required', 'string', 'max:500'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['nullable', 'integer'],
            'question_is_active' => ['nullable', 'array'],
            'question_is_active.*' => ['boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $questionIds = $this->input('question_ids', []);
        $questionStatuses = $this->input('question_is_active', []);

        $questions = collect($this->input('questions', []))
            ->map(function ($question, $index) use ($questionIds, $questionStatuses) {
                $isActive = filter_var($questionStatuses[$index] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                return [
                    'id' => filled($questionIds[$index] ?? null) ? (int) $questionIds[$index] : null,
                    'question' => is_string($question) ? trim($question) : $question,
                    'is_active' => $isActive ?? true,
                ];
            })
            ->filter(fn ($question) => filled($question['question'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'questions' => collect($questions)->pluck('question')->all(),
            'question_ids' => collect($questions)->pluck('id')->all(),
            'question_is_active' => collect($questions)->pluck('is_active')->map(fn (bool $isActive) => $isActive ? 1 : 0)->all(),
            'question_payload' => $questions,
            'is_default' => filter_var($this->input('is_default'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $normalizedQuestions = collect($this->input('questions', []))
                ->map(fn ($question) => mb_strtolower(trim((string) $question)))
                ->filter();

            if ($normalizedQuestions->count() !== $normalizedQuestions->unique()->count()) {
                $validator->errors()->add('questions', 'Duplicate questions are not allowed.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'questions.required' => 'Add at least one question.',
            'questions.array' => 'Questions must be provided as a list.',
            'questions.min' => 'Add at least one question.',
            'questions.*.required' => 'Each question field is required.',
            'questions.*.string' => 'Each question must be valid text.',
            'questions.*.max' => 'Each question may not be greater than 500 characters.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $validated['questions'] = $this->input('question_payload', []);
        unset($validated['question_ids']);
        unset($validated['question_is_active']);

        return $validated;
    }
}
