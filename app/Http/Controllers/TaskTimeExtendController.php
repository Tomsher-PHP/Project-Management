<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskTimeExtendApproveRequest;
use App\Http\Requests\TaskTimeExtendRejectRequest;
use App\Http\Requests\TaskTimeExtendStoreRequest;
use App\Models\Task;
use App\Models\TaskExtendTimeRequest;
use App\Services\TaskTimeExtendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskTimeExtendController extends Controller
{
    protected $service;
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct(TaskTimeExtendService $service)
    {
        $this->service = $service;
        $this->pageTitle = 'Task Time Extend Requests';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));
        $selectedStatus = in_array($request->input('request_status'), ['pending', 'approved', 'rejected'], true)
            ? $request->input('request_status')
            : 'pending';

        $filterOptions = $this->service->getFilterOptions($request->user());

        return view('requests.task-time-extend-request.index', [
            'extendRequests' => $this->service->getRequests(
                $request->user(),
                $perPage,
                $selectedStatus,
                $request->all()
            ),
            'users' => $filterOptions['users'],
            'projects' => $filterOptions['projects'],
            'selectedStatus' => $selectedStatus,
            'perPage' => $perPage,
        ]);
    }

    public function show(TaskExtendTimeRequest $extendTimeRequest): JsonResponse
    {
        $extendTimeRequest->loadMissing(['task.project', 'user']);

        return response()->json([
            'status' => true,
            'data' => [
                'project_name' => $extendTimeRequest->task?->project?->name ?? '--',
                'task_name' => $extendTimeRequest->task?->name ?? '--',
                'user_name' => $extendTimeRequest->user?->name ?? '--',
                'current_estimate_formatted' => $extendTimeRequest->estimated_time_formatted,
                'new_estimated_time_minutes' => (int) ($extendTimeRequest->new_estimated_time_seconds / 60),
                'reason' => $extendTimeRequest->reason ?? '--',
            ]
        ]);
    }

    public function approve(TaskTimeExtendApproveRequest $request, TaskExtendTimeRequest $extendTimeRequest)
    {
        $this->service->approve($request->user(), $extendTimeRequest, (int) $request->validated('new_estimated_time_minutes'));

        $message = 'Task time extend request approved successfully.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('tasks.extend-time-requests.index')
            ->with('success', $message);
    }

    public function reject(TaskTimeExtendRejectRequest $request, TaskExtendTimeRequest $extendTimeRequest)
    {
        $this->service->reject($request->user(), $extendTimeRequest, $request->validated('reason'));

        $message = 'Task time extend request rejected successfully.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('tasks.extend-time-requests.index')
            ->with('success', $message);
    }

    public function store(TaskTimeExtendStoreRequest $request, Task $task): JsonResponse
    {
        // Check if current user is assignee
        if (!$this->service->canRequestEstimate($task)) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot request an estimate change for this task.',
            ], 403);
        }

        $validated = $request->validated();

        $this->service->createRequest($task, $validated);

        return response()->json([
            'status' => true,
            'message' => 'Estimate change request submitted successfully.',
        ]);
    }

    public function pending(Task $task): JsonResponse
    {
        // Check if current user is assignee
        if ((int) Auth::id() !== (int) $task->current_assignee_id) {
            return response()->json([
                'status' => false,
                'message' => 'Only the task assignee can request an estimate change.',
            ], 403);
        }

        // Find any existing request, regardless of status.
        $existingRequest = TaskExtendTimeRequest::where('task_id', $task->id)
            ->latest('id')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'status' => true,
                'data' => [
                    'new_estimated_time_minutes' => (int) ($existingRequest->new_estimated_time_seconds / 60),
                    'reason' => $existingRequest->reason,
                    'request_status' => $existingRequest->status,
                    'message' => 'Only one extend time request is allowed per task.',
                ],
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => null,
        ]);
    }
}
