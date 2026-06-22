<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\TaskSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScheduleTaskRequest extends FormRequest
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
        $projectId = $this->integer('project_id');

        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'project_milestone_id' => ['nullable', 'integer', Rule::exists('project_milestones', 'id')->where(fn ($query) => $query->where('project_id', $projectId))],
            'project_sprint_id' => ['nullable', 'integer', Rule::exists('project_sprints', 'id')->where(fn ($query) => $query->where('project_id', $projectId))],
            'name' => ['required', 'string', 'max:255'],
            'current_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('project_members', 'user_id')->where(fn ($query) => $query
                    ->where('project_id', $projectId)
                    ->whereNull('removed_at')
                    ->where('is_active', true)),
            ],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'task_type_id' => ['nullable', 'integer', Rule::exists('task_types', 'id')->where('is_active', true)],
            'task_mode_id' => ['nullable', 'integer', Rule::exists('task_modes', 'id')->where('is_active', true)],
            'priority' => ['required', Rule::in(array_keys(config('project_constants.task_priorities', [])))],
            'is_billable' => ['nullable', 'boolean'],
            'frequency_type' => ['required', Rule::in([
                TaskSchedule::FREQUENCY_DAILY,
                TaskSchedule::FREQUENCY_WEEKDAYS,
                TaskSchedule::FREQUENCY_WEEKLY,
                TaskSchedule::FREQUENCY_MONTHLY,
            ])],
            'start_date' => ['required', 'date', 'after_or_equal:' . now(config('constants.timezone'))->format('Y-m-d')],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'week_days' => ['required_if:frequency_type,'.TaskSchedule::FREQUENCY_WEEKDAYS, 'nullable', 'array', 'min:1'],
            'week_days.*' => ['integer', 'between:1,7', 'distinct'],
            'weekly_day' => ['required_if:frequency_type,'.TaskSchedule::FREQUENCY_WEEKLY, 'nullable', 'integer', 'between:1,7'],
            'monthly_day' => ['required_if:frequency_type,'.TaskSchedule::FREQUENCY_MONTHLY, 'nullable', 'integer', 'between:1,31'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (! $this->integer('project_id') || ! $this->user()) {
                return;
            }

            $accessible = Project::query()
                ->accessibleBy($this->user())
                ->whereKey($this->integer('project_id'))
                ->exists();

            if (! $accessible) {
                $validator->errors()->add('project_id', 'The selected project is invalid.');
            }

            $milestoneId = $this->integer('project_milestone_id');
            $sprintId = $this->integer('project_sprint_id');

            if ($milestoneId && $sprintId) {
                $sprintMatchesMilestone = DB::table('project_sprints')
                    ->where('id', $sprintId)
                    ->where('project_milestone_id', $milestoneId)
                    ->exists();

                if (! $sprintMatchesMilestone) {
                    $validator->errors()->add('project_sprint_id', 'The selected sprint does not belong to the selected milestone.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Please choose a project.',
            'name.required' => 'Please enter a task name.',
            'frequency_type.required' => 'Please choose a frequency.',
            'week_days.required_if' => 'Please choose at least one day.',
            'weekly_day.required_if' => 'Please choose a day of the week.',
            'monthly_day.required_if' => 'Please choose a day of the month.',
        ];
    }
}
