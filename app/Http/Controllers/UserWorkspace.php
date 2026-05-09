<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use App\Services\TaskServices;
use App\Services\UserTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserWorkspace extends Controller
{
    private const KANBAN_STATUS_PAGE_SIZE = 5;

    protected string $pageTitle;

    private UserTimelineService $timeLineService;

    public function __construct(UserTimelineService $timeLineService)
    {
        $this->timeLineService = $timeLineService;
        $this->pageTitle = 'Workspace';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request, TaskServices $taskServices)
    {
        $user = $request->user();
        $selectedDate = $this->resolveSelectedDate($request->input('date'));
        $timelineViewData = $this->buildTimelineViewData($user->id, $selectedDate, $user?->name);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('workspace.partials.daily-timeline', $timelineViewData)->render(),
            ]);
        }

        $selectedFlowType = $user->generalSettings()->where('user_id', $user->id)->value('kanban_view') ?? 'agile';

        $boardStatuses = TaskStatus::query()
            ->active()
            ->forFlow($selectedFlowType)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'is_default', 'is_completed']);

        $tasksByStatus = $taskServices->getKanban(
            $user,
            $request->all(),
            $selectedFlowType,
            $boardStatuses,
            self::KANBAN_STATUS_PAGE_SIZE
        );

        return view('workspace.view', [
            'tasksByStatus' => $tasksByStatus,
            'boardStatuses' => $boardStatuses,
            'selectedFlowType' => $selectedFlowType,
        ] + $timelineViewData);
    }

    private function buildTimelineViewData(int $userId, Carbon $selectedDate, ?string $userName = null): array
    {
        $assignedShift = $this->timeLineService->getAssignedShift($userId, $selectedDate);
        $workedTaskSegments = $this->timeLineService->getWorkedTaskTimelineSegments($userId, $selectedDate);
        $breakTaskSegments = $this->timeLineService->getBreakTimelineSegments($workedTaskSegments, $assignedShift, $selectedDate);
        $shiftSummaryDuration = (!empty($assignedShift['timeline_segments']) && ($assignedShift['is_working_day'] ?? false))
            ? ($assignedShift['timeline_segments'][0]['duration_label'] ?? '--')
            : '--';
        $workedTotalMinutes = $this->timeLineService->getTotalTimelineMinutes($workedTaskSegments);
        $breakTotalMinutes = $this->timeLineService->getTotalTimelineMinutes($breakTaskSegments);

        return [
            'assignedShift' => $assignedShift,
            'workedTaskSegments' => $workedTaskSegments,
            'breakTaskSegments' => $breakTaskSegments,
            'shiftSummaryDuration' => $shiftSummaryDuration,
            'workedSummaryDuration' => $this->formatDurationLabel($workedTotalMinutes),
            'breakSummaryDuration' => $this->formatDurationLabel($breakTotalMinutes),
            'selectedDateValue' => $selectedDate->toDateString(),
            'todayDate' => now($selectedDate->getTimezone())->toDateString(),
            'workspaceGreetingLabel' => $this->buildWorkspaceGreetingLabel($userName),
            'workspaceGreetingDayName' => $userName ? now()->format('l') : null,
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
