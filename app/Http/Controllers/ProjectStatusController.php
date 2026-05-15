<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStatusRequest;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProjectStatusController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Statuses';
        $this->subTitle = 'Manage your project statuses';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));
        $types = config('project_constants.project_status_types');

        $projectStatuses = ProjectStatus::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) ProjectStatus::max('sort_order')) + 1;

        return view('settings.project-statuses.index', compact('projectStatuses', 'perPage', 'nextSortOrder','types'));
    }

    public function store(ProjectStatusRequest $request)
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');

        $projectStatus = DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults();
            }

            return activity()->withoutLogs(fn () => ProjectStatus::create($data));
        });

        return response()->json([
            'status' => true,
            'message' => 'Project status created successfully.',
            'data' => $projectStatus
        ]);
    }

    public function update(ProjectStatusRequest $request, ProjectStatus $projectStatus)
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');

        $projectStatus = DB::transaction(function () use ($projectStatus, $data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults($projectStatus->id);
            }

            activity()->withoutLogs(fn () => $projectStatus->update($data));

            return $projectStatus->refresh();
        });

        return response()->json([
            'status' => true,
            'message' => 'Project status updated successfully.',
            'data' => $projectStatus
        ]);
    }

    public function destroy(ProjectStatus $projectStatus)
    {
        if ($projectStatus->is_system) {
            return redirect()
                ->route('settings.project_statuses.index')
                ->with('error', 'System project status cannot be deleted.');
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

    protected function clearExistingDefaults(?int $exceptId = null): void
    {
        $query = ProjectStatus::query();

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
