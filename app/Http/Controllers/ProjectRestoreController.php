<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectRestoreService;
use Illuminate\Http\Request;

class ProjectRestoreController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Restore Projects';
        $this->subTitle = 'Review deleted projects and restore them with proper dependency validation';

        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    /**
     * Display a listing of soft-deleted projects.
     */
    public function index(Request $request, ProjectRestoreService $projectRestoreService)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projects = $projectRestoreService->getDeletedProjects($perPage);

        return view('projects.restore.index', compact('projects', 'perPage'));
    }

    /**
     * Restore a soft-deleted project.
     */
    public function restore($id, ProjectRestoreService $projectRestoreService)
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        $validation = $projectRestoreService->validateRestore($project);

        if (!$validation['can_restore']) {
            return redirect()
                ->back()
                ->with('error', $validation['message']);
        }

        // Restore ONLY the project
        $project->restore();

        return redirect()
            ->back()
            ->with('success', 'Project restored successfully.');
    }

    /**
     * Bulk restore soft-deleted projects.
     */
    public function bulkRestore(Request $request, ProjectRestoreService $projectRestoreService)
    {
        $validated = $request->validate([
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['integer'],
        ]);

        $result = $projectRestoreService->bulkRestoreProjects($validated['project_ids']);

        if ($result['selected_count'] === 0) {
            return redirect()
                ->back()
                ->with('error', 'No deleted projects were selected for restore.');
        }

        if ($result['restored_count'] === 0) {
            $message = 'Selected projects could not be restored due to validation failures: ' . implode('; ', $result['failed_details']);
            return redirect()
                ->back()
                ->with('error', $message);
        }

        if ($result['failed_count'] > 0) {
            $message = "Successfully restored {$result['restored_count']} project(s). {$result['failed_count']} project(s) failed validation: " . implode('; ', $result['failed_details']);
            return redirect()
                ->back()
                ->with('success', $message);
        }

        return redirect()
            ->back()
            ->with('success', "Successfully restored all {$result['restored_count']} selected project(s).");
    }
}
