<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HandoffFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $exists = Project::accessibleBy(auth()->user())->where('id', $value)->exists();
                    if (!$exists) {
                        $fail('The selected project is invalid or you do not have permission to access it.');
                    }
                },
            ],
            'project_milestone_id' => [
                'nullable',
                'integer',
                Rule::exists('project_milestones', 'id')->where('project_id', $this->project_id),
            ],
            'project_sprint_id' => [
                'nullable',
                'integer',
                Rule::exists('project_sprints', 'id')->where(function ($query) {
                    $query->where('project_id', $this->project_id);
                    if ($this->project_milestone_id) {
                        $query->where('project_milestone_id', $this->project_milestone_id);
                    }
                }),
            ],
            'source_task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where('project_id', $this->project_id),
            ],
            'purpose' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->project_id && !$validator->errors()->has('project_id')) {
                $project = Project::find($this->project_id);
                if ($project && $project->is_agile && empty($this->project_milestone_id)) {
                    $validator->errors()->add('project_milestone_id', 'The milestone field is required for agile projects.');
                }
            }

            if ($this->source_task_id && !$validator->errors()->has('source_task_id')) {
                $task = Task::find($this->source_task_id);
                if ($task) {
                    if ($this->project_milestone_id && $task->project_milestone_id != $this->project_milestone_id) {
                        $validator->errors()->add('source_task_id', 'The source task does not belong to the selected milestone.');
                    }
                    if ($this->project_sprint_id && $task->project_sprint_id != $this->project_sprint_id) {
                        $validator->errors()->add('source_task_id', 'The source task does not belong to the selected sprint.');
                    }
                }
            }
        });
    }
}
