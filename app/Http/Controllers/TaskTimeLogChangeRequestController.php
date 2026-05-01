<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskTimeLogChangeRequestActionRequest;
use App\Http\Requests\TaskTimeLogChangeRequestBulkActionRequest;
use App\Http\Requests\StoreTaskTimeLogChangeRequest;
use App\Models\TaskTimeLogChangeRequest;
use App\Services\TaskTimeLogChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TaskTimeLogChangeRequestController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Task Time Log Change Requests';
        $this->subTitle = 'Review submitted task time log change requests';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request, TaskTimeLogChangeRequestService $taskTimeLogChangeRequestService)
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));
        $selectedStatus = in_array($request->input('request_status'), ['pending', 'approved', 'rejected'], true)
            ? $request->input('request_status')
            : 'pending';

        return view('tasks.time-log-change-requests.index', [
            'changeRequests' => $taskTimeLogChangeRequestService->getRequestsForUser($request->user(), $perPage, $selectedStatus, $request->all()),
            'users' => $taskTimeLogChangeRequestService->getFilterOptions($request->user())['users'],
            'perPage' => $perPage,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function store(StoreTaskTimeLogChangeRequest $request, TaskTimeLogChangeRequestService $taskTimeLogChangeRequestService): JsonResponse
    {
        $timeLog = $request->resolveTimeLog();

        abort_unless($timeLog, Response::HTTP_NOT_FOUND);

        $changeRequest = $taskTimeLogChangeRequestService->create(
            $request->user(),
            $timeLog,
            [
                'new_started_at' => $request->normalizedNewStartedAt(),
                'new_ended_at' => $request->normalizedNewEndedAt(),
                'reason' => $request->validated('reason'),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Time change request submitted successfully.',
            'data' => [
                'id' => $changeRequest->id,
                'task_time_log_id' => $changeRequest->task_time_log_id,
                'request_status' => $changeRequest->status,
            ],
        ], Response::HTTP_CREATED);
    }

    public function handleAction(
        TaskTimeLogChangeRequestActionRequest $request,
        TaskTimeLogChangeRequest $changeRequest,
        string $action,
        TaskTimeLogChangeRequestService $taskTimeLogChangeRequestService
    ): RedirectResponse {
        abort_unless(in_array($action, ['approve', 'reject'], true), Response::HTTP_NOT_FOUND);

        try {
            $taskTimeLogChangeRequestService->handleAction(
                $request->user(),
                $changeRequest,
                $action,
                $request->validated('reason')
            );
        } catch (ValidationException $exception) {
            $firstMessage = collect($exception->errors())
                ->flatten()
                ->filter()
                ->first();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $firstMessage ?: 'Unable to process the time log change request.')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('tasks.time-log-change-requests.index')
            ->with('success', $action === 'approve'
                ? 'Time log change request approved successfully.'
                : 'Time log change request rejected successfully.');
    }

    public function handleBulkAction(
        TaskTimeLogChangeRequestBulkActionRequest $request,
        string $action,
        TaskTimeLogChangeRequestService $taskTimeLogChangeRequestService
    ): RedirectResponse {
        abort_unless(in_array($action, ['approve', 'reject'], true), Response::HTTP_NOT_FOUND);

        try {
            $processedCount = $taskTimeLogChangeRequestService->handleBulkAction(
                $request->user(),
                $request->validated('change_request_ids'),
                $action,
                $request->validated('reason')
            );
        } catch (ValidationException $exception) {
            $firstMessage = collect($exception->errors())
                ->flatten()
                ->filter()
                ->first();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $firstMessage ?: 'Unable to process the time log change requests.')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('tasks.time-log-change-requests.index')
            ->with('success', $action === 'approve'
                ? "{$processedCount} time log change request(s) approved successfully."
                : "{$processedCount} time log change request(s) rejected successfully.");
    }
}
