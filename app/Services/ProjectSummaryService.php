<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\TaskStatus;
use App\Models\User;

class ProjectSummaryService
{
    /**
     * Get workspace summary counts based on user involvement.
     *
     * @param User $authUser
     * @param User|null $selectedUser
     * @return array
     */
    public function getSummary(User $authUser, ?User $selectedUser = null): array
    {
        $user = $selectedUser ?: $authUser;

        $involvedTaskIds = $this->getInvolvedTaskIds($user);

        return [
            'projects' => $this->countProjects($user, $involvedTaskIds),
            'total_tasks' => count($involvedTaskIds),
            'pending' => $this->countTasksByStatusType($involvedTaskIds, 'pending'),
            'active' => $this->countTasksByStatusType($involvedTaskIds, 'active'),
            'completed' => $this->countTasksByStatusType($involvedTaskIds, 'completed'),
            'archived' => $this->countTasksByStatusType($involvedTaskIds, 'archived'),
            'request_pending' => $this->countPendingRequests($involvedTaskIds),
        ];
    }

    /**
     * Get the configured summary tiles.
     *
     * @param bool $withCounts Whether to include real counts or zeros for animation.
     * @param User|null $authUser Required if $withCounts is true.
     * @param User|null $selectedUser
     * @return array
     */
    public function getTiles(bool $withCounts = false, ?User $authUser = null, ?User $selectedUser = null): array
    {
        $summary = $withCounts && $authUser ? $this->getSummary($authUser, $selectedUser) : null;

        return [
            [
                'key' => 'projects',
                'label' => 'Projects',
                'count' => $summary['projects'] ?? 0,
                'helper' => 'Active workspace projects',
                'accent' => 'text-[#2463eb] dark:text-[#8eb4ff]',
                'iconBg' => 'bg-[#edf4ff] dark:bg-[#1f2d45]',
                'icon' => 'folder',
            ],
            [
                'key' => 'total_tasks',
                'label' => 'Total Tasks',
                'count' => $summary['total_tasks'] ?? 0,
                'helper' => 'All visible board tasks',
                'accent' => 'text-[#0f766e] dark:text-[#5eead4]',
                'iconBg' => 'bg-[#ecfdf5] dark:bg-[#183833]',
                'icon' => 'grid',
            ],
            [
                'key' => 'pending',
                'label' => 'Pending',
                'count' => $summary['pending'] ?? 0,
                'helper' => 'Waiting to be started',
                'accent' => 'text-[#d97706] dark:text-[#fdba74]',
                'iconBg' => 'bg-[#fff7ed] dark:bg-[#3a2818]',
                'icon' => 'clock',
            ],
            [
                'key' => 'active',
                'label' => 'Active',
                'count' => $summary['active'] ?? 0,
                'helper' => 'Currently in progress',
                'accent' => 'text-[#4338ca] dark:text-[#a5b4fc]',
                'iconBg' => 'bg-[#eef2ff] dark:bg-[#27264a]',
                'icon' => 'pulse',
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'count' => $summary['completed'] ?? 0,
                'helper' => 'Finished work items',
                'accent' => 'text-[#15803d] dark:text-[#86efac]',
                'iconBg' => 'bg-[#f0fdf4] dark:bg-[#1c3726]',
                'icon' => 'check',
            ],
            [
                'key' => 'archived',
                'label' => 'Archived',
                'count' => $summary['archived'] ?? 0,
                'helper' => 'Stored for reference',
                'accent' => 'text-[#64748b] dark:text-[#cbd5e1]',
                'iconBg' => 'bg-[#f8fafc] dark:bg-[#2b3545]',
                'icon' => 'archive',
            ],
            [
                'key' => 'request_pending',
                'label' => 'Request Pending',
                'count' => $summary['request_pending'] ?? 0,
                'helper' => 'Awaiting request action',
                'accent' => 'text-[#be123c] dark:text-[#fda4af]',
                'iconBg' => 'bg-[#fff1f2] dark:bg-[#40232c]',
                'icon' => 'request',
            ],
        ];
    }

    /**
     * Get distinct task IDs where the user has been involved via assignment logs.
     *
     * @param User $user
     * @return array
     */
    private function getInvolvedTaskIds(User $user): array
    {
        return TaskAssignmentLog::where('user_id', $user->id)
            ->distinct()
            ->pluck('task_id')
            ->toArray();
    }

