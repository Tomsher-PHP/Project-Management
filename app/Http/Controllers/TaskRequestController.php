<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequestActionRequest;
use App\Http\Requests\TaskRequestBulkActionRequest;
use App\Models\Task;
use App\Services\TaskRequestServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskRequestController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Task Requests Management';
        $this->subTitle = 'Manage your task requests and approvals';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request, TaskRequestServices $taskRequestServices)
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        $selectedStatus = in_array($request->input('request_status'), ['pending', 'approved', 'rejected'], true)
            ? $request->input('request_status')
            : 'pending';
        $filterOptions = $taskRequestServices->getFilterOptions($request->user());

        return view('tasks.requests.index', [
            'tasks' => $taskRequestServices->getRequestsForUser(
                $request->user(),
                $perPage,
                $selectedStatus,
                $request->all()
            ),
            'selectedStatus' => $selectedStatus,
            'projects' => $filterOptions['projects'],
            'users' => $filterOptions['users'],
            'perPage' => $perPage,
        ]);
    }

    public function handleAction(TaskRequestActionRequest $request, Task $task, string $action, TaskRequestServices $taskRequestServices): RedirectResponse
    {
        abort_unless(in_array($action, ['approve', 'reject'], true), Response::HTTP_NOT_FOUND);

        $taskRequestServices->handleAction(
            $request->user(),
            $task,
            $action,
            $request->validated('reason')
        );

        return redirect()
            ->route('tasks.requests.index')
            ->with('success', $action === 'approve' ? 'Task request approved successfully.' : 'Task request rejected successfully.');
    }

    public function handleBulkAction(TaskRequestBulkActionRequest $request, string $action, TaskRequestServices $taskRequestServices): RedirectResponse
    {
        abort_unless(in_array($action, ['approve', 'reject'], true), Response::HTTP_NOT_FOUND);

        $processedCount = $taskRequestServices->handleBulkAction(
            $request->user(),
            $request->validated('task_ids'),
            $action,
            $request->validated('reason')
        );

        return redirect()
            ->route('tasks.requests.index')
            ->with('success', $action === 'approve'
                ? "{$processedCount} task request(s) approved successfully."
                : "{$processedCount} task request(s) rejected successfully.");
    }
}
