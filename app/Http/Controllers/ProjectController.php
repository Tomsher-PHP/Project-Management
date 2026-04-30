<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsProjectActivityQueries;
use App\Http\Requests\ProjectFileRequest;
use App\Http\Requests\ProjectCommentRequest;
use App\Http\Requests\ProjectNoteRequest;
use App\Http\Requests\ProjectPaymentStatusRequest;
use App\Http\Requests\ProjectRequest;
use App\Models\Attachment;
use App\Models\AgileMilestone;
use App\Models\AgileMilestoneStatus;
use App\Models\AgileSprint;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectNote;
use App\Models\ProjectCategory;
use App\Models\ProjectSprint;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\AttachmentService;
use App\Services\ProjectServices;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    use BuildsProjectActivityQueries;

    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Management';
        $this->subTitle = 'Manage your projects';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request, ProjectServices $service)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projects = Project::accessibleBy(auth()->user())
            ->filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $projects->getCollection()->transform(function ($project) use ($service) {
            $project->project_timeline = $service->getTimelines($project)['projectTimeline'];

            return $project;
        });

        $customers = Customer::active()->get();
        $statuses = ProjectStatus::active()->orderBy('sort_order', 'asc')->get();
        $priorities = config('project_constants.project_priorities');
        $types = config('project_constants.project_flows');

        return view('projects.index', compact('projects', 'perPage', 'customers', 'statuses', 'priorities', 'types'));
    }

    public function store(ProjectRequest $request, ProjectServices $service)
    {
        $project = $service->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Project created successfully.',
            'redirect_url' => route('projects.edit', $project->id),
        ], Response::HTTP_OK);
    }

    public function edit(Project $project, ProjectServices $service)
    {
        return view('projects.detail-page', array_merge([
            'project' => $project,
            'projectActivitiesCount' => $this->getProjectActivitiesQuery($project)->count(),
            'projectCommentsCount' => $project->comments()->count(),
        ], $this->getProjectHeaderData($project, $service)));
    }

    public function activityModal(Project $project): JsonResponse
    {
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

    public function commentsModal(Project $project): JsonResponse
    {
        $comments = $this->getRecentProjectComments($project);
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

    public function storeComment(ProjectCommentRequest $request, Project $project): JsonResponse
    {
        $project->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated()['comment'],
        ]);

        $comments = $this->getRecentProjectComments($project);
        $totalComments = $project->comments()->count();

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'count' => $totalComments,
            'html' => view('projects.partials.modals.comments-content', [
                'project' => $project,
                'comments' => $comments,
                'totalComments' => $totalComments,
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function tab(Request $request, Project $project, string $tab, ProjectServices $service)
    {
        $allowedTabs = ['milestones', 'tasks', 'team', 'scope', 'notes', 'history', 'settings'];
        abort_unless(in_array($tab, $allowedTabs, true), Response::HTTP_NOT_FOUND);

        return response()->json([
            'status' => true,
            'tab' => $tab,
            'html' => $this->renderTab($project, $tab, $service, $request),
        ], Response::HTTP_OK);
    }

    public function update(ProjectRequest $request, Project $project, ProjectServices $service)
    {
        $project = $service->update($project, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'project' => $project,
            'project_header' => $this->renderProjectHeader($project, $service),
        ], Response::HTTP_OK);
    }

    public function updateProjectStatus(Request $request, Project $project, ProjectServices $service)
    {
        $latestStatusChangeDate = $this->getLatestProjectStatusChangeDate($project);
        $validator = Validator::make($request->all(), [
            'status_id' => 'required|exists:project_statuses,id',
            'change_date' => 'required|date',
            'remarks' => 'nullable|string|max:150',
        ]);
        $this->applyProjectChangeDateValidation($validator, $request->input('change_date'), $latestStatusChangeDate);
        $validated = $validator->validate();

        $project = $service->updateStatus(
            $project,
            (int) $validated['status_id'],
            $validated['change_date'],
            $validated['remarks'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Project status updated successfully.',
            'project_header' => $this->renderProjectHeader($project, $service),
            'history_tab' => $this->renderHistoryTab($project),
        ], Response::HTTP_OK);
    }

    public function updateProjectStage(Request $request, Project $project, ProjectServices $service)
    {
        $request->merge([
            'project_stage_id' => $request->filled('project_stage_id') ? $request->input('project_stage_id') : null,
        ]);

        $latestStageChangeDate = $this->getLatestProjectStageChangeDate($project);
        $validator = Validator::make($request->all(), [
            'project_stage_id' => 'nullable|exists:project_stages,id',
            'change_date' => 'required|date',
            'remarks' => 'nullable|string|max:150',
        ]);
        $this->applyProjectChangeDateValidation($validator, $request->input('change_date'), $latestStageChangeDate);
        $validated = $validator->validate();

        $project = $service->updateStage(
            $project,
            isset($validated['project_stage_id']) ? (int) $validated['project_stage_id'] : null,
            $validated['change_date'],
            $validated['remarks'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Project stage updated successfully.',
            'project_header' => $this->renderProjectHeader($project, $service),
            'history_tab' => $this->renderHistoryTab($project),
        ], Response::HTTP_OK);
    }

    public function storeNote(ProjectNoteRequest $request, Project $project, ProjectServices $service)
    {
        $service->createNote($project, $request->validated());
        $projectNotes = $this->getPaginatedProjectNotes($project, 1);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'html' => view('projects.partials.project-notes-list', [
                'projectNotes' => $projectNotes,
                'canRemove' => auth()->user()->can('project.remove_notes_files'),
                'canCreate' => auth()->user()->can('project.add_notes_files'),
            ])->render(),
            'current_page' => $projectNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNote(Request $request, Project $project, ProjectNote $note, AttachmentService $attachmentService)
    {
        abort_unless($note->project_id === $project->id, Response::HTTP_NOT_FOUND);

        $attachmentService->delete($note->attachments);
        $note->delete();
        $projectNotes = $this->getPaginatedProjectNotes($project, (int) $request->input('notes_page', 1));

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully.',
            'html' => view('projects.partials.project-notes-list', [
                'projectNotes' => $projectNotes,
                'canRemove' => auth()->user()->can('project.remove_notes_files'),
                'canCreate' => auth()->user()->can('project.add_notes_files'),
            ])->render(),
            'current_page' => $projectNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function deleteNoteAttachment(Request $request, Project $project, ProjectNote $note, Attachment $attachment, AttachmentService $attachmentService)
    {
        abort_unless($note->project_id === $project->id, Response::HTTP_NOT_FOUND);
        abort_unless(
            $attachment->link_type === ProjectNote::class && (int) $attachment->link_id === (int) $note->id,
            Response::HTTP_NOT_FOUND
        );

        $attachmentService->delete(collect([$attachment]));
        $projectNotes = $this->getPaginatedProjectNotes($project, (int) $request->input('notes_page', 1));

        return response()->json([
            'success' => true,
            'message' => 'File removed successfully.',
            'html' => view('projects.partials.project-notes-list', [
                'projectNotes' => $projectNotes,
                'canRemove' => auth()->user()->can('project.remove_notes_files'),
                'canCreate' => auth()->user()->can('project.add_notes_files'),
            ])->render(),
            'current_page' => $projectNotes->currentPage(),
        ], Response::HTTP_OK);
    }

    public function uploadScopeFile(ProjectFileRequest $request, Project $project, ProjectServices $service)
    {
        $attachments = $service->uploadFile($project, $request->validated(), 'scope_files');

        $html = [];
        foreach ($attachments as $file) {
            $file->load('addedBy');
            $html[] = view('projects.partials.file-item', ['file' => $file])->render();
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully',
            'html' => $html
        ], Response::HTTP_OK);
    }

    public function deleteScopeFile(Project $project, $fileId, AttachmentService $attachmentService)
    {
        $attachment = $project->attachments()->where('id', $fileId)->get();
        $attachmentService->delete($attachment);

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.',
        ], Response::HTTP_OK);
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $project = Project::findOrFail($request->id);
        $project->is_active = !$project->is_active;
        $project->save();

        return response()->json([
            'success' => true,
            'is_active' => $project->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    private function getPaginatedProjectNotes(Project $project, int $page)
    {
        $perPage = 3;
        $total = $project->projectNotes()->count();
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min(max($page, 1), $lastPage);

        return $project->projectNotes()
            ->with(['attachments', 'addedBy'])
            ->paginate($perPage, ['*'], 'notes_page', $page)
            ->withPath(route('projects.edit', $project))
            ->withQueryString();
    }

    private function renderTab(Project $project, string $tab, ProjectServices $service, Request $request): string
    {
        return match ($tab) {
            'milestones' => $this->renderMilestonesTab($project),
            'tasks' => app(ProjectTaskController::class)->renderTasksTab($project),
            'team' => $this->renderTeamTab($project),
            'scope' => $this->renderScopeTab($project),
            'notes' => $this->renderNotesTab($project, $request),
            'history' => $this->renderHistoryTab($project),
            'settings' => $this->renderSettingsTab($project),
            default => abort(Response::HTTP_NOT_FOUND),
        };
    }

    private function getRecentProjectComments(Project $project, int $limit = 10): Collection
    {
        return $project->comments()
            ->with('user.primaryAttachment')
            ->latest()
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();
    }

    private function renderMilestonesTab(Project $project): string
    {
        $projectMilestones = $project->projectMilestones()
            ->with([
                'addedBy',
                'updatedBy',
                'status',
                'owner',
            ])
            ->withCount('projectSprints')
            ->orderForDisplay()
            ->get();

        $agileMilestones = AgileMilestone::active()->orderBy('sort_order', 'asc')->get();
        $agileSprints = AgileSprint::active()->orderBy('sort_order', 'asc')->get();
        $agileMilestoneStatuses = AgileMilestoneStatus::active()->orderBy('sort_order', 'asc')->get();
        $assignableUsers = $project->activeMembers()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);
        $trashedProjectMilestones = ProjectMilestone::onlyTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('deleted_at')
            ->get();
        $trashedProjectSprintsByMilestone = ProjectSprint::onlyTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('deleted_at')
            ->get()
            ->groupBy('project_milestone_id');

        return view('projects.partials.tabs.milestones', compact(
            'project',
            'projectMilestones',
            'agileMilestones',
            'agileSprints',
            'agileMilestoneStatuses',
            'assignableUsers',
            'trashedProjectMilestones',
            'trashedProjectSprintsByMilestone'
        ))->render();
    }

    private function renderTeamTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $project->load('members');

        $existingMemberIds = $project->members
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $users = app(UserService::class)
            ->getAccessibleUsers(auth()->user(), [], $salesPersonIds)
            ->reject(fn($user) => in_array((int) $user->id, $existingMemberIds, true))
            ->values();

        $projectRoles = config('project_constants.project_roles');

        return view('projects.partials.tabs.team', compact('project', 'users', 'projectRoles'))->render();
    }

    private function renderScopeTab(Project $project): string
    {
        $project->load(['scopeFiles.addedBy']);

        return view('projects.partials.tabs.scope', compact('project'))->render();
    }

    private function renderNotesTab(Project $project, Request $request): string
    {
        $projectNotes = $this->getPaginatedProjectNotes($project, (int) $request->input('notes_page', 1));

        return view('projects.partials.tabs.notes', compact('project', 'projectNotes'))->render();
    }

    private function renderHistoryTab(Project $project): string
    {
        $statusHistory = $project->statusHistories()
            ->with(['status', 'fromStatus', 'addedBy:id,name'])
            ->orderByDesc('added_at')
            ->get()
            ->map(function ($history) {
                return [
                    'from_label' => $history->fromStatus?->name ?? 'Start',
                    'from_color' => $history->fromStatus?->color ?: '#CBD5E1',
                    'to_label' => $history->status?->name ?? 'No Status',
                    'to_color' => $history->status?->color ?: '#CBD5E1',
                    'changed_at' => $this->convertStoredTimestampToConfigTimezone($history->getRawOriginal('added_at')),
                    'changed_by' => $history->addedBy?->name ?? '--',
                    'remarks' => $history->remarks,
                ];
            })
            ->values();

        $stageHistory = $project->stageHistories()
            ->with(['stage', 'fromStage', 'addedBy:id,name'])
            ->orderByDesc('added_at')
            ->get()
            ->map(function ($history) {
                return [
                    'from_label' => $history->fromStage?->name ?? 'Start',
                    'from_color' => $history->fromStage?->color ?: '#CBD5E1',
                    'to_label' => $history->stage?->name ?? 'No Stage',
                    'to_color' => $history->stage?->color ?: '#CBD5E1',
                    'changed_at' => $this->convertStoredTimestampToConfigTimezone($history->getRawOriginal('added_at')),
                    'changed_by' => $history->addedBy?->name ?? '--',
                    'remarks' => $history->remarks,
                ];
            })
            ->values();

        $currentStatus = [
            'label' => $project->projectStatus?->name ?? 'No Status',
            'color' => $project->projectStatus?->color ?: '#CBD5E1',
        ];
        $currentStage = [
            'label' => $project->projectStage?->name ?? 'No Stage',
            'color' => $project->projectStage?->color ?: '#CBD5E1',
        ];

        return view('projects.partials.tabs.history', compact(
            'project',
            'statusHistory',
            'stageHistory',
            'currentStatus',
            'currentStage'
        ))->render();
    }

    private function renderSettingsTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $selectedCustomerId = $project->customer_id;
        $selectedCategoryId = $project->project_category_id;
        $selectedTechnologyIds = $project->technologies()->get()->pluck('id')->map(fn($id) => (int) $id)->all();

        $users = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $salesPersonIds);
        $project->load('technologies');

        $customers = Customer::forForm($selectedCustomerId)->get();
        $projectCategories = ProjectCategory::forForm($selectedCategoryId, 'sort_order')->get();
        $projectTechnologies = Technology::forForm($selectedTechnologyIds, 'sort_order')->get();

        $nextProjectCategorySortOrder = ((int) ProjectCategory::max('sort_order')) + 1;
        $nextProjectTechnologySortOrder = ((int) Technology::max('sort_order')) + 1;

        $priorities = config('project_constants.project_priorities');
        // $statuses = ProjectStatus::active()->orderBy('sort_order', 'asc')->get();
        // $projectStages = ProjectStage::active()->orderBy('sort_order', 'asc')->get();

        return view('projects.partials.tabs.settings', compact(
            'project',
            'users',
            'customers',
            'projectCategories',
            'nextProjectCategorySortOrder',
            'projectTechnologies',
            'nextProjectTechnologySortOrder',
            'priorities'
            // 'statuses',
            // 'projectStages',
        ))->render();
    }

    private function getProjectHeaderData(Project $project, ProjectServices $service): array
    {
        $project->loadMissing(['customer', 'projectStatus', 'projectStage', 'addedBy']);
        $timelines = $service->getTimelines($project);
        $paymentSummary = $service->getPaymentSummary($project);
        $statusChangeMinDate = $this->getLatestProjectStatusChangeDate($project);
        $stageChangeMinDate = $this->getLatestProjectStageChangeDate($project);

        $selectedStatusId = $project->status_id;
        $selectedStageId = $project->project_stage_id;

        return [
            'priority' => config('project_constants.project_priorities')[$project->priority] ?? null,
            'projectTimeline' => $timelines['projectTimeline'],
            'customerTimeline' => $timelines['customerTimeline'],
            'paymentSummary' => $paymentSummary,
            'projectStatuses' => ProjectStatus::forForm($selectedStatusId, ['order_by' => 'sort_order'])->get(),
            'projectStages' => ProjectStage::forForm($selectedStageId, ['order_by' => 'sort_order'])->get(),
            'statusChangeMinDate' => $statusChangeMinDate?->toDateString(),
            'statusChangeMinDateLabel' => $statusChangeMinDate ? AppServiceProvider::formatAppDate($statusChangeMinDate) : null,
            'stageChangeMinDate' => $stageChangeMinDate?->toDateString(),
            'stageChangeMinDateLabel' => $stageChangeMinDate ? AppServiceProvider::formatAppDate($stageChangeMinDate) : null,
        ];
    }

    private function renderProjectHeader(Project $project, ProjectServices $service): string
    {
        return view('projects.partials.header', array_merge([
            'project' => $project,
        ], $this->getProjectHeaderData($project, $service)))->render();
    }

    private function getLatestProjectStatusChangeDate(Project $project): ?Carbon
    {
        $latestDate = $project->statusHistories()
            ->reorderDesc('added_at')
            ->orderByDesc('id')
            ->value('added_at');

        return $this->convertStoredTimestampToConfigTimezone($latestDate)?->startOfDay();
    }

    private function getLatestProjectStageChangeDate(Project $project): ?Carbon
    {
        $latestDate = $project->stageHistories()
            ->reorderDesc('added_at')
            ->orderByDesc('id')
            ->value('added_at');

        return $this->convertStoredTimestampToConfigTimezone($latestDate)?->startOfDay();
    }

    private function applyProjectChangeDateValidation($validator, ?string $changeDate, ?Carbon $minimumDate): void
    {
        if (blank($changeDate) || ! $minimumDate) {
            return;
        }

        $validator->after(function ($validator) use ($changeDate, $minimumDate) {
            try {
                $submittedDate = Carbon::parse($changeDate, config('constants.timezone'))->startOfDay();

                if ($submittedDate->lt($minimumDate)) {
                    $validator->errors()->add(
                        'change_date',
                        'The change date must be on or after ' . AppServiceProvider::formatAppDate($minimumDate) . '.'
                    );
                }
            } catch (\Throwable) {
                // The base date validation already reports invalid formats.
            }
        });
    }

    private function convertStoredTimestampToConfigTimezone($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC')->timezone(config('constants.timezone'));
        } catch (\Throwable) {
            try {
                return Carbon::parse($value, config('constants.timezone'))->timezone(config('constants.timezone'));
            } catch (\Throwable) {
                return null;
            }
        }
    }

    public function updateProjectPaymentStatus(ProjectPaymentStatusRequest $request, Project $project, ProjectServices $service): JsonResponse
    {
        $validated = $request->validated();

        $service->createPayment($project, $validated);
        $project = $project->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Project payment status updated successfully.',
            'project_header' => $this->renderProjectHeader($project, $service),
        ], Response::HTTP_OK);
    }
}
