<?php

namespace App\Http\Controllers;

use App\Exports\MilestoneReportExport;
use App\Exports\ProjectReportExport;
use App\Exports\SprintReportExport;
use App\Exports\TaskReportExport;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Services\Reports\DailyTimeReportService;
use App\Services\Reports\MilestoneReportService;
use App\Services\Reports\ProductivityReportService;
use App\Services\Reports\ProjectReportService;
use App\Services\Reports\SprintReportService;
use App\Services\Reports\TaskReportService;
use App\Services\Reports\TimeTrackingReportService;
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
            'project-report_' . $generatedAt . '.xlsx'
        );
    }

    /** ============ Project::Milestone ============ */

    // MILESTONE REPORT
    public function milestone(Request $request)
    {
        $this->pageTitle = 'Milestone Report';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);

        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        $milestones = $this->milestoneReportService->getMilestones($request, $perPage);
        $projects = $this->milestoneReportService->getProjects($request);
        $owners = $this->milestoneReportService->getOwners($request);
        $statuses = $this->milestoneReportService->getStatuses();
        $columns = $this->milestoneReportService->getColumnLabels();
        $milestoneStats = $this->milestoneReportService->getStats($request);

        return view('reports.milestone', compact(
            'milestones',
            'perPage',
            'projects',
            'owners',
            'statuses',
            'columns',
            'milestoneStats'
        ));
    }

    // MILESTONE REPORT EXPORT
    public function milestoneExport(Request $request)
    {
        $milestones = $this->milestoneReportService->exportMilestones($request);
        $columns = $this->milestoneReportService->resolveExportColumns($request);
        $generatedAt = now((string) config('constants.timezone', config('app.timezone')));

        return Excel::download(
            new MilestoneReportExport(
                $milestones,
                $columns,
                $request->all(),
                $generatedAt
            ),
            'milestone-report_' . $generatedAt . '.xlsx'
        );
    }

    /** ============ Project::SPRINT ============ */

    // SPRINT REPORT
    public function sprint(Request $request)
    {
        $this->pageTitle = 'Sprint Report';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);

        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        $sprints = $this->sprintReportService->getSprints($request, $perPage);
        $projects = $this->sprintReportService->getProjects($request);
        $milestones = $this->sprintReportService->getMilestones($request);
        $statuses = $this->sprintReportService->getStatuses();
        $columns = $this->sprintReportService->getColumnLabels();
        $sprintStats = $this->sprintReportService->getStats($request);

        return view('reports.sprint', compact(
            'sprints',
            'perPage',
            'projects',
            'milestones',
            'statuses',
            'columns',
            'sprintStats'
        ));
    }

    // SPRINT REPORT EXPORT
    public function sprintExport(Request $request)
    {
        $sprints = $this->sprintReportService->exportSprints($request);
        $columns = $this->sprintReportService->resolveExportColumns($request);
        $generatedAt = now((string) config('constants.timezone', config('app.timezone')));

        return Excel::download(
            new SprintReportExport(
                $sprints,
                $columns,
                $request->all(),
                $generatedAt
            ),
            'sprint-report_' . $generatedAt . '.xlsx'
        );
    }

    /** ============ Project::Task ============ */

    // TASK REPORT
    public function taskReport(Request $request)
    {
        $this->pageTitle = 'Task Report';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);

        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        $tasks = $this->taskReportService->getTasks($request, $perPage);
        $projects = $this->taskReportService->getProjects($request);
        $projectMilestones = $this->taskReportService->getMilestones($request);
        $projectSprints = $this->taskReportService->getSprints($request);
        $assignees = $this->taskReportService->getAssignees($request);
        $statuses = $this->taskReportService->getStatuses();
        $priorities = $this->taskReportService->getPriorityOptions();
        $taskTypeOptions = $this->taskReportService->getTaskTypes();
        $taskModeOptions = $this->taskReportService->getTaskModes();
        $columns = $this->taskReportService->getColumnLabels();
        $taskStats = $this->taskReportService->getStats($request);

        return view('reports.task', [
            'tasks' => $tasks,
            'perPage' => $perPage,
            'columns' => $columns,
            'taskStats' => $taskStats,
            'projects' => $projects,
            'projectMilestones' => $projectMilestones,
            'projectSprints' => $projectSprints,
            'assignees' => $assignees,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'taskTypeOptions' => $taskTypeOptions,
            'taskModeOptions' => $taskModeOptions,
        ]);
    }

    // TASK REPORT EXPORT
    public function taskReportExport(Request $request)
    {
        $tasks = $this->taskReportService->exportTasks($request);
        $columns = $this->taskReportService->resolveExportColumns($request);
        $generatedAt = now((string) config('constants.timezone', config('app.timezone')));

        return Excel::download(
            new TaskReportExport(
                $tasks,
                $columns,
                $request->all(),
                $generatedAt
            ),
            'task-report_' . $generatedAt . '.xlsx'
        );
    }

    public function task(Request $request)
    {
        return $this->taskReport($request);
    }

    public function taskExport(Request $request)
    {
        return $this->taskReportExport($request);
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
        $canExport = $reportService->canExport($request);

        $requestStatuses = [
            Task::REQUEST_PENDING => 'Pending',
            Task::REQUEST_APPROVED => 'Approved',
            Task::REQUEST_REJECTED => 'Rejected',
        ];

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
            'canExport',
            'dailyStats',
            'requestStatuses'
        ));
    }

    // TIME TRACKING REPORT EXPORT
    public function timeTrackingExport(Request $request)
    {
        return app(TimeTrackingReportService::class)->export($request);
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

    /** ============ Performance::Productivity ============ */

    // Productivity report
    public function productivity(Request $request)
    {
        $this->pageTitle = 'Productivity Report';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);

        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));
        $reportService = app(ProductivityReportService::class);
        $reportService->normalizeRequestFilters($request);
        $reportData = $reportService->getReportData($request, $perPage);

        return view('reports.productivity', array_merge($reportData, [
            'perPage' => $perPage,
        ]));
    }

    // Productivity report export
    public function productivityExport(Request $request)
    {
        $reportService = app(ProductivityReportService::class);
        return $reportService->export($request);
    }
}
