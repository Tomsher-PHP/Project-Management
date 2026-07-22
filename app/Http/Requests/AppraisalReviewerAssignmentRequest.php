<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppraisalReviewerAssignmentRequest extends FormRequest
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
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.user_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'assignments.*.reviewer_user_ids' => ['required', 'array', 'min:1'],
            'assignments.*.reviewer_user_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignments = collect($this->input('assignments', []))
            ->map(fn ($assignment) => [
                'user_id' => filled($assignment['user_id'] ?? null)
                    ? (int) $assignment['user_id']
                    : null,
                'reviewer_user_ids' => collect($assignment['reviewer_user_ids'] ?? [])
                    ->filter(fn ($id) => filled($id))
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $this->merge([
            'month' => (int) $this->input('month'),
            'year' => (int) $this->input('year'),
            'assignments' => $assignments,
        ]);
    }

    public function messages(): array
    {
        return [
            'assignments.required' => 'Reviewer assignments are required.',
            'assignments.*.reviewer_user_ids.required' => 'Select at least one reviewer for each employee.',
            'assignments.*.reviewer_user_ids.min' => 'Select at least one reviewer for each employee.',
        ];
    }
}
