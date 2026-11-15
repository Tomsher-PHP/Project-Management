<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsProjectActivityQueries;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ProjectRestoreService;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectRestoreController extends Controller
{
    use BuildsProjectActivityQueries;

    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Restore Projects';

        view()->share(['pageTitle' => $this->pageTitle]);
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

    public function show($id, ProjectRestoreService $projectRestoreService)
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        view()->share([
            'pageTitle' => 'Project Management',
            'subTitle' => 'Manage your projects',
        ]);

        return view('projects.detail-page', $projectRestoreService->getDeletedProjectDetailData($project));
    }

    public function tab(Request $request, $id, string $tab, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        $allowedTabs = [
            'overview',
            'milestones',
            'tasks',
            'team',
            'scope',
            'notes',
            'checklists',
            'history',
            'settings',
            'payments',
            'meetings',
            'hosting',
            'timelines',
        ];

        abort_unless(in_array($tab, $allowedTabs, true), Response::HTTP_NOT_FOUND);

        return response()->json([
            'status' => true,
            'tab' => $tab,
            'html' => $projectRestoreService->renderDeletedProjectTab($project, $tab, $request),
        ], Response::HTTP_OK);
    }

    public function activityModal($id, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        $activities = $this->getProjectActivitiesQuery($project)
            ->with([
                'subject',
                'causer' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        User::class => ['primaryAttachment'],
                    ]);
                },
            ])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.modals.activity-content', [
                'project' => $project,
                'activities' => $activities,
                'viewAllUrl' => route('activity.log', ['project_id' => $project->id]),
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function commentsModal($id, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        $comments = $projectRestoreService->getRecentProjectComments($project);
        $totalComments = $project->comments()->count();

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.modals.comments-content', [
                'project' => $project,
                'comments' => $comments,
                'totalComments' => $totalComments,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function milestoneSprints($id, ProjectMilestone $projectMilestone, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        abort_unless((int) $projectMilestone->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);

        return app(ProjectSprintController::class)->index($project, $projectMilestone);
    }

    public function taskGroupsPage(Request $request, $id, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        return app(ProjectTaskController::class)->taskGroupsPage($request, $project);
    }

    public function taskGroup($id, string $group, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        return app(ProjectTaskController::class)->taskGroup($project, $group);
    }

    public function meetingGroup(Request $request, $id, string $group, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        return response()->json([
            'status' => true,
            'html' => view('projects.partials.meetings.meeting-rows', [
                'project' => $project,
                'groupKey' => $group,
                'meetings' => [],
                'showEmptyState' => true,
            ])->render(),
            'pagination' => [
                'page' => 1,
                'next_page' => null,
                'has_more_pages' => false,
                'total' => 0,
            ],
        ]);
    }

    public function hostingIndex(Request $request, $id, ProjectRestoreService $projectRestoreService): JsonResponse
    {
        $project = $projectRestoreService->findDeletedProjectOrFail((int) $id);
        $projectRestoreService->assertDeletedProject($project);

        $html = '<div class="px-6 py-10 text-center"><div class="mx-auto max-w-md rounded-[8px] border border-dashed border-bgray-300 bg-bgray-50 px-6 py-8 text-center dark:border-darkblack-400 dark:bg-darkblack-500"><p class="text-base font-semibold text-error-300">No server & hosting records.</p><p class="mt-2 text-sm text-error-300">Server & hosting records are not available for deleted projects.</p></div></div>';

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => [
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1,
            ],
        ]);
    }
}
