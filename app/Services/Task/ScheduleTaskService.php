<?php

namespace App\Services\Task;

use App\Models\TaskSchedule;

class ScheduleTaskService
{
    public function create(array $data): TaskSchedule
    {
        return TaskSchedule::create($this->normalizeData($data));
    }

    public function update(TaskSchedule $taskSchedule, array $data): TaskSchedule
    {
        $taskSchedule->update($this->normalizeData($data));

        return $taskSchedule->refresh();
    }

    public function toggleStatus(TaskSchedule $taskSchedule): TaskSchedule
    {
        $taskSchedule->update(['is_active' => ! $taskSchedule->is_active]);

        return $taskSchedule->refresh();
    }

    private function normalizeData(array $data): array
    {
        $frequency = $data['frequency_type'];

        return [
            'project_id' => $data['project_id'],
            'project_milestone_id' => $data['project_milestone_id'] ?? null,
            'project_sprint_id' => $data['project_sprint_id'] ?? null,
            'task_type_id' => $data['task_type_id'] ?? null,
            'task_mode_id' => $data['task_mode_id'] ?? null,
            'current_assignee_id' => $data['current_assignee_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'estimated_time_seconds' => ((int) ($data['estimated_time_minutes'] ?? 0)) * 60,
            'is_billable' => (bool) ($data['is_billable'] ?? false),
            'frequency_type' => $frequency,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'week_days' => $frequency === TaskSchedule::FREQUENCY_WEEKDAYS
                ? array_values(array_map('intval', $data['week_days'] ?? []))
                : null,
            'weekly_day' => $frequency === TaskSchedule::FREQUENCY_WEEKLY
                ? (int) $data['weekly_day']
                : null,
            'monthly_day' => $frequency === TaskSchedule::FREQUENCY_MONTHLY
                ? (int) $data['monthly_day']
                : null,
        ];
    }
}
