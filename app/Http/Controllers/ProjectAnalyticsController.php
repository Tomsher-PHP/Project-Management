<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAnalyticsService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ProjectAnalyticsController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Analytics';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    /**
     * Initial dashboard page request.
     * Note: Per AJAX requirements, this initial page request must not calculate or fetch summary counts.
     */
    public function index(Request $request, ProjectAnalyticsService $analyticsService)
    {
        $authUser = $request->user();

        // Server-side validation and authorization for requested filters
        $selectedUser = null;
        if ($request->filled('user_id')) {
            $selectedUser = $this->resolveSelectedUser($request);
            if ($selectedUser instanceof JsonResponse) {
                abort(403, 'You are not authorized to view data for this user.');
            }
        }

        $selectedProject = null;
        if ($request->filled('project_id')) {
            $selectedProject = $this->resolveSelectedProject($request);
            if ($selectedProject instanceof JsonResponse) {
                abort(403, 'You are not authorized to view data for this project.');
            }

            if ($selectedUser && ! in_array($selectedProject->id, $analyticsService->getUserInvolvedProjectIds($selectedUser))) {
                abort(403, 'This project is not associated with the selected user.');
            }
        }

        // Show only users involved in projects auth user is authorized to access
        $projectSelectableUsers = $analyticsService->getProjectSelectableUsers($authUser);

        $priorityOptions = collect(config('project_constants.task_priorities', []))->map(
            fn($config, $key) => (object) [
                'id' => $key,
                'name' => $config['label'],
            ]
        );

        $projectSummaryTiles = [
            [
                'key' => 'total_projects',
                'label' => 'Total Projects',
                'icon' => 'folder',
                'iconBg' => 'bg-primary-50 dark:bg-primary-500/10',
                'accent' => 'text-primary-500',
            ],
            [
                'key' => 'active_projects',
                'label' => 'Active Projects',
                'icon' => 'pulse',
                'iconBg' => 'bg-success-50 dark:bg-success-500/10',
                'accent' => 'text-success-500',
            ],
            [
                'key' => 'completed_projects',
                'label' => 'Completed Projects',
                'icon' => 'check',
                'iconBg' => 'bg-purple-50 dark:bg-purple-500/10',
                'accent' => 'text-purple-500',
            ],
            [
                'key' => 'total_tasks',
                'label' => 'Total Tasks',
                'icon' => 'grid',
                'iconBg' => 'bg-warning-50 dark:bg-warning-500/10',
                'accent' => 'text-warning-500',
            ],
            [
                'key' => 'formatted_worked_time',
                'label' => 'Worked Time',
                'icon' => 'clock',
                'iconBg' => 'bg-blue-50 dark:bg-blue-500/10',
                'accent' => 'text-blue-500',
            ],
            [
                'key' => 'upcoming_timelines',
                'label' => 'Upcoming Timelines',
                'icon' => 'archive',
                'iconBg' => 'bg-error-50 dark:bg-error-500/10',
                'accent' => 'text-error-400',
            ],
        ];

        $viewData = [
            'priorityOptions' => $priorityOptions,
            'projectSelectableUsers' => $projectSelectableUsers,
            'selectableUsers' => $projectSelectableUsers,
            'projectAnalyticsSelectedUserId' => $selectedUser ? (string) $selectedUser->id : '',
            'projectAnalyticsSelectedProjectId' => $selectedProject ? (string) $selectedProject->id : '',
            'selectedProject' => $selectedProject,
            'projectSummaryTiles' => $projectSummaryTiles,
        ];

        view()->share($viewData);

        return view('project-analytics.index', $viewData);
    }

    /**
     * Dedicated AJAX endpoint for project summary counts.
     */
    public function summary(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $data = $analyticsService->getProjectSummaryCounts(
            $request->user(),
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Dedicated AJAX endpoint for summary tile drill-down modal details.
     */
    public function tileDetails(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $type = (string) $request->query('type');

        $data = $analyticsService->getTileDetails(
            $request->user(),
            $type,
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'html' => view('dashboard.partials.tile-details-modal-content', $data)->render(),
        ]);
    }

    /**
     * 1. Projects by Service Type
     */
    public function projectsByServiceType(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $data = $analyticsService->getProjectsByServiceType(
            $request->user(),
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 2. Projects by Flow (Agile or Linear)
     */
    public function projectsByFlow(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $data = $analyticsService->getProjectsByFlow(
            $request->user(),
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 3. Projects by Status
     */
    public function projectsByStatus(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $data = $analyticsService->getProjectsByStatus(
            $request->user(),
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 4. Projects by Stage
     */
    public function projectsByStage(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $data = $analyticsService->getProjectsByStage(
            $request->user(),
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 5. Upcoming Timeline Expirations (ending in next 45 days)
     */
    public function upcomingTimelines(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;
        $limit = $request->integer('limit') ?: 50;
        $endDate = $request->input('end_date');
        $days = $request->integer('days') ?: 45;

        $data = $analyticsService->getUpcomingTimelineExpirations(
            $request->user(),
            $selectedUser,
            $project?->id,
            $limit,
            $endDate,
            $days
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Single project details and summary.
     */
    public function projectDetails(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        if (! $project) {
            return response()->json([
                'success' => false,
                'message' => 'Project ID is required for project details.',
            ], 422);
        }

        $data = $analyticsService->getSingleProjectDetails(
            $request->user(),
            $project,
            $selectedUser
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Single project overview sections: Time Progress, Milestone Journey burnup, and User Wise breakdown.
     */
    public function projectOverview(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        if (! $project) {
            return response()->json([
                'success' => false,
                'message' => 'Project ID is required for project overview.',
            ], 422);
        }

        $timelineProgressbars = $analyticsService->getTimelineProgressbars($project);
        $taskAssigneeOverview = $analyticsService->getTaskAssigneeOverview($project);
        $milestoneBurnupChart = $project->isAgile ? $analyticsService->getMilestoneBurnupChartData($project) : [
            'labels' => [],
            'datasets' => [],
        ];

        $totalTimelineWorked = $timelineProgressbars->sum(fn($b) => (int) $b->get('worked_seconds', 0));
        $totalTimelineEstimated = $timelineProgressbars->sum(fn($b) => (int) $b->get('estimated_seconds', 0));
        $totalTimelineCustomerEstimate = $timelineProgressbars->sum(fn($b) => (int) $b->get('customer_estimate_seconds', 0));

        $totalWorkedSeconds = (int) $taskAssigneeOverview->sum('worked_time_seconds');
        $assigneeCount = $taskAssigneeOverview->whereNotNull('id')->count();

        $formatDuration = fn(?int $seconds): string => $analyticsService->formatDuration((int) $seconds);

        $viewData = [
            'project' => $project,
            'timelineProgressbars' => $timelineProgressbars,
            'totalTimelineWorked' => $totalTimelineWorked,
            'totalTimelineEstimated' => $totalTimelineEstimated,
            'totalTimelineCustomerEstimate' => $totalTimelineCustomerEstimate,
            'taskAssigneeOverview' => $taskAssigneeOverview,
            'totalWorkedSeconds' => $totalWorkedSeconds,
            'assigneeCount' => $assigneeCount,
            'milestoneBurnupChart' => $milestoneBurnupChart,
            'hasMilestoneBurnupData' => ! empty($milestoneBurnupChart['labels']),
            'formatDuration' => $formatDuration,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'is_agile' => (bool) $project->isAgile,
                'time_progress_html' => view('project-analytics.partials.single-project-time-progress-content', $viewData)->render(),
                'user_wise_html' => view('project-analytics.partials.single-project-user-wise-content', $viewData)->render(),
                'burnup_chart' => $milestoneBurnupChart,
                'assignee_data' => $taskAssigneeOverview->values()->all(),
                'has_timelines' => $timelineProgressbars->isNotEmpty(),
                'has_milestones' => ! empty($milestoneBurnupChart['labels']),
                'has_assignees' => $taskAssigneeOverview->isNotEmpty(),
                'total_worked_formatted' => $formatDuration($totalWorkedSeconds),
                'assignee_count' => $assigneeCount,
            ],
        ]);
    }

    /**
     * 7a. Project-specific task analytics: Status chart
     */
    public function taskStatusChart(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $data = $analyticsService->getProjectTaskStatusChart(
            $request->user(),
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 7b. Project-specific task analytics: Priority chart
     */
    public function taskPriorityChart(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;

        $data = $analyticsService->getProjectTaskPriorityChart(
            $request->user(),
            $selectedUser,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 8. User/project time comparison
     */
    public function timeComparisonChart(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $resolved = $this->resolveFilters($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$selectedUser, $project] = $resolved;
        $date = $this->resolveSelectedDate($request->input('date'));

        $data = $analyticsService->getProjectTimeComparisonChart(
            $request->user(),
            $selectedUser,
            $date,
            $project?->id
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Search projects for Tom Select autocomplete, respecting access control and user involvement.
     */
    public function searchProjects(Request $request, ProjectAnalyticsService $analyticsService): JsonResponse
    {
        $selectedUser = $this->resolveSelectedUser($request);
        if ($selectedUser instanceof JsonResponse) {
            return $selectedUser;
        }

        $query = $request->input('q') ?? $request->input('query');
        $excludeId = $request->integer('exclude_id') ?: null;
        $projectId = $request->integer('project_id') ?: ($request->integer('id') ?: null);
        $page = max(1, $request->integer('page') ?: 1);
        $limit = min(100, max(1, $request->integer('limit') ?: 50));

        $data = $analyticsService->searchProjects(
            $request->user(),
            $selectedUser,
            $query,
            $excludeId,
            $projectId,
            $page,
            $limit
        );

        return response()->json($data);
    }

    /**
     * Resolve and validate user and project filters together.
     *
     * @return array{0: User|null, 1: Project|null}|JsonResponse
     */
    protected function resolveFilters(Request $request): array|JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
        ]);

        $selectedUser = $this->resolveSelectedUser($request);
        if ($selectedUser instanceof JsonResponse) {
            return $selectedUser;
        }

        $project = $this->resolveSelectedProject($request);
        if ($project instanceof JsonResponse) {
            return $project;
        }

        return [$selectedUser, $project];
    }

    /**
     * Resolve and authorize the selected user for requests.
     */
    protected function resolveSelectedUser(Request $request): User|null|JsonResponse
    {
        $authUser = $request->user();

        if (! $request->filled('user_id')) {
            return null;
        }

        $userId = (int) $request->input('user_id');

        if ($userId === (int) $authUser->id) {
            return $authUser;
        }

        $selectedUser = User::query()
            ->accessibleBy($authUser)
            ->whereKey($userId)
            ->first();

        if (! $selectedUser) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view data for this user.',
            ], 403);
        }

        return $selectedUser;
    }

    /**
     * Resolve and authorize the selected project for requests.
     */
    protected function resolveSelectedProject(Request $request): Project|null|JsonResponse
    {
        $authUser = $request->user();

        if (! $request->filled('project_id')) {
            return null;
        }

        $projectId = (int) $request->input('project_id');

        $project = Project::query()
            ->accessibleBy($authUser)
            ->whereKey($projectId)
            ->first();

        if (! $project) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view data for this project.',
            ], 403);
        }

        return $project;
    }

    /**
     * Parse date or default to today.
     */
    protected function resolveSelectedDate(mixed $date): Carbon
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
