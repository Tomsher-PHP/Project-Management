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
            'subTitle' => 'Manage recurring task schedules',
        ]);
    }

    public function index(Request $request, TaskFormService $taskFormService)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        $taskSchedules = TaskSchedule::query()
            ->accessibleBy($user)
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
        $projects = $formData['taskCreateProjects'] ?? collect();

        return view('schedule-tasks.index', [
            'taskSchedules' => $taskSchedules,
            'perPage' => $perPage,
            'scheduleDependencies' => $this->buildDependencies($projects),
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
        abort_unless(
            TaskSchedule::accessibleBy(auth()->user())->whereKey($taskSchedule->id)->exists(),
            Response::HTTP_FORBIDDEN
        );

        $formData = $taskFormService->getCreateData($request->user());
        $projects = $formData['taskCreateProjects'] ?? collect();

        return response()->json([
            'status' => true,
            'html' => view('schedule-tasks.partials.create-modal', [
                'taskSchedule' => $taskSchedule,
                'scheduleDependencies' => $this->buildDependencies($projects),
                ...$formData,
            ])->render(),
        ]);
    }

    public function update(ScheduleTaskRequest $request, TaskSchedule $taskSchedule, ScheduleTaskService $service): JsonResponse
    {
        abort_unless(
            TaskSchedule::accessibleBy(auth()->user())->whereKey($taskSchedule->id)->exists(),
            Response::HTTP_FORBIDDEN
        );

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

    private function buildDependencies(Collection $projects): array
    {
        return [
            'projects' => $projects->mapWithKeys(fn(Project $project) => [(string) $project->id => [
                'default_billable' => (bool) $project->default_billable,
                'default_task_estimate_minutes' => intdiv((int) ($project->default_task_estimate_seconds ?? 0), 60),
                'milestones' => $project->projectMilestones
                    ->reject(fn(ProjectMilestone $milestone) => $milestone->is_backlog || $milestone->is_system)
                    ->map(fn(ProjectMilestone $milestone) => [
                        'value' => (string) $milestone->id,
                        'text' => $milestone->name,
                    ])->values(),
                'sprints' => $project->projectSprints
                    ->reject(fn(ProjectSprint $sprint) => $sprint->is_backlog || $sprint->is_system)
                    ->map(fn(ProjectSprint $sprint) => [
                        'value' => (string) $sprint->id,
                        'text' => $sprint->name,
                        'project_milestone_id' => (string) ($sprint->project_milestone_id ?? ''),
                    ])->values(),
                'assignees' => $project->activeMembers
                    ->sortBy('name')
                    ->map(fn(User $user) => [
                        'value' => (string) $user->id,
                        'text' => $user->name,
                    ])->values(),
            ]]),
        ];
    }
}
