<?php

namespace App\Http\Controllers;

use App\Http\Requests\BreakRequestActionRequest;
use App\Http\Requests\BreakRequestBulkActionRequest;
use App\Http\Requests\BreakWorkStoreRequest;
use App\Models\BreakWorkRequest;
use App\Services\BreakRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class BreakRequestController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Break Requests Management';
        $this->subTitle = 'Manage break work requests and approvals';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request, BreakRequestService $breakRequestService)
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));
        $selectedStatus = in_array($request->input('request_status'), BreakWorkRequest::STATUSES, true)
            ? $request->input('request_status')
            : BreakWorkRequest::STATUS_PENDING;

        return view('break-requests.index', [
            'breakRequests' => $breakRequestService->getRequestsForUser(
                $request->user(),
                $perPage,
                $selectedStatus,
                $request->all()
            ),
            'users' => $breakRequestService->getFilterOptions($request->user())['users'],
            'selectedStatus' => $selectedStatus,
            'perPage' => $perPage,
        ]);
    }

    public function store(BreakWorkStoreRequest $request, BreakRequestService $breakRequestService): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $startedAt = $request->normalizedStartedAt();
        $endedAt = $request->normalizedEndedAt();

        abort_unless($startedAt && $endedAt, Response::HTTP_UNPROCESSABLE_ENTITY);

        $breakWorkRequest = $breakRequestService->store(
            $user,
            $request->validated('work_date'),
            $startedAt,
            $endedAt,
            $request->durationSeconds(),
            $request->validated('description')
        );

        $message = 'Break work request submitted successfully.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'id' => $breakWorkRequest->id,
                    'status' => $breakWorkRequest->status,
                    'processing_status' => $breakWorkRequest->processing_status,
                ],
            ], Response::HTTP_CREATED);
        }

        return redirect()->back()->with('success', $message);
    }

    public function handleAction(BreakRequestActionRequest $request, BreakWorkRequest $breakWorkRequest, string $action, BreakRequestService $breakRequestService): RedirectResponse
    {
        abort_unless(in_array($action, ['approve', 'reject'], true), Response::HTTP_NOT_FOUND);

        try {
            $breakRequestService->handleAction(
                $request->user(),
                $breakWorkRequest,
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
                ->with('error', $firstMessage ?: 'Unable to process the break work request.')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('break-requests.index')
            ->with('success', $action === 'approve'
                ? 'Break work request approved successfully.'
                : 'Break work request rejected successfully.');
    }

    public function handleBulkAction(BreakRequestBulkActionRequest $request, string $action, BreakRequestService $breakRequestService): RedirectResponse
    {
        abort_unless(in_array($action, ['approve', 'reject'], true), Response::HTTP_NOT_FOUND);

        try {
            $processedCount = $breakRequestService->handleBulkAction(
                $request->user(),
                $request->validated('break_request_ids'),
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
                ->with('error', $firstMessage ?: 'Unable to process the break work requests.')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('break-requests.index')
            ->with('success', $action === 'approve'
                ? "{$processedCount} break work request(s) approved successfully."
                : "{$processedCount} break work request(s) rejected successfully.");
    }
}
