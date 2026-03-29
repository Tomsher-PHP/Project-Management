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

        return view('settings.project-stages.index', compact('projectStages', 'perPage'));
    }

    public function store(ProjectStageRequest $request)
    {
        $projectStage = ProjectStage::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Project stage created successfully.',
            'data' => $projectStage
        ]);
    }

    public function update(ProjectStageRequest $request, ProjectStage $projectStage)
    {
        $projectStage->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Project stage updated successfully.',
            'data' => $projectStage
        ]);
    }

    public function destroy(ProjectStage $projectStage)
    {
        if ($projectStage->default) {
            return redirect()
                ->route('settings.project_stages.index')
                ->with('error', 'Default project stage cannot be deleted.');
        }

        $projectStage->delete();

        return redirect()
            ->route('settings.project-stages.index')
            ->with('success', 'Project stage deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $projectStage = ProjectStage::findOrFail($request->id);
        $projectStage->status = !$projectStage->status;
        $projectStage->save();

        return response()->json([
            'success' => true,
            'status' => $projectStage->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
