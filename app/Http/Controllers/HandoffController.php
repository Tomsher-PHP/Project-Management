<?php

namespace App\Http\Controllers;

use App\Http\Requests\HandoffFormRequest;
use App\Models\HandoffRequest;
use App\Models\TaskStatus;
use App\Services\HandoffServices;
use App\Services\TaskFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HandoffController extends Controller
{
    protected HandoffServices $handoffServices;

    public function __construct(HandoffServices $handoffServices)
    {
        $this->handoffServices = $handoffServices;
        view()->share([
            'pageTitle' => 'Handoff Requests',
        ]);
    }

    public function index(Request $request, TaskFormService $taskFormService)
    {
        $user = $request->user();
        $canViewHandoffs = $user->is_super_admin || $user->canAny([
            'handoff_request.view',
            'handoff_request.view_all',
        ]);
        $isHandoffTarget = HandoffRequest::query()
            ->where('target_user_id', $user->id)
            ->exists();

        if (! $canViewHandoffs && ! $isHandoffTarget) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 15));

        $selectedStatus = in_array($request->input('request_status'), ['pending', 'noted', 'assigned'], true)
            ? $request->input('request_status')
            : 'pending';

        $statusValue = match ($selectedStatus) {
            'pending' => HandoffRequest::STATUS_PENDING,
            'noted' => HandoffRequest::STATUS_NOTED,
            'assigned' => HandoffRequest::STATUS_ASSIGNED,
            default => HandoffRequest::STATUS_PENDING,
        };

        $filters = array_merge($request->all(), ['status' => $statusValue]);

        $handoffRequests = $this->handoffServices->getHandoffRequestsForList($request->user(), $perPage, $filters);
        $handoffRequests->getCollection()->load('targetUser.primaryAttachment');
        $filterOptions = $this->handoffServices->getFilterOptions($request->user());

        $taskFormData = [];
        $taskCreateDependencies = [];
        if ($request->user()->can('task.create') || $request->user()->can('request-task')) {
            $taskFormData = $taskFormService->getCreateData($request->user());
            $taskCreateDependencies = $taskFormService->getInitialDependencies();
        }

        return view('requests.handoff-requests.index', array_merge([
            'handoffRequests' => $handoffRequests,
            'perPage' => $perPage,
            'selectedStatus' => $selectedStatus,
            'taskCreateDependencies' => $taskCreateDependencies,
        ], $filterOptions, $taskFormData));
    }

    public function store(HandoffFormRequest $request)
    {
        $validated = $request->validated();
        $handoffRequest = $this->handoffServices->createHandoffRequest(
            $validated,
            $request->user()->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Handoff request created successfully.',
            'data' => $handoffRequest
        ]);
    }

    public function update(HandoffFormRequest $request, HandoffRequest $handoff_request)
    {
        if ((int) $handoff_request->user_id !== (int) $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only edit your own handoff request.',
            ], 403);
        }

        if ((int) $handoff_request->status !== HandoffRequest::STATUS_PENDING) {
            return response()->json([
                'status' => false,
                'message' => 'Only pending handoff requests can be edited.',
            ], 422);
        }

        $validated = $request->validated();
        $handoffRequest = $this->handoffServices->updateHandoffRequest(
            $handoff_request,
            $validated,
            $request->user()->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Handoff request updated successfully.',
            'data' => $handoffRequest,
        ]);
    }

    public function noted(Request $request, HandoffRequest $handoff_request)
    {
        $this->handoffServices->markAsNoted($handoff_request, $request->user()->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Handoff request marked as noted successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Handoff request marked as noted successfully.');
    }
}
