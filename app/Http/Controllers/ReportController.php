<?php

namespace App\Http\Controllers;

use App\Exports\ProjectReportExport;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\Reports\DailyReportService;
use App\Services\Reports\DailyTimeReportService;
use App\Services\Reports\MilestoneReportService;
use App\Services\Reports\ProjectReportService;
use App\Services\Reports\SprintReportService;
use App\Services\Reports\TaskReportService;
use App\Services\Reports\TimeTrackingReportService;
use App\Services\TaskFilterService;
use App\Services\TaskFormService;
use App\Services\TaskQueryService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    protected ProjectReportService $projectReportService;
    protected TaskReportService $taskReportService;
    protected TimeTrackingReportService $timeTrackingReportService;
    protected MilestoneReportService $milestoneReportService;
    protected SprintReportService $sprintReportService;

    public function __construct(
        ProjectReportService $projectReportService,
        TaskReportService $taskReportService,
        TimeTrackingReportService $timeTrackingReportService,
        MilestoneReportService $milestoneReportService,
        SprintReportService $sprintReportService,
    ) {
        $this->projectReportService = $projectReportService;
        $this->taskReportService = $taskReportService;
        $this->timeTrackingReportService = $timeTrackingReportService;
        $this->milestoneReportService = $milestoneReportService;
        $this->sprintReportService = $sprintReportService;
    }

    /** ============ Performance::Daily Time ============ */

    // Daily Time report
    public function dailyTime(Request $request)
    {
        $this->pageTitle = 'Daily Time Report';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);

        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));
        $reportService = app(DailyTimeReportService::class);
        $reportService->normalizeRequestFilters($request);
        $reportData = $reportService->getReportData($request, $perPage);

        return view('reports.daily-time', array_merge($reportData, [
            'perPage' => $perPage,
        ]));
    }

    // Daily Time report export
    public function dailyTimeExport(Request $request)
    {
        $reportService = app(DailyTimeReportService::class);
        return $reportService->export($request);
    }

    /** ============ Performance::Time Tracking ============ */

    // TIME TRACKING REPORT
    public function timeTracking(Request $request)
    {
        $this->pageTitle = 'Time Tracking Report';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);

        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $reportService = app(TimeTrackingReportService::class);

        $reports = $reportService->getReports($request, $perPage);
        $displayRows = $reportService->buildDisplayRows($reports, $request);

        $projects = $reportService->getFilterProjects($request);
        $projectMilestones = $reportService->getFilterMilestones($request);
        $projectSprints = $reportService->getFilterSprints($request);
        $users = $reportService->getFilterUsers($request);

        $totalMinutes = $reportService->getTotalMinutes($request);

        $columns = $reportService->getColumnLabels();

        $dailyStats = [
            'total_hours' =>  formatMinutesToHoursMinutes($totalMinutes),

            'approved_entries' => $reports->getCollection()
                ->where('is_approved', true)
                ->count(),

            'pending_entries' => $reports->getCollection()
                ->where('is_approved', false)
                ->count(),

            'active_users' => $reports->getCollection()
                ->pluck('user_id')
                ->unique()
                ->count(),

            'project_count' => $reports->getCollection()
                ->pluck('task.project_id')
                ->filter()
                ->unique()
                ->count(),

            'task_count' => $reports->getCollection()
                ->pluck('task_id')
                ->filter()
                ->unique()
                ->count(),
        ];

        return view('reports.time-tracking', compact(
            'reports',
            'projects',
            'projectMilestones',
            'projectSprints',
            'users',
            'perPage',
            'displayRows',
            'totalMinutes',
            'columns',
            'dailyStats'
        ));
    }

    // TIME TRACKING REPORT EXPORT
    public function timeTrackingExport(Request $request)
    {
        return app(TimeTrackingReportService::class)->export($request);
    }

    /** ============ Project::Project ============ */

    public function getProjectsByFlow(Request $request)
    {

        $flows = $request->project_flow;

        $projects = Project::query();

        if (!empty($flows)) {
            $projects->whereIn('project_flow', $flows);
        }

        return response()->json([
            'projects' => $projects
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        ]);
    }

    // PROJECT REPORT
    public function project(Request $request)
    {
        $this->pageTitle = 'Project Report';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);

        $selectedFlows = (array) $request->input('project_flow', []);

        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projects = $this->projectReportService->getProjects($request, $perPage);

        $customers = Customer::active()->get();
        $statuses = ProjectStatus::active()->orderBy('sort_order', 'asc')->get();
        $priorities = config('project_constants.project_priorities');
        $types = config('project_constants.project_flows');

        // Project dropdown filter
        $projectsFilter = Project::select('id', 'name')
            ->when(!empty($selectedFlows), function ($query) use ($selectedFlows) {
                $query->whereIn('project_flow', $selectedFlows);
            })
            ->orderBy('name')
            ->get();

        $columns = [
            'project_name' => 'Project Name',
            'customer' => 'Customer',
            'sales_person' => 'Sales Person',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'estimated_hours' => 'Estimated Hours',
            'actual_hours' => 'Actual Hours',
            'progress' => 'Progress',
            'priority' => 'Priority',
            'milestone_status' => 'Milestone Status',
            'status' => 'Status',
            'stage' => 'Stage',
            'actions' => 'Actions',
        ];

        /*
        |--------------------------------------------------------------------------
        | FILTERED STATS
        |--------------------------------------------------------------------------
        */

        $filteredQuery = Project::query()
            ->accessibleBy(auth()->user())
            ->filter($request->all());

        $projectStats = [
            'total' => (clone $filteredQuery)->count(),

            'completed' => (clone $filteredQuery)
                ->completed()
                ->count(),

            'in_progress' => (clone $filteredQuery)
                ->inProgress()
                ->count(),

            'open' => (clone $filteredQuery)
                ->open()
                ->count(),

            'archieved' => (clone $filteredQuery)
                ->archived()
                ->count(),
        ];

        return view('reports.project', compact(
            'projects',
            'perPage',
            'customers',
            'statuses',
            'priorities',
            'types',
            'projectsFilter',
            'columns',
            'projectStats'
        ));
    }

    // PROJECT REPORT EXPORT
    public function projectExport(Request $request)
    {
        $projects = $this->projectReportService->exportProjects($request);
        $columns = $this->projectReportService->resolveExportColumns($request);
        $generatedAt = now((string) config('constants.timezone', config('app.timezone')));

        return Excel::download(
            new ProjectReportExport(
                $projects,
                $columns,
                $request->all(),
                $generatedAt
            ),
            'project-report.xlsx'
        );
    }

    /** ============ Project::Milestone ============ */

    // MILESTONE REPORT
    public function milestone(Request $request)
    {
        $this->pageTitle = 'Milestone Report';

        $this->subTitle =
            'Detailed milestone progress and delivery overview';

        view()->share([
            'pageTitle' => $this->pageTitle,
            'subTitle' => $this->subTitle,
        ]);

        $perPage = (int) $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        $milestones = $this->milestoneReportService
            ->getMilestones($request, $perPage);

        $projects = Project::accessibleBy(auth()->user())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $columns = [
            'project' => 'Project',
            'milestone' => 'Milestone Name',
            'due_date' => 'Due Date',
            'total_tasks' => 'Total Tasks',
            'completed_tasks' => 'Deliverables Completed',
            'status' => 'Status',
            'progress' => 'Progress',
        ];

        return view('reports.milestone', compact(
            'milestones',
            'perPage',
            'projects',
            'columns'
        ));
    }

    // MILESTONE REPORT EXPORT
    public function milestoneExport(Request $request)
    {
        return $this->milestoneReportService->export($request);
    }

    /** ============ Project::Task ============ */

    // TASK REPORT
    public function task(Request $request, TaskFilterService $filterService, TaskFormService $taskFormService)
    {
        $this->pageTitle = 'Task Report';

        $this->subTitle =
            'Detailed task progress and tracking overview';

        view()->share([
            'pageTitle' => $this->pageTitle,
            'subTitle' => $this->subTitle,
        ]);

        $user = $request->user();

        $perPage = (int) $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        // TASKS
        $tasks = $this->taskReportService
            ->getTasks($request, $perPage);

        /**
         * FILTERS
         */
        $baseQuery = app(TaskQueryService::class)
            ->baseQuery($user);

        $filters = $filterService
            ->getFilters($user, $baseQuery);

        $formData = $taskFormService
            ->getCreateData($user);

        /**
         * COLUMN MANAGER
         */
        $columns = [
            'task' => 'Task',
            'project' => 'Project',
            'milestone' => 'Milestone',
            'sprint' => 'Sprint',
            'assignee' => 'Assignee',
            'estimated_hours' => 'Estimated Time',
            'actual_hours' => 'Actual Time',
            'progress' => 'Progress',
            'status' => 'Status',
        ];

        /**
         * TASK STATS
         */
        $taskBaseQuery = clone $baseQuery;

        $taskStats = [
            'total' => (clone $taskBaseQuery)->count(),

            'completed' => (clone $taskBaseQuery)
                ->whereHas('status', fn($q) => $q->where('is_completed', true))
                ->count(),

            'in_progress' => (clone $taskBaseQuery)
                ->whereHas(
                    'status',
                    fn($q) =>
                    $q->where('type', 'in_progress')
                        ->where('is_completed', false)
                )
                ->count(),

            'open' => (clone $taskBaseQuery)
                ->whereHas(
                    'status',
                    fn($q) =>
                    $q->where('type', 'open')
                        ->where('is_completed', false)
                )
                ->count(),
        ];

        return view('reports.task', [
            'tasks' => $tasks,
            'perPage' => $perPage,
            'columns' => $columns,
            'taskStats' => $taskStats,

            ...$filters,
            ...$formData,
        ]);
    }

    // TASK REPORT EXPORT
    public function taskExport(Request $request)
    {
        return $this->taskReportService
            ->export($request);
    }

    /** ============ Project::SPRINT ============ */

    // SPRINT REPORT
    public function sprint(Request $request)
    {
        $this->pageTitle = 'Sprint Report';

        $this->subTitle =
            'Detailed sprint execution and completion overview';

        view()->share([
            'pageTitle' => $this->pageTitle,
            'subTitle' => $this->subTitle,
        ]);

        $perPage = (int) $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        $sprints = $this->sprintReportService
            ->getSprints($request, $perPage);

        $projects = Project::accessibleBy(auth()->user())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $columns = [
            'project' => 'Project',
            'sprint' => 'Sprint Name',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'total_tasks' => 'Total Tasks',
            'completed_tasks' => 'Completed',
            'pending_tasks' => 'Pending',
            'status' => 'Status',
            'progress' => 'Progress',
        ];

        return view('reports.sprint', compact(
            'sprints',
            'perPage',
            'projects',
            'columns'
        ));
    }

    // SPRINT REPORT EXPORT
    public function sprintExport(Request $request)
    {
        return $this->sprintReportService
            ->export($request);
    }
}
