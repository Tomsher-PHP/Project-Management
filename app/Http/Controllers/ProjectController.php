<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectFileRequest;
use App\Http\Requests\ProjectNoteRequest;
use App\Http\Requests\ProjectRequest;
use App\Models\Attachment;
use App\Models\AgileModule;
use App\Models\AgileSprint;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectNote;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\ProjectServices;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
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
        $priorities = config('constants.project_priorities');
        $types = config('constants.project_types');

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
        $projectActivities = $project->activities()
            ->with('causer')
            ->latest()
            ->limit(3)
            ->get();

        return view('projects.detail-page', array_merge([
            'project' => $project,
            'projectActivities' => $projectActivities,
        ], $this->getProjectHeaderData($project, $service)));
    }

    public function tab(Request $request, Project $project, string $tab, ProjectServices $service)
    {
        $allowedTabs = ['modules', 'tasks', 'team', 'scope', 'notes', 'settings'];
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
        $validated = $request->validate([
            'status_id' => 'required|exists:project_statuses,id',
        ]);

        $project = $service->updateStatus($project, (int) $validated['status_id']);

        return response()->json([
            'success' => true,
            'message' => 'Project status updated successfully.',
            'project_header' => $this->renderProjectHeader($project, $service),
        ], Response::HTTP_OK);
    }

    public function updateProjectStage(Request $request, Project $project, ProjectServices $service)
    {
        $request->merge([
            'project_stage_id' => $request->filled('project_stage_id') ? $request->input('project_stage_id') : null,
        ]);

        $validated = $request->validate([
            'project_stage_id' => 'nullable|exists:project_stages,id',
        ]);

        $project = $service->updateStage(
            $project,
            isset($validated['project_stage_id']) ? (int) $validated['project_stage_id'] : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Project stage updated successfully.',
            'project_header' => $this->renderProjectHeader($project, $service),
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
        $project->status = !$project->status;
        $project->save();

        return response()->json([
            'success' => true,
            'status' => $project->status,
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
            'modules' => $this->renderModulesTab($project),
            'tasks' => view('projects.partials.tabs.tasks', compact('project'))->render(),
            'team' => $this->renderTeamTab($project),
            'scope' => $this->renderScopeTab($project),
            'notes' => $this->renderNotesTab($project, $request),
            'settings' => $this->renderSettingsTab($project),
            default => abort(Response::HTTP_NOT_FOUND),
        };
    }

    private function renderModulesTab(Project $project): string
    {
        $projectModules = $project->projectModules()
            ->with([
                'addedBy',
                'updatedBy',
                'projectSprints' => fn ($query) => $query
                    ->with(['addedBy', 'updatedBy'])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $agileModules = AgileModule::active()->orderBy('sort_order', 'asc')->get();
        $agileSprints = AgileSprint::active()->orderBy('sort_order', 'asc')->get();
        $trashedProjectModules = ProjectModule::onlyTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('deleted_at')
            ->get();

        return view('projects.partials.tabs.modules', compact(
            'project',
            'projectModules',
            'agileModules',
            'agileSprints',
            'trashedProjectModules'
        ))->render();
    }

    private function renderTeamTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $users = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $salesPersonIds);
        $projectRoles = config('constants.project_roles');
        $project->load('members');

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

    private function renderSettingsTab(Project $project): string
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $users = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $salesPersonIds);
        $project->load('technologies');

        $customers = Customer::active()->get();
        $statuses = ProjectStatus::active()->orderBy('sort_order', 'asc')->get();
        $projectCategories = ProjectCategory::active()->orderBy('sort_order', 'asc')->get();
        $projectTechnologies = Technology::active()->orderBy('sort_order', 'asc')->get();
        $projectStages = ProjectStage::active()->orderBy('sort_order', 'asc')->get();
        $priorities = config('constants.project_priorities');

        return view('projects.partials.tabs.settings', compact(
            'project',
            'users',
            'customers',
            'statuses',
            'projectCategories',
            'projectTechnologies',
            'projectStages',
            'priorities'
        ))->render();
    }

    private function getProjectHeaderData(Project $project, ProjectServices $service): array
    {
        $project->loadMissing(['customer', 'projectStatus', 'projectStage', 'addedBy']);
        $timelines = $service->getTimelines($project);

        return [
            'priority' => config('constants.project_priorities')[$project->priority] ?? null,
            'projectTimeline' => $timelines['projectTimeline'],
            'customerTimeline' => $timelines['customerTimeline'],
            'projectStatuses' => ProjectStatus::active()->orderBy('sort_order', 'asc')->get(),
            'projectStages' => ProjectStage::active()->orderBy('sort_order', 'asc')->get(),
        ];
    }

    private function renderProjectHeader(Project $project, ProjectServices $service): string
    {
        return view('projects.partials.header', array_merge([
            'project' => $project,
        ], $this->getProjectHeaderData($project, $service)))->render();
    }
}
