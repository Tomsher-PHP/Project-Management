<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\TaskFilterService;
use App\Services\TaskFormService;
use App\Services\TaskQueryService;
use App\Services\TaskServices;
use App\Services\UserService;
use App\Services\UserTimelineService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UserWorkspaceController extends Controller
{
    private const KANBAN_STATUS_PAGE_SIZE = 5;

    protected string $pageTitle;

    private UserTimelineService $timeLineService;

    public function __construct(UserTimelineService $timeLineService)
    {
        $this->timeLineService = $timeLineService;
        $this->pageTitle = 'My Workspace';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request, TaskServices $taskServices, TaskFilterService $taskFilterService, TaskFormService $taskFormService, UserService $userService)
    {
        $user = $request->user();
        $workspaceUser = $this->resolveWorkspaceUser($request);
        $selectedDate = $this->resolveSelectedDate($request->input('date'));
        $timelineViewData = $this->buildTimelineViewData($workspaceUser, $selectedDate, (int) $user->id === (int) $workspaceUser->id);

        if ($request->ajax() && $request->boolean('kanban')) {
            return $this->renderKanbanBoard($request, $taskServices, $workspaceUser);
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('workspace.partials.daily-timeline', $timelineViewData)->render(),
            ]);
        }

        [$selectedFlowType, $boardStatuses, $tasksByStatus, $selectedKanbanSort] = $this->buildWorkspaceKanbanData($request, $taskServices, $workspaceUser);
        $filterViewData = $this->buildWorkspaceFilterViewData($request, $taskFilterService, $selectedFlowType, $workspaceUser);

        $formData = $taskFormService->getCreateData($user);
        $taskCreateProjects = $formData['taskCreateProjects'] ?? collect();
        $taskCreateDependencies = $this->buildTaskCreateDependencies($taskCreateProjects);

        $workspaceFilterCount = collect([
            filled($request->input('project_id')) ? 'project_id' : null,
            filled($request->input('project_milestone_id')) ? 'project_milestone_id' : null,
            filled($request->input('project_sprint_id')) ? 'project_sprint_id' : null,
            filled($request->input('priority')) ? 'priority' : null
        ])->filter()->count();

        $priorityOptions = collect(config('project_constants.task_priorities', []))->map(
            fn($config, $key) => (object) [
                'id' => $key,
                'name' => $config['label'],
            ]
        );

        return view('workspace.view', [
            'tasksByStatus' => $tasksByStatus,
            'boardStatuses' => $boardStatuses,
            'selectedFlowType' => $selectedFlowType,
            'selectedKanbanSort' => $selectedKanbanSort,
            'kanbanSortOptions' => $taskServices->getKanbanSortOptions(),
            'priorities' => config('project_constants.task_priorities', []),
            'priorityOptions' => $priorityOptions,
            'workspaceSelectableUsers' => $userService->getNavSelectableUsers($user),
            'workspaceSelectedUserId' => (int) $workspaceUser->id === (int) $user->id ? '' : (string) $workspaceUser->id,
            'taskCreateDependencies' => $taskCreateDependencies,
            'workspaceFilterCount' => $workspaceFilterCount,
            'workspaceHasActiveFilters' => $workspaceFilterCount > 0,
        ] + $timelineViewData + $filterViewData + $formData);
    }

    private function renderKanbanBoard(Request $request, TaskServices $taskServices, User $workspaceUser)
    {
        [$selectedFlowType, $boardStatuses, $tasksByStatus] = $this->buildWorkspaceKanbanData($request, $taskServices, $workspaceUser, true);
        $priorities = config('project_constants.task_priorities', []);

        if ($request->filled('status_id')) {
            $statusId = (int) $request->input('status_id');
            $page = max((int) $request->input('page', 1), 1);

            $status = $boardStatuses->firstWhere('id', $statusId);
            abort_unless($status, Response::HTTP_NOT_FOUND);

            $column = $taskServices->getKanbanStatusData(
                $request->user(),
                $request->all(),
                $selectedFlowType,
                $statusId,
                $page,
                self::KANBAN_STATUS_PAGE_SIZE,
                $this->buildWorkspaceKanbanOptions($boardStatuses, $request, $taskServices, $workspaceUser)
            );

            return response()->json([
                'status' => true,
                'html' => view('tasks.kanban._cards', [
                    'tasks' => $column['tasks'],
                    'status' => $status,
                    'priorities' => $priorities,
                ])->render(),
                'hasMore' => $column['hasMore'],
                'nextPage' => $column['nextPage'],
                'taskIds' => $column['taskIds'],
                'total' => $column['total'],
            ], Response::HTTP_OK);
        }

        return view('tasks.kanban._board', compact('boardStatuses', 'tasksByStatus', 'priorities'))->render();
    }

    private function buildWorkspaceKanbanData(Request $request, TaskServices $taskServices, User $workspaceUser, bool $persistRequestedFlow = false): array
    {
        $user = $request->user();
        $selectedFlowType = $this->resolveWorkspaceFlowType($request, $persistRequestedFlow);
        $boardStatuses = TaskStatus::query()
            ->active()
            ->forFlow($selectedFlowType)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'is_default', 'is_completed']);
        $options = $this->buildWorkspaceKanbanOptions($boardStatuses, $request, $taskServices, $workspaceUser);

        $tasksByStatus = $request->filled('status_id')
            ? []
            : $taskServices->getKanban(
                $user,
                $request->all(),
                $selectedFlowType,
                $boardStatuses,
                self::KANBAN_STATUS_PAGE_SIZE,
                $options
            );

        return [
            $selectedFlowType,
            $boardStatuses,
            $tasksByStatus,
            $options['sort'] ?? null,
        ];
    }

    private function buildWorkspaceKanbanOptions($boardStatuses, Request $request, TaskServices $taskServices, User $workspaceUser): array
    {
        return [
            'sort' => $taskServices->resolveKanbanSort($request->input('sort')),
            'workspace_user_id' => (int) $workspaceUser->id,
            'workspace_recent_completed_days' => 7,
            'completed_status_ids' => $boardStatuses
                ->where('is_completed', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all(),
        ];
    }

    private function resolveWorkspaceFlowType(Request $request, bool $persistRequestedFlow = false): string
    {
        $user = $request->user();
        $selectedFlowType = $request->input('flow')
            ?: ($user->generalSettings()->where('user_id', $user->id)->value('kanban_view') ?? 'agile');

        if ($persistRequestedFlow && $request->filled('flow')) {
            $user->generalSettings()->update(['kanban_view' => $selectedFlowType]);
        }

        return $selectedFlowType;
    }

    private function buildWorkspaceFilterViewData(Request $request, TaskFilterService $taskFilterService, string $selectedFlowType, User $workspaceUser): array
    {
        $baseQuery = app(TaskQueryService::class)
            ->baseQuery($request->user())
            ->where('current_assignee_id', $workspaceUser->id)
            ->whereHas('project', fn($query) => $query->where('project_flow', $selectedFlowType))
            ->where('request_status', '!=', 'rejected');

        return $taskFilterService->getFilters($request->user(), $baseQuery);
    }

    private function resolveWorkspaceUser(Request $request): User
    {
        $authUser = $request->user();

        if (! $request->filled('user_id')) {
            return $authUser;
        }

        $selectedUserId = (int) $request->input('user_id');

        if ($selectedUserId === (int) $authUser->id) {
            return $authUser;
        }

        $workspaceUser = User::query()
            ->accessibleBy($authUser)
            ->whereKey($selectedUserId)
            ->first();

        abort_unless($workspaceUser, Response::HTTP_FORBIDDEN, 'You are not allowed to access this workspace user.');

        return $workspaceUser;
    }

    private function buildTimelineViewData(User $workspaceUser, Carbon $selectedDate, bool $isOwnWorkspace = true): array
    {
        $userId = (int) $workspaceUser->id;
        $assignedShift = $this->timeLineService->getAssignedShift($userId, $selectedDate);
        $workedTaskSegments = $this->timeLineService->getWorkedTaskTimelineSegments($userId, $selectedDate);
        $breakTaskSegments = $this->timeLineService->getBreakTimelineSegments($workedTaskSegments, $assignedShift, $selectedDate);
        $shiftSummaryDuration = (!empty($assignedShift['timeline_segments']) && ($assignedShift['is_working_day'] ?? false))
            ? ($assignedShift['timeline_segments'][0]['duration_label'] ?? '--')
            : '--';
        $workedTotalSeconds = $this->timeLineService->getTotalTimelineSeconds($workedTaskSegments);
        $breakTotalSeconds = $this->timeLineService->getTotalTimelineSeconds($breakTaskSegments);

        $dateFormat = config('constants.date_format');
        return [
            'assignedShift' => $assignedShift,
            'workedTaskSegments' => $workedTaskSegments,
            'breakTaskSegments' => $breakTaskSegments,
            'shiftSummaryDuration' => $shiftSummaryDuration,
            'workedSummaryDuration' => formatSecondsToHMS($workedTotalSeconds),
            'breakSummaryDuration' => formatSecondsToHMS($breakTotalSeconds),
            'selectedDateValue' => $selectedDate->toDateString(),
            'todayDate' => now($selectedDate->getTimezone())->toDateString(),
            'workspaceGreetingLabel' => $isOwnWorkspace ? $this->buildWorkspaceGreetingLabel($workspaceUser->name) : null,
            'workspaceGreetingDayName' => $isOwnWorkspace ? now()->format('l, ' . $dateFormat) : null,
            'workspaceTimelineUserName' => $workspaceUser->name,
            'workspaceTimelineUserAvatarUrl' => $workspaceUser->profileImageUrl,
            'workspaceTimelineUserInitial' => Str::upper(Str::substr($workspaceUser->name ?? 'U', 0, 2)),
            'workspaceTimelineShowsUser' => ! $isOwnWorkspace,
        ];
    }

    private function resolveSelectedDate(mixed $date): Carbon
    {
        if (blank($date)) {
            return now()->startOfDay();
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function buildWorkspaceGreetingLabel(?string $userName): ?string
    {
        if (blank($userName)) {
            return null;
        }

        $hour = now()->setTimezone(config('constants.timezone'))->hour;
        $greeting = match (true) {
            $hour < 12 => 'Good Morning',
            $hour < 17 => 'Good Afternoon',
            default => 'Good Evening',
        };

        return "{$greeting}, {$userName}";
    }

    private function buildTaskCreateDependencies(\Illuminate\Support\Collection $projects): array
    {
        $statusOptionsByFlow = TaskStatus::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'flow_type'])
            ->groupBy('flow_type')
            ->map(fn(\Illuminate\Support\Collection $statuses) => $statuses->map(fn(TaskStatus $status) => [
                'value' => (string) $status->id,
                'text' => $status->name,
            ])->values())
            ->toArray();
        $defaultStatusIdsByFlow = collect(array_keys(config('project_constants.project_flows', [])))
            ->mapWithKeys(fn(string $flowType) => [$flowType => $this->getDefaultTaskStatusIdForFlow($flowType)]);

        return [
            'projects' => $projects->mapWithKeys(function (Project $project) use ($defaultStatusIdsByFlow) {
                return [(string) $project->id => [
                    'id' => $project->id,
                    'flow' => $project->project_flow,
                    'default_billable' => (bool) $project->default_billable,
                    'default_status_id' => $defaultStatusIdsByFlow[$project->project_flow] ?? null,
                    'default_task_estimate_minutes' => $project->default_task_estimate_seconds !== null
                        ? intdiv((int) $project->default_task_estimate_seconds, 60)
                        : 0,
                    'milestones' => $project->projectMilestones
                        ->reject(fn(ProjectMilestone $projectMilestone) => (bool) ($projectMilestone->is_backlog || $projectMilestone->is_system))
                        ->map(fn(ProjectMilestone $projectMilestone) => [
                            'value' => (string) $projectMilestone->id,
                            'text' => $projectMilestone->name,
                        ])
                        ->values(),
                    'sprints' => $project->projectSprints
                        ->reject(fn(ProjectSprint $projectSprint) => (bool) ($projectSprint->is_backlog || $projectSprint->is_system))
                        ->map(fn(ProjectSprint $projectSprint) => [
                            'value' => (string) $projectSprint->id,
                            'text' => $projectSprint->name,
                            'project_milestone_id' => (string) ($projectSprint->project_milestone_id ?? ''),
                        ])
                        ->values(),
                    'assignees' => $project->activeMembers
                        ->sortBy('name')
                        ->values()
                        ->map(fn(User $user) => [
                            'value' => (string) $user->id,
                            'text' => $user->name,
                        ]),
                ]];
            }),
            'status_options_by_flow' => $statusOptionsByFlow,
            'defaults' => [
                'project_id' => $projects->firstWhere('id', $this->resolveDefaultTaskCreateProjectId($projects))?->id,
                'priority' => $this->getDefaultTaskPriorityValue(),
                'due_date_time' => now(config('constants.timezone'))->addDay()->format('Y-m-d H:i'),
            ],
            'parent_options_url' => route('tasks.quick-create-parent-options'),
        ];
    }

    private function resolveDefaultTaskCreateProjectId(\Illuminate\Support\Collection $projects): ?int
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        $projectId = Task::query()
            ->where('added_by', $userId)
            ->whereNotNull('project_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('project_id');

        if (! $projectId) {
            return null;
        }

        return $projects->contains('id', $projectId) ? (int) $projectId : null;
    }

    private function getDefaultTaskStatusIdForFlow(?string $flowType): ?int
    {
        if (blank($flowType)) {
            return null;
        }

        return TaskStatus::query()
            ->active()
            ->where('flow_type', $flowType)
            ->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN sort_order = 1 THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    private function getDefaultTaskPriorityValue(): string
    {
        $priorities = config('project_constants.task_priorities', []);

        if (array_key_exists('medium', $priorities)) {
            return 'medium';
        }

        return (string) (array_key_first($priorities) ?? 'medium');
    }
}
