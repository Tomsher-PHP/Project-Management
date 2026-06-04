<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ProjectSummaryService;
use App\Services\TaskServices;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Analytics';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request, ProjectSummaryService $summaryService, UserService $userService)
    {
        $user = $request->user();
        $workspaceUser = $this->resolveWorkspaceUser($request);

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

        return view('analytics.view', [
            'priorities' => config('project_constants.task_priorities', []),
            'priorityOptions' => $priorityOptions,
            'workspaceSelectableUsers' => $userService->getNavSelectableUsers($user),
            'workspaceSelectedUserId' => (int) $workspaceUser->id === (int) $user->id ? '' : (string) $workspaceUser->id,
            'workspaceSummaryTiles' => $summaryService->getTiles(),
            'workspaceFilterCount' => $workspaceFilterCount,
            'workspaceHasActiveFilters' => $workspaceFilterCount > 0,
        ]);
    }

    public function summary(Request $request, ProjectSummaryService $summaryService)
    {
        $authUser = $request->user();
        $selectedUser = $this->resolveSelectedUser($request);

        if ($selectedUser instanceof \Illuminate\Http\JsonResponse) {
            return $selectedUser;
        }

        $data = $summaryService->getSummary($authUser, $selectedUser);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function taskStatusChart(Request $request, ProjectSummaryService $summaryService)
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
        ]);

        $selectedUser = $this->resolveSelectedUser($request);

        if ($selectedUser instanceof \Illuminate\Http\JsonResponse) {
            return $selectedUser;
        }

        $data = $summaryService->getTaskStatusChart(
            $request->user(),
            $selectedUser,
            $request->integer('project_id') ?: null
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function taskPriorityChart(Request $request, ProjectSummaryService $summaryService)
    {
        $selectedUser = $this->resolveSelectedUser($request);

        if ($selectedUser instanceof \Illuminate\Http\JsonResponse) {
            return $selectedUser;
        }

        $data = $summaryService->getTaskPriorityChart($request->user(), $selectedUser);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function timeComparisonChart(Request $request, ProjectSummaryService $summaryService)
    {
        $selectedUser = $this->resolveSelectedUser($request);

        if ($selectedUser instanceof \Illuminate\Http\JsonResponse) {
            return $selectedUser;
        }

        $date = $this->resolveSelectedDate($request->input('date'));

        $data = $summaryService->getTaskLoggedTimeChart($request->user(), $selectedUser, $date);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Resolve and authorize the selected user for AJAX requests.
     *
     * @param Request $request
     * @return User|null|\Illuminate\Http\JsonResponse
     */
    private function resolveSelectedUser(Request $request)
    {
        $authUser = $request->user();
        $userId = $request->integer('user_id');

        if (! $userId || (int) $userId === (int) $authUser->id) {
            return null;
        }

        $selectedUser = User::query()
            ->accessibleBy($authUser)
            ->whereKey($userId)
            ->first();

        if (! $selectedUser) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this data.',
            ], 403);
        }

        return $selectedUser;
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
}
