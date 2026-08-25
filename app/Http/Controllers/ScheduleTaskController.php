<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleTaskRequest;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\TaskSchedule;
use App\Models\User;
use App\Services\Task\ScheduleTaskService;
use App\Services\TaskFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ScheduleTaskController extends Controller
{
    public function __construct()
    {
        view()->share([
            'pageTitle' => 'Schedule Tasks',
        ]);
    }

    public function index(Request $request, TaskFormService $taskFormService)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        $baseQuery = TaskSchedule::query()->accessibleBy($user);

        // Calculate dynamic filter options based on the base query before filtering
        $projectIds = (clone $baseQuery)->distinct()->pluck('project_id')->filter();
        $filterProjects = $projectIds->isEmpty() ? collect() : Project::whereIn('id', $projectIds)->orderBy('name')->get(['id', 'name']);

        $assigneeIds = (clone $baseQuery)->whereNotNull('current_assignee_id')->distinct()->pluck('current_assignee_id')->filter();
        $filterAssignees = $assigneeIds->isEmpty() ? collect() : User::whereIn('id', $assigneeIds)->orderBy('name')->get(['id', 'name', 'email']);

        $taskSchedules = (clone $baseQuery)
            ->filter($request->all())
            ->with([
                'project:id,name,project_code',
                'projectMilestone:id,name',
                'projectSprint:id,name',
                'currentAssignee:id,name',
                'taskType:id,name',
                'taskMode:id,name',
                'addedBy:id,name',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $formData = $taskFormService->getCreateData($user);

        return view('schedule-tasks.index', [
            'taskSchedules' => $taskSchedules,
            'perPage' => $perPage,
            'scheduleDependencies' => $taskFormService->buildDependencies(),
            'filterProjects' => $filterProjects,
            'filterAssignees' => $filterAssignees,
            ...$formData,
        ]);
    }

    public function store(ScheduleTaskRequest $request, ScheduleTaskService $service): JsonResponse
    {
        $taskSchedule = $service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Scheduled task created successfully.',
            'data' => $taskSchedule,
        ], Response::HTTP_OK);
    }

    public function edit(Request $request, TaskSchedule $taskSchedule, TaskFormService $taskFormService): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->is_super_admin || $user->can('task.create') || (int) $taskSchedule->added_by_id === (int) $user->id,
            Response::HTTP_FORBIDDEN
        );

        $formData = $taskFormService->getCreateData($user);
        $scheduleDependencies = $taskSchedule->project
            ? $taskFormService->buildDependencies($taskSchedule->project)
            : $taskFormService->buildDependencies();

        $html = view('schedule-tasks.partials.create-modal', [
            'taskSchedule' => $taskSchedule,
            'scheduleDependencies' => $scheduleDependencies,
            ...$formData,
        ])->render();

        return response()->json([
            'status' => true,
            'html' => $html,
            'dependencies' => $scheduleDependencies,
        ]);
    }

    public function update(ScheduleTaskRequest $request, TaskSchedule $taskSchedule, ScheduleTaskService $service): JsonResponse
    {
        $taskSchedule = $service->update($taskSchedule, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Scheduled task updated successfully.',
            'data' => $taskSchedule,
        ]);
    }

    public function toggleStatus(TaskSchedule $taskSchedule, ScheduleTaskService $service): JsonResponse
    {
        abort_unless(
            TaskSchedule::accessibleBy(auth()->user())->whereKey($taskSchedule->id)->exists(),
            Response::HTTP_FORBIDDEN
        );

        $taskSchedule = $service->toggleStatus($taskSchedule);

        return response()->json([
            'success' => true,
            'status' => true,
            'is_active' => $taskSchedule->is_active,
            'message' => $taskSchedule->is_active
                ? 'Scheduled task enabled successfully.'
                : 'Scheduled task disabled successfully.',
        ]);
    }

    public function destroy(Request $request, TaskSchedule $taskSchedule)
    {
        $user = $request->user();

        abort_unless(
            $user->is_super_admin || $user->can('task.delete') || (int) $taskSchedule->added_by_id === (int) $user->id,
            Response::HTTP_FORBIDDEN
        );

        $taskSchedule->delete();

        return redirect(session('task_schedules_return_url', route('schedule-tasks.index')))->with('success', 'Scheduled task deleted successfully.');
    }
}
