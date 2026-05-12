<?php

namespace App\Http\Controllers;

use App\Http\Requests\HandoffFormRequest;
use App\Models\TaskStatus;
use App\Services\HandoffServices;
use App\Services\TaskFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HandoffController extends Controller
{
    protected HandoffServices $handoffServices;

    public function __construct(HandoffServices $handoffServices)
    {
        $this->handoffServices = $handoffServices;
        view()->share([
            'pageTitle' => 'Handoff Requests',
            'subTitle' => 'Manage and review handoff requests'
        ]);
    }

    public function index(Request $request, TaskFormService $taskFormService)
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 15));
        $handoffRequests = $this->handoffServices->getHandoffRequestsForList($request->user(), $perPage, $request->all());
        $filterOptions = $this->handoffServices->getFilterOptions($request->user());

        $taskFormData = [];
        $taskCreateDependencies = [];
        if ($request->user()->can('task.create') && $request->user()->can('handoff_request.assign')) {
            $taskFormData = $taskFormService->getCreateData($request->user());
            $taskCreateDependencies = $this->buildTaskCreateDependencies($taskFormData['taskCreateProjects'] ?? collect());
        }

        return view('handoff-requests.index', array_merge([
            'handoffRequests' => $handoffRequests,
            'perPage' => $perPage,
            'taskCreateDependencies' => $taskCreateDependencies,
        ], $filterOptions, $taskFormData));
    }

    private function buildTaskCreateDependencies(Collection $projects): array
    {
        $statusOptionsByFlow = TaskStatus::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'flow_type'])
            ->groupBy('flow_type')
            ->map(fn($statuses) => $statuses->map(fn($status) => [
                'value' => (string) $status->id,
                'text' => $status->name,
            ])->values())
            ->toArray();

        $defaultStatusIdsByFlow = collect(array_keys(config('project_constants.project_flows', [])))
            ->mapWithKeys(fn(string $flowType) => [$flowType => $this->getDefaultTaskStatusIdForFlow($flowType)]);

        return [
            'projects' => $projects->mapWithKeys(function ($project) use ($defaultStatusIdsByFlow) {
                return [(string) $project->id => [
                    'id' => $project->id,
                    'flow' => $project->project_flow,
                    'default_billable' => (bool) $project->default_billable,
                    'default_status_id' => $defaultStatusIdsByFlow->get($project->project_flow),
                    'default_task_estimate_minutes' => $project->default_task_estimate_seconds !== null
                        ? intdiv((int) $project->default_task_estimate_seconds, 60)
                        : 0,
                    'milestones' => $project->projectMilestones
                        ->reject(fn($m) => (bool) ($m->is_backlog || $m->is_system))
                        ->map(fn($m) => [
                            'value' => (string) $m->id,
                            'text' => $m->name,
                        ])->values()->toArray(),
                    'sprints' => $project->projectSprints
                        ->reject(fn($s) => (bool) ($s->is_backlog || $s->is_system))
                        ->map(fn($s) => [
                            'value' => (string) $s->id,
                            'text' => $s->name,
                            'project_milestone_id' => (string) ($s->project_milestone_id ?? ''),
                        ])->values()->toArray(),
                    'assignees' => $project->activeMembers
                        ->sortBy('name')
                        ->values()
                        ->map(fn($m) => [
                            'value' => (string) $m->id,
                            'text' => $m->name,
                        ])->values()->toArray(),
                ]];
            })->toArray(),
            'statuses_by_flow' => $statusOptionsByFlow,
            'defaults' => [
                'project_id' => null,
                'priority' => 'medium',
                'due_date_time' => now(config('constants.timezone'))->addDay()->format('Y-m-d H:i'),
            ],
            'parent_options_url' => route('tasks.quick-create-parent-options'),
        ];
    }

    private function getDefaultTaskStatusIdForFlow(?string $flowType): ?int
    {
        $status = \App\Models\TaskStatus::query()
            ->active()
            ->where('flow_type', $flowType)
            ->where('is_default', 1)
            ->first();

        if (!$status) {
            $status = \App\Models\TaskStatus::query()
                ->active()
                ->where('flow_type', $flowType)
                ->orderBy('sort_order')
                ->first();
        }

        return $status?->id;
    }

    public function store(HandoffFormRequest $request)
    {
        $validated = $request->validated();
        $handoffRequest = $this->handoffServices->createHandoffRequest(
            $validated,
            $request->user()->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Handoff request created successfully.',
            'data' => $handoffRequest
        ]);
    }

    public function noted(Request $request, \App\Models\HandoffRequest $handoff_request)
    {
        $this->handoffServices->markAsNoted($handoff_request, $request->user()->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Handoff request marked as noted successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Handoff request marked as noted successfully.');
    }
}
