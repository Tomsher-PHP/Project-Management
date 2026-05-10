<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use App\Models\User;
use App\Services\TaskFilterService;
use App\Services\TaskQueryService;
use App\Services\TaskServices;
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

    public function index(Request $request, TaskServices $taskServices, TaskFilterService $taskFilterService)
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

        return view('workspace.view', [
            'tasksByStatus' => $tasksByStatus,
            'boardStatuses' => $boardStatuses,
            'selectedFlowType' => $selectedFlowType,
            'selectedKanbanSort' => $selectedKanbanSort,
            'kanbanSortOptions' => $taskServices->getKanbanSortOptions(),
            'priorities' => config('project_constants.task_priorities', []),
            'workspaceSelectableUsers' => $this->getWorkspaceSelectableUsers($user),
            'workspaceSelectedUserId' => (int) $workspaceUser->id === (int) $user->id ? '' : (string) $workspaceUser->id,
        ] + $timelineViewData + $filterViewData);
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

    private function getWorkspaceSelectableUsers(User $authUser)
    {
        return User::query()
            ->accessibleBy($authUser)
            ->where('id', '!=', $authUser->id)
            ->orderBy('name')
            ->get(['id', 'name']);
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
        $workedTotalMinutes = $this->timeLineService->getTotalTimelineMinutes($workedTaskSegments);
        $breakTotalMinutes = $this->timeLineService->getTotalTimelineMinutes($breakTaskSegments);

        $dateFormat = config('constants.date_format');
        return [
            'assignedShift' => $assignedShift,
            'workedTaskSegments' => $workedTaskSegments,
            'breakTaskSegments' => $breakTaskSegments,
            'shiftSummaryDuration' => $shiftSummaryDuration,
            'workedSummaryDuration' => $this->formatDurationLabel($workedTotalMinutes),
            'breakSummaryDuration' => $this->formatDurationLabel($breakTotalMinutes),
            'selectedDateValue' => $selectedDate->toDateString(),
            'todayDate' => now($selectedDate->getTimezone())->toDateString(),
            'workspaceGreetingLabel' => $isOwnWorkspace ? $this->buildWorkspaceGreetingLabel($workspaceUser->name) : null,
            'workspaceGreetingDayName' => $isOwnWorkspace ? now()->format('l, '.$dateFormat) : null,
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

    private function formatDurationLabel(int $minutes): string
    {
        $hours = intdiv(max($minutes, 0), 60);
        $remainingMinutes = max($minutes, 0) % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$remainingMinutes}m";
    }

    private function buildWorkspaceGreetingLabel(?string $userName): ?string
    {
        if (blank($userName)) {
            return null;
        }

        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 12 => 'Good Morning',
            $hour < 17 => 'Good Afternoon',
            default => 'Good Evening',
        };

        return "{$greeting}, {$userName}";
    }
}
