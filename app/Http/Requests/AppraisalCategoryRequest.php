<?php

namespace App\Http\Requests;

use App\Models\AppraisalQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'questions' => ['nullable', 'array'],
            'questions.*' => ['required', 'string', 'max:500'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['nullable', 'integer'],
            'question_is_active' => ['nullable', 'array'],
            'question_is_active.*' => ['boolean'],
            'question_types' => ['nullable', 'array'],
            'question_types.*' => ['required', 'string', Rule::in(array_keys(AppraisalQuestion::QUESTION_TYPES))],
            'measurement_types' => ['nullable', 'array'],
            'target_values' => ['nullable', 'array'],
            'units' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
        ];

        foreach ($this->input('questions', []) as $index => $question) {
            $isTarget = ($this->input("question_types.$index") === AppraisalQuestion::QUESTION_TYPE_TARGET);
            $presenceRule = $isTarget ? 'required' : 'nullable';

            $rules["measurement_types.$index"] = [
                $presenceRule,
                'string',
                Rule::in(array_keys(AppraisalQuestion::MEASUREMENT_TYPES)),
            ];
            $rules["target_values.$index"] = [$presenceRule, 'numeric'];
            $rules["units.$index"] = [
                $presenceRule,
                'string',
                'max:255',
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $rawQuestions = $this->input('questions_json');
        if (is_string($rawQuestions)) {
            $rawQuestions = json_decode($rawQuestions, true) ?? [];
        }

        if (is_array($rawQuestions) && ! empty($rawQuestions) && isset($rawQuestions[0]) && is_array($rawQuestions[0])) {
            $questions = collect($rawQuestions)
                ->map(function (array $question) {
                    $questionType = $question['question_type'] ?? $question['questionTypes'] ?? AppraisalQuestion::QUESTION_TYPE_RATING;
                    $isTarget = $questionType === AppraisalQuestion::QUESTION_TYPE_TARGET;
                    $isActive = filter_var($question['is_active'] ?? $question['isActive'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                    return [
                        'id' => filled($question['id'] ?? null) ? (int) $question['id'] : null,
                        'question' => is_string($question['question'] ?? null) ? trim($question['question']) : ($question['question'] ?? null),
                        'question_type' => $questionType,
                        'measurement_type' => $isTarget ? ($question['measurement_type'] ?? $question['measurementTypes'] ?? null) : null,
                        'target_value' => $isTarget ? ($question['target_value'] ?? $question['targetValues'] ?? null) : null,
                        'unit' => $isTarget && is_string($question['unit'] ?? null)
                            ? trim($question['unit'])
                            : ($isTarget ? ($question['unit'] ?? null) : null),
                        'is_active' => $isActive ?? true,
                    ];
                })
                ->filter(fn ($question) => filled($question['question'] ?? null))
                ->values()
                ->all();
        } else {
            $questionIds = $this->input('question_ids', []);
            $questionStatuses = $this->input('question_is_active', []);
            $questionTypes = $this->input('question_types', []);
            $measurementTypes = $this->input('measurement_types', []);
            $targetValues = $this->input('target_values', []);
            $units = $this->input('units', []);

            $questions = collect($this->input('questions', []))
                ->map(function ($question, $index) use ($questionIds, $questionStatuses, $questionTypes, $measurementTypes, $targetValues, $units) {
                    $isActive = filter_var($questionStatuses[$index] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $questionType = $questionTypes[$index] ?? AppraisalQuestion::QUESTION_TYPE_RATING;
                    $isTarget = $questionType === AppraisalQuestion::QUESTION_TYPE_TARGET;

                    return [
                        'id' => filled($questionIds[$index] ?? null) ? (int) $questionIds[$index] : null,
                        'question' => is_string($question) ? trim($question) : $question,
                        'question_type' => $questionType,
                        'measurement_type' => $isTarget ? ($measurementTypes[$index] ?? null) : null,
                        'target_value' => $isTarget ? ($targetValues[$index] ?? null) : null,
                        'unit' => $isTarget && is_string($units[$index] ?? null)
                            ? trim($units[$index])
                            : ($isTarget ? ($units[$index] ?? null) : null),
                        'is_active' => $isActive ?? true,
                    ];
                })
                ->filter(fn ($question) => filled($question['question'] ?? null))
                ->values()
                ->all();
        }

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'questions' => collect($questions)->pluck('question')->all(),
            'question_ids' => collect($questions)->pluck('id')->all(),
            'question_is_active' => collect($questions)->pluck('is_active')->map(fn (bool $isActive) => $isActive ? 1 : 0)->all(),
            'question_types' => collect($questions)->pluck('question_type')->all(),
            'measurement_types' => collect($questions)->pluck('measurement_type')->all(),
            'target_values' => collect($questions)->pluck('target_value')->all(),
            'units' => collect($questions)->pluck('unit')->all(),
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

            if ($normalizedQuestions->isNotEmpty() && $normalizedQuestions->count() !== $normalizedQuestions->unique()->count()) {
                $validator->errors()->add('questions', 'Duplicate questions are not allowed.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'questions.array' => 'Questions must be provided as a list.',
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
        unset($validated['question_types']);
        unset($validated['measurement_types']);
        unset($validated['target_values']);
        unset($validated['units']);

        return $validated;
    }
}
