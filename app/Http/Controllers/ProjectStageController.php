<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStageRequest;
use App\Models\ProjectStage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectStageController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Stages';
        $this->subTitle = 'Manage your project stages';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projectStages = ProjectStage::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) ProjectStage::max('sort_order')) + 1;

        return view('settings.project-stages.index', compact('projectStages', 'perPage', 'nextSortOrder'));
    }

    public function store(ProjectStageRequest $request)
    {
        $projectStage = activity()->withoutLogs(fn () => ProjectStage::create($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Project stage created successfully.',
            'data' => $projectStage
        ]);
    }

    public function update(ProjectStageRequest $request, ProjectStage $projectStage)
    {
        activity()->withoutLogs(fn () => $projectStage->update($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Project stage updated successfully.',
            'data' => $projectStage
        ]);
    }

    public function destroy(ProjectStage $projectStage)
    {
        if ($projectStage->is_system) {
            return redirect()
                ->route('settings.project_stages.index')
                ->with('error', 'System project stage cannot be deleted.');
        }

        activity()->withoutLogs(fn () => $projectStage->delete());

        return redirect()
            ->route('settings.project-stages.index')
            ->with('success', 'Project stage deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $projectStage = ProjectStage::findOrFail($request->id);
        $projectStage->is_active = !$projectStage->is_active;
        activity()->withoutLogs(fn () => $projectStage->save());

        return response()->json([
            'success' => true,
            'is_active' => $projectStage->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