    /**
     * Count distinct projects where the user is involved.
     *
     * @param User $user
     * @param array $involvedTaskIds
     * @return int
     */
    private function countProjects(User $user, array $involvedTaskIds): int
    {
        // 1. Projects from active membership (removed_at is null)
        $projectIdsFromMembers = ProjectMember::where('user_id', $user->id)
            ->whereNull('removed_at')
            ->pluck('project_id');

        // 2. Projects from task involvement (through task_assignment_logs)
        $projectIdsFromTasks = Task::whereIn('id', $involvedTaskIds)
            ->pluck('project_id');

        // Combine and count distinct project IDs that are not soft-deleted
        $allProjectIds = $projectIdsFromMembers->merge($projectIdsFromTasks)
            ->unique()
            ->filter()
            ->toArray();

        if (empty($allProjectIds)) {
            return 0;
        }

        return Project::whereIn('id', $allProjectIds)->count();
    }

    /**
     * Count distinct involved tasks filtered by status type.
     *
     * @param array $involvedTaskIds
     * @param string $type
     * @return int
     */
    private function countTasksByStatusType(array $involvedTaskIds, string $type): int
    {
        if (empty($involvedTaskIds)) {
            return 0;
        }

        return Task::whereIn('tasks.id', $involvedTaskIds)
            ->join('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
            ->where('task_statuses.type', $type)
            ->whereNull('task_statuses.deleted_at')
            ->count();
    }

    /**
     * Count distinct involved tasks that are pending approval.
     *
     * @param array $involvedTaskIds
     * @return int
     */
    private function countPendingRequests(array $involvedTaskIds): int
    {
        if (empty($involvedTaskIds)) {
            return 0;
        }

        return Task::whereIn('id', $involvedTaskIds)->where('request_status', 'pending')->count();
    }

    /**
     * Get real task status distribution chart data.
     *
     * @param User $authUser
     * @param User|null $selectedUser
     * @param int|null $projectId
     * @return array
     */
    public function getTaskStatusChart(User $authUser, ?User $selectedUser = null, ?int $projectId = null): array
    {
        $user = $selectedUser ?: $authUser;

        // Base query for involved tasks
        $query = Task::query()
            ->where('current_assignee_id', $user->id)
            ->where('request_status', '!=', 'rejected')
            ->whereNull('tasks.deleted_at');

        if ($projectId) {
            $query->where('tasks.project_id', $projectId);
        }

        // $query = collect([]);
        // Get grouped counts by status_id
        $taskCounts = $query->selectRaw('status_id, COUNT(DISTINCT tasks.id) as count')
            ->groupBy('status_id')
            ->pluck('count', 'status_id')
            ->filter(fn($count, $statusId) => !is_null($statusId));

        if ($taskCounts->isEmpty()) {
            return [
                'labels' => [],
                'values' => [],
                'colors' => [],
            ];
        }

        // Fetch used statuses with their metadata
        $statusesQuery = TaskStatus::query()
            ->withTrashed()
            ->whereIn('id', $taskCounts->keys());

        // Apply project-specific ordering/flow if project is selected
        if ($projectId) {
            $project = Project::find($projectId);
            if ($project) {
                $statusesQuery->forFlow($project->project_flow)->orderBy('sort_order');
            }
        } else {
            $statusesQuery->orderBy('name');
        }

        $statuses = $statusesQuery->get(['id', 'name', 'color']);

        return [
            'labels' => $statuses->pluck('name')->toArray(),
            'values' => $statuses->map(fn($status) => (int) $taskCounts->get($status->id, 0))->toArray(),
            'colors' => $statuses->map(fn($status) => $status->color ?: '#CBD5E1')->toArray(),
        ];
    }

    /**
     * Get task priority breakdown chart data (Dummy).
     *
     * @param User $authUser
     * @param User|null $selectedUser
     * @return array
     */
    public function getTaskPriorityChart(User $authUser, ?User $selectedUser = null): array
    {
        // Note: Real logic later must exclude completed tasks.
        return [
            'labels' => ['Low', 'Medium', 'High', 'Urgent'],
            'values' => [3, 7, 5, 2],
            'colors' => ['#94a3b8', '#3b82f6', '#f59e0b', '#ef4444'],
        ];
    }

    /**
     * Get time comparison chart data (Dummy).
     *
     * @param User $authUser
     * @param User|null $selectedUser
     * @param \Illuminate\Support\Carbon|null $date
     * @return array
     */
    public function getTimeComparisonChart(User $authUser, ?User $selectedUser = null, ?\Illuminate\Support\Carbon $date = null): array
    {
        // Dummy logic: assume shift is assigned for now if not specified otherwise
        $hasShift = true;

        if ($hasShift) {
            return [
                'has_shift' => true,
                'labels' => ['Shift Time', 'Task Worked Time', 'Total Break Time'],
                'values' => [8, 6.5, 1],
                'unit' => 'hours',
                'colors' => ['#3b82f6', '#10b981', '#f59e0b'],
            ];
        }

        return [
            'has_shift' => false,
            'labels' => ['Task Worked Time', 'Total Break Time'],
            'values' => [6.5, 1],
            'unit' => 'hours',
            'colors' => ['#10b981', '#f59e0b'],
        ];
    }
}
