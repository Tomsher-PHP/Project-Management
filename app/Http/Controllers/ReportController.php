<?php

namespace App\Http\Controllers;

use App\Exports\ProjectReportExport;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Services\Reports\ProjectReportService;
use App\Services\Reports\TaskReportService;
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

    public function __construct(ProjectReportService $projectReportService, TaskReportService $taskReportService)
    {
        $this->projectReportService = $projectReportService;
        $this->taskReportService = $taskReportService;
    }

    /**
     * PROJECT REPORT
     */
    public function project(Request $request)
    {
        $this->pageTitle = 'Project Report';
        $this->subTitle = 'Detailed project performance and progress overview';

        view()->share([
            'pageTitle' => $this->pageTitle,
            'subTitle' => $this->subTitle,
        ]);

        $perPage = $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        $projects = $this->projectReportService
            ->getProjects($request, $perPage);

        $customers = Customer::active()->get();

        $statuses = ProjectStatus::active()
            ->orderBy('sort_order', 'asc')
            ->get();

        $priorities = config('project_constants.project_priorities');
        // dd($priorities);

        $types = config('project_constants.project_flows');

        $projectsFilter = Project::select('id', 'name')
            ->orderBy('name')
            ->get();
      

        return view('reports.project', compact(
            'projects',
            'perPage',
            'customers',
            'statuses',
            'priorities',
            'types',
            'projectsFilter'
        ));
    }

    public function export(Request $request)
    {
        $projects = $this->projectReportService
            ->exportProjects($request);

        return Excel::download(
            new ProjectReportExport($projects),
            'project-report.xlsx'
        );
    }

    public function task(Request $request, TaskFilterService $filterService, TaskFormService $taskFormService) {
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

        $tasks = $this->taskReportService
            ->getTasks($request, $perPage);

        /**
         * Reuse same filters
         */
        $baseQuery = app(TaskQueryService::class)
            ->baseQuery($user);

        $filters = $filterService
            ->getFilters($user, $baseQuery);

        $formData = $taskFormService
            ->getCreateData($user);

        return view('reports.task', [
            'tasks' => $tasks,
            'perPage' => $perPage,

            ...$filters,
            ...$formData,
        ]);
    }

    /**
     * TASK EXPORT
     */
    public function taskExport(Request $request)
    {
        return $this->taskReportService
            ->export($request);
    }
}