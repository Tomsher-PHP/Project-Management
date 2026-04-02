<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStatusRequest;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectStatusController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Statuses';
        $this->subTitle = 'Manage your project statuses';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projectStatuses = ProjectStatus::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();

        return view('settings.project-statuses.index', compact('projectStatuses', 'perPage'));
    }

    public function store(ProjectStatusRequest $request)
    {
        $projectStatus = activity()->withoutLogs(fn () => ProjectStatus::create($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Project status created successfully.',
            'data' => $projectStatus
        ]);
    }

    public function update(ProjectStatusRequest $request, ProjectStatus $projectStatus)
    {
        activity()->withoutLogs(fn () => $projectStatus->update($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Project status updated successfully.',
            'data' => $projectStatus
        ]);
    }

    public function destroy(ProjectStatus $projectStatus)
    {
        if ($projectStatus->is_default) {
            return redirect()
                ->route('settings.project_statuses.index')
                ->with('error', 'Default project status cannot be deleted.');
        }

        activity()->withoutLogs(fn () => $projectStatus->delete());

        return redirect()
            ->route('settings.project-statuses.index')
            ->with('success', 'Project status deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $projectStatus = ProjectStatus::findOrFail($request->id);
        $projectStatus->is_active = !$projectStatus->is_active;
        activity()->withoutLogs(fn () => $projectStatus->save());

        return response()->json([
            'success' => true,
            'is_active' => $projectStatus->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
