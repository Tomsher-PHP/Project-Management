<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickNoteRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $content = $this->input('content');

        if (is_string($content)) {
            // Trim non-breaking spaces and strip tags to detect empty HTML strings from Quill (e.g. "<p><br></p>")
            $plainText = trim(str_replace("\xc2\xa0", ' ', strip_tags($content)));

            $this->merge([
                'content' => $plainText === '' ? null : $content,
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255', 'required_without:content'],
            'content' => ['nullable', 'string', 'required_without:title'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'task_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')->whereNull('deleted_at')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_archived' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'task_id' => 'task',
            'is_pinned' => 'pinned status',
            'is_archived' => 'archived status',
            'sort_order' => 'sort order',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required_without' => 'Please provide either a title or content for the note.',
            'content.required_without' => 'Please provide either a title or content for the note.',
            'project_id.exists' => 'The selected project is invalid.',
            'task_id.exists' => 'The selected task is invalid.',
        ];
    }

    /**
     * Perform additional validation hooks.
     */
    public function after(): array
    {
        return [function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            if ($this->filled('project_id')) {
                $projectId = (int) $this->input('project_id');
                $accessible = Project::query()
                    ->accessibleBy($user)
                    ->whereKey($projectId)
                    ->exists();

                if (! $accessible) {
                    $validator->errors()->add('project_id', 'The selected project is not accessible.');
                }
            }

            if ($this->filled('task_id')) {
                $taskId = (int) $this->input('task_id');
                $task = Task::query()
                    ->accessibleBy($user)
                    ->whereKey($taskId)
                    ->first();

                if (! $task) {
                    $validator->errors()->add('task_id', 'The selected task is not accessible.');
                } elseif ($this->filled('project_id')) {
                    $projectId = (int) $this->input('project_id');
                    if ((int) $task->project_id !== $projectId) {
                        $validator->errors()->add('task_id', 'The selected task does not belong to the selected project.');
                    }
                }
            }
        }];
    }
}
