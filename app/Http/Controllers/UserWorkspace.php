<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use App\Services\TaskServices;
use Illuminate\Http\Request;

class UserWorkspace extends Controller
{
    private const KANBAN_STATUS_PAGE_SIZE = 5;

    public function index(
        Request $request,
        TaskServices $taskServices
    )
    {
        $selectedFlowType = 'agile';
        $user = $request->user();

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
        ]);
    }
}
