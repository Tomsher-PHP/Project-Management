<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use App\Services\TaskServices;
use App\Services\UserTimelineService;
use Illuminate\Http\Request;

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
        $selectedDate = $request->input('date') ?: now();
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

        $assignedShift = $this->timeLineService->getAssignedShift($user->id, $selectedDate);
        $workedTaskSegments = $this->timeLineService->getWorkedTaskTimelineSegments($user->id, $selectedDate);

        return view('workspace.view', [
            'tasksByStatus' => $tasksByStatus,
            'boardStatuses' => $boardStatuses,
            'assignedShift' => $assignedShift,
            'workedTaskSegments' => $workedTaskSegments,
        ]);
    }
}
