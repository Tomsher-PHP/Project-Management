<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickTaskCreateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && ($user->is_super_admin || $user->can('task.quick_task')));
    }

    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'start_timer' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Please choose a project.',
            'project_id.integer' => 'The selected project is invalid.',
            'project_id.exists' => 'The selected project is invalid.',
            'name.required' => 'Please enter a task name.',
            'name.max' => 'The task name may not be greater than 255 characters.',
        ];
    }
}
