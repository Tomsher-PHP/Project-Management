<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistRequest extends FormRequest
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $questions = collect($this->input('questions', []))
            ->map(fn ($question) => is_string($question) ? trim($question) : $question)
            ->filter(fn ($question) => filled($question))
            ->values()
            ->all();

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'questions' => $questions,
        ]);
    }

    public function messages(): array
    {
        return [
            'questions.required' => 'Add at least one question.',
            'questions.array' => 'Questions must be provided as a list.',
            'questions.min' => 'Add at least one question.',
            'questions.*.required' => 'Each question field is required.',
        ];
    }
}
