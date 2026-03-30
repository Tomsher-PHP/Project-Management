<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectFileRequest;
use App\Http\Requests\ProjectRequest;
use App\Models\Attachment;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectCategory;
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

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projects = Project::accessibleBy(auth()->user())
            ->filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $customers = Customer::active()->get();
        $statuses = ProjectStatus::active()->orderBy('order', 'asc')->get();
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

    public function edit(Project $project)
    {
        $salesPersonIds = $project->sales_person_id ? [$project->sales_person_id] : [];
        $users = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $salesPersonIds);

        $customers = Customer::active()->get();
        $statuses = ProjectStatus::active()->orderBy('order', 'asc')->get();
        $projectCategories = ProjectCategory::active()->orderBy('order', 'asc')->get();
        $projectTechnologies = Technology::active()->orderBy('order', 'asc')->get();

        $priorities = config('constants.project_priorities');
        $projectStages = config('constants.project_stages');
        $projectRoles = config('constants.project_roles');

        return view('projects.detail-page', compact(
            'project',
            'users',
            'customers',
            'statuses',
            'priorities',
            'projectStages',
            'projectCategories',
            'projectRoles',
            'projectTechnologies'
        ));
    }

    public function update(ProjectRequest $request, Project $project, ProjectServices $service)
    {
        $project = $service->update($project, $request->validated());

        $priority = config('constants.project_priorities')[$project->priority] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'project' => $project,
            'project_header' => view('projects.partials.header', [
                'project' => $project,
                'priority' => $priority
            ])->render(),
        ], Response::HTTP_OK);
    }

    public function updateNotes(Request $request, Project $project)
    {
        $project->update([
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notes updated successfully.',
            'project' => $project,
        ], Response::HTTP_OK);
    }

    public function uploadFile(ProjectFileRequest $request, Project $project, ProjectServices $service)
    {
        $attachments = $service->uploadFile($project, $request->validated());

        $html = [];
        foreach ($attachments as $file) {
            $html[] = view('projects.partials.file-item', ['file' => $file])->render();
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully',
            'html' => $html
        ], Response::HTTP_OK);
    }

    public function deleteFile(Project $project, $fileId, AttachmentService $attachmentService)
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
}
