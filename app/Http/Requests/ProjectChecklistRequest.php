<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $userId = (int) $this->route('userId');

        return [
            'checklists' => ['present', 'array'],
            'checklists.*.id' => [
                'nullable',
                'integer',
                Rule::exists('project_checklists', 'id')->where(function ($query) use ($project, $userId) {
                    return $query
                        ->where('project_id', $project?->id)
                        ->where('assigned_to', $userId);
                }),
            ],
            'checklists.*.checklist_template_id' => [
                'nullable',
                'integer',
                Rule::exists('checklist_templates', 'id')->where(fn($query) => $query->where('is_active', true)),
            ],
            'checklists.*.title' => ['required', 'string', 'max:255'],
            'checklists.*.questions' => ['required', 'array', 'min:1'],
            'checklists.*.questions.*.id' => ['nullable', 'integer'],
            'checklists.*.questions.*.question' => ['required', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $checklists = collect($this->input('checklists', []))
            ->map(function ($checklist) {
                $questions = collect(data_get($checklist, 'questions', []))
                    ->map(function ($question) {
                        if (is_string($question)) {
                            return [
                                'question' => trim($question),
                            ];
                        }

                        return [
                            'id' => data_get($question, 'id'),
                            'question' => is_string(data_get($question, 'question'))
                                ? trim(data_get($question, 'question'))
                                : data_get($question, 'question'),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => data_get($checklist, 'id'),
                    'checklist_template_id' => data_get($checklist, 'checklist_template_id'),
                    'title' => is_string(data_get($checklist, 'title'))
                        ? trim(data_get($checklist, 'title'))
                        : data_get($checklist, 'title'),
                    'questions' => $questions,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'checklists' => $checklists,
        ]);
    }

    public function messages(): array
    {
        return [
            'checklists.*.title.required' => 'Each checklist needs a title.',
            'checklists.*.questions.required' => 'Add at least one question to each checklist.',
            'checklists.*.questions.min' => 'Add at least one question to each checklist.',
            'checklists.*.questions.*.question.required' => 'Checklist questions cannot be empty.',
        ];
    }
}
