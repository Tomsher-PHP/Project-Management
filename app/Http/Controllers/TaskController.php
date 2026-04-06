<?php

namespace App\Http\Controllers;

use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Task Management';
        $this->subTitle = 'Manage your tasks';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $tasks = ProjectTask::query()
            ->accessibleBy($request->user())
            ->with([
                'project:id,name,project_flow',
                'projectModule:id,name',
                'projectSprint:id,name',
                'currentAssignee:id,name',
                'status:id,name,color',
            ])
            ->filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $statuses = ProjectTaskStatus::active()
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name']);

        $assignees = User::active()
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $types = config('project_constants.task_type', []);
        $modes = config('project_constants.task_mode', []);
        $priorities = config('project_constants.task_priorities', []);

        return view('tasks.index', compact('tasks', 'perPage', 'statuses', 'assignees', 'types', 'modes', 'priorities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectTask $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
}
