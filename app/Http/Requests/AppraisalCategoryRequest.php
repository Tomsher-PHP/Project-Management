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
        ];
    }

    protected function prepareForValidation(): void
    {
        $questionIds = $this->input('question_ids', []);

        $questions = collect($this->input('questions', []))
            ->map(function ($question, $index) use ($questionIds) {
                return [
                    'id' => filled($questionIds[$index] ?? null) ? (int) $questionIds[$index] : null,
                    'question' => is_string($question) ? trim($question) : $question,
                ];
            })
            ->filter(fn ($question) => filled($question['question'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'questions' => collect($questions)->pluck('question')->all(),
            'question_ids' => collect($questions)->pluck('id')->all(),
            'question_payload' => $questions,
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

        return $validated;
    }
}
