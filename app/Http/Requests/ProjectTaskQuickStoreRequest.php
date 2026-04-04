<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectTaskQuickStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = is_object($project) ? $project->id : $project;

        return [
            'title' => ['required', 'string', 'max:255'],
            'project_sprint_id' => [
                'nullable',
                'integer',
                Rule::exists('project_sprints', 'id')->where(
                    fn ($query) => $query->where('project_id', $projectId)
                ),
            ],
            'current_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('project_members', 'user_id')->where(
                    fn ($query) => $query
                        ->where('project_id', $projectId)
                        ->whereNull('removed_at')
                        ->where('is_active', true)
                ),
            ],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
