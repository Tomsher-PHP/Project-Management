<?php

namespace App\Http\Controllers;

use App\Models\LeaveCancellationRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestHistory;
use App\Models\UserLeaveBalance;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class LeaveCancellationRequestController extends Controller
{
    protected string $pageTitle;
    public function __construct(
        protected NotificationService $notificationService
    ) {
        $this->pageTitle = 'Leave Cancellation Requests';
        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);
    }

    /**
     * Display cancellation requests.
     */
    public function index(): View
    {
        $user = auth()->user();

        $query = LeaveCancellationRequest::with([
            'leaveRequest.user',
            'leaveRequest.leaveType',
            'requester',
            'reviewer',
        ])->latest();

        if (!$this->isSuperAdmin($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('requested_by', $user->id)
                    ->orWhereHas('leaveRequest', function ($leaveQuery) use ($user) {
                        $leaveQuery->whereJsonContains(
                            'assigned_to',
                            (int) $user->id
                        );
                    });
            });
        }

        $cancellationRequests = $query
            ->paginate(15);

        return view(
            'leave_cancellation_requests.index',
            compact('cancellationRequests')
        );
    }

    /**
     * Display a cancellation request.
     */
    public function show(
        LeaveCancellationRequest $cancellationRequest
    ): View {
        $cancellationRequest->load([
            'leaveRequest.user',
            'leaveRequest.leaveType',
            'requester',
            'reviewer',
        ]);

        $this->authorizeView($cancellationRequest);

        return view(
            'leave_cancellation_requests.show',
            compact('cancellationRequest')
        );
    }

    /**
     * Submit a cancellation request for an approved leave.
     *
     * The original leave and its balance are not changed here.
     */
    public function store(
        Request $request,
        LeaveRequest $leaveRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $user = auth()->user();

        abort_unless(
            (int) $leaveRequest->user_id === (int) $user->id,
            403,
            'Only the employee who requested the leave can request its cancellation.'
        );

        if ($leaveRequest->status !== 'approved') {
            return redirect()
                ->route('leave-requests.show', $leaveRequest)
                ->with(
                    'error',
                    'Only approved leave can have a cancellation request.'
                );
        }

        if (
            !$leaveRequest->approved_from_date
            || !$leaveRequest->approved_to_date
        ) {
            return redirect()
                ->route('leave-requests.show', $leaveRequest)
                ->with(
                    'error',
                    'The approved leave dates are missing.'
                );
        }

        $cancellationRequest = DB::transaction(
            function () use (
                $leaveRequest,
                $user,
                $validated
            ) {
                $lockedLeave = LeaveRequest::query()
                    ->whereKey($leaveRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedLeave->status !== 'approved') {
                    throw ValidationException::withMessages([
                        'leave' => 'This leave is no longer approved.',
                    ]);
                }

                $hasPendingRequest = LeaveCancellationRequest::query()
                    ->where('leave_request_id', $lockedLeave->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->exists();

                if ($hasPendingRequest) {
                    throw ValidationException::withMessages([
                        'reason' => 'A pending cancellation request already exists for this leave.',
                    ]);
                }

                return LeaveCancellationRequest::create([
                    'leave_request_id' => $lockedLeave->id,
                    'requested_by' => $user->id,
                    'reason' => $validated['reason'],
                    'status' => 'pending',
                ]);
            }
        );

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with(
                'success',
                'Leave cancellation request submitted successfully.'
            );
    }

    /**
     * Approve a cancellation request.
     *
     * Restore the leave balance and cancel the original leave
     * only after authorization and successful transaction completion.
     */
    public function approve(
        LeaveCancellationRequest $cancellationRequest
    ): RedirectResponse {
        $user = auth()->user();

        $leaveRequest = $cancellationRequest->leaveRequest;

        abort_unless(
            $this->canReview($leaveRequest, $user),
            403,
            'You are not authorized to approve this cancellation request.'
        );

        abort_if(
            (int) $leaveRequest->user_id === (int) $user->id,
            403,
            'You cannot approve a cancellation request for your own leave.'
        );

        try {
            DB::transaction(function () use (
                $cancellationRequest,
                $user
            ) {
                $cancellation = LeaveCancellationRequest::query()
                    ->whereKey($cancellationRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cancellation->status !== 'pending') {
                    throw new RuntimeException(
                        'This cancellation request has already been processed.'
                    );
                }

                $leave = LeaveRequest::query()
                    ->whereKey($cancellation->leave_request_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($leave->status !== 'approved') {
                    throw new RuntimeException(
                        'The original leave is no longer approved.'
                    );
                }

                if (
                    !$leave->approved_from_date
                    || !$leave->approved_to_date
                ) {
                    throw new RuntimeException(
                        'Approved leave dates are missing. The balance cannot be restored.'
                    );
                }

                /*
                 * Find the same active balance record used by
                 * the existing LeaveRequestController::cancel().
                 */
                $balance = UserLeaveBalance::query()
                    ->where('user_id', $leave->user_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('status', true)
                    ->whereDate(
                        'valid_from',
                        '<=',
                        Carbon::parse(
                            $leave->approved_from_date
                        )->toDateString()
                    )
                    ->whereDate(
                        'valid_to',
                        '>=',
                        Carbon::parse(
                            $leave->approved_to_date
                        )->toDateString()
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    throw new RuntimeException(
                        'No active leave balance was found for this employee, leave type, and approved date range. The cancellation cannot be approved.'
                    );
                }

                $paidDays = round(
                    (float) $leave->paid_days,
                    2
                );

                $unpaidDays = round(
                    (float) $leave->unpaid_days,
                    2
                );

                /*
                 * Restore paid balance and reverse the recorded usage.
                 * These fields follow the existing cancellation method.
                 */
                $balance->current_balance = round(
                    (float) $balance->current_balance + $paidDays,
                    2
                );

                $balance->paid_days_used = round(
                    max(
                        0,
                        (float) $balance->paid_days_used - $paidDays
                    ),
                    2
                );

                $balance->unpaid_days_used = round(
                    max(
                        0,
                        (float) $balance->unpaid_days_used - $unpaidDays
                    ),
                    2
                );

                $balance->cancelled_days_restored = round(
                    (float) ($balance->cancelled_days_restored ?? 0)
                        + $paidDays,
                    2
                );

                $balance->used_balance = round(
                    max(
                        0,
                        (float) $balance->used_balance
                            - (float) $leave->approved_duration
                    ),
                    2
                );

                $balance->updated_by = $user->id;
                $balance->save();

                /*
                 * Record balance restoration in leave history.
                 */
                $this->recordLeaveHistory(
                    leaveRequest: $leave,
                    action: 'balance_restored',
                    oldStatus: 'approved',
                    newStatus: 'approved',
                    reason: 'Leave balance restored after cancellation request approval.',
                    metadata: [
                        'cancellation_request_id' => $cancellation->id,
                        'cancelled_by' => $user->id,
                        'approved_duration' => (float) $leave->approved_duration,
                        'paid_days_restored' => $paidDays,
                        'unpaid_days_reversed' => $unpaidDays,
                        'remaining_balance' => (float) $balance->current_balance,
                    ]
                );

                /*
                 * Cancel the original leave only after balance restoration.
                 */
                $leave->status = 'cancelled';
                $leave->cancelled_by = $user->id;
                $leave->cancelled_at = now();
                $leave->cancellation_reason = $cancellation->reason;
                $leave->save();

                $this->recordLeaveHistory(
                    leaveRequest: $leave,
                    action: 'cancelled',
                    oldStatus: 'approved',
                    newStatus: 'cancelled',
                    reason: $cancellation->reason,
                    metadata: [
                        'cancellation_request_id' => $cancellation->id,
                        'cancelled_by' => $user->id,
                        'balance_restored' => true,
                    ]
                );

                /*
                 * Mark the separate cancellation request as approved.
                 */
                $cancellation->status = 'approved';
                $cancellation->reviewed_by = $user->id;
                $cancellation->reviewed_at = now();
                $cancellation->review_reason = null;
                $cancellation->save();
            });
        } catch (RuntimeException $e) {
            return redirect()
                ->route(
                    'leave-cancellation-requests.show',
                    $cancellationRequest
                )
                ->with('error', $e->getMessage());
        }

        /*
         * Notify the employee that the original leave is cancelled.
         * Reuse the notification method from the existing controller.
         */
        $cancellationRequest->refresh();
        $cancellationRequest->load('leaveRequest');

        $this->notificationService->notifyLeaveRequestCancelled(
            $cancellationRequest->leaveRequest,
            (int) $user->id
        );

        return redirect()
            ->route(
                'leave-cancellation-requests.show',
                $cancellationRequest
            )
            ->with(
                'success',
                'Cancellation request approved. The leave was cancelled and its balance was restored.'
            );
    }

    /**
     * Reject a cancellation request.
     *
     * The original approved leave and balance remain unchanged.
     */
    public function reject(
        Request $request,
        LeaveCancellationRequest $cancellationRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'review_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $user = auth()->user();

        $leaveRequest = $cancellationRequest->leaveRequest;

        abort_unless(
            $this->canReview($leaveRequest, $user),
            403,
            'You are not authorized to reject this cancellation request.'
        );

        abort_if(
            (int) $leaveRequest->user_id === (int) $user->id,
            403,
            'You cannot reject a cancellation request for your own leave.'
        );

        try {
            DB::transaction(function () use (
                $cancellationRequest,
                $user,
                $validated
            ) {
                $cancellation = LeaveCancellationRequest::query()
                    ->whereKey($cancellationRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cancellation->status !== 'pending') {
                    throw new RuntimeException(
                        'This cancellation request has already been processed.'
                    );
                }

                $cancellation->status = 'rejected';
                $cancellation->reviewed_by = $user->id;
                $cancellation->reviewed_at = now();
                $cancellation->review_reason =
                    $validated['review_reason'];
                $cancellation->save();
            });
        } catch (RuntimeException $e) {
            return redirect()
                ->route(
                    'leave-cancellation-requests.show',
                    $cancellationRequest
                )
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route(
                'leave-cancellation-requests.show',
                $cancellationRequest
            )
            ->with(
                'success',
                'Cancellation request rejected. The original approved leave remains unchanged.'
            );
    }

    /**
     * Allow the requester to withdraw their own pending request.
     */
    public function cancel(
        LeaveCancellationRequest $cancellationRequest
    ): RedirectResponse {
        $user = auth()->user();

        abort_unless(
            (int) $cancellationRequest->requested_by === (int) $user->id
            || $this->isSuperAdmin($user),
            403,
            'You are not authorized to withdraw this request.'
        );

        $updated = DB::transaction(function () use (
            $cancellationRequest,
            $user
        ) {
            $cancellation = LeaveCancellationRequest::query()
                ->whereKey($cancellationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cancellation->status !== 'pending') {
                return false;
            }

            $cancellation->status = 'cancelled';
            $cancellation->cancelled_by = $user->id;
            $cancellation->cancelled_at = now();
            $cancellation->save();

            return true;
        });

        if (!$updated) {
            return redirect()
                ->route(
                    'leave-cancellation-requests.show',
                    $cancellationRequest
                )
                ->with(
                    'error',
                    'Only pending cancellation requests can be withdrawn.'
                );
        }

        return redirect()
            ->route(
                'leave-cancellation-requests.index'
            )
            ->with(
                'success',
                'Cancellation request withdrawn successfully.'
            );
    }

    /**
     * Check whether the current user is allowed to review a request.
     */
    private function canReview(
        LeaveRequest $leaveRequest,
        $user
    ): bool {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if (!$user->can('leave_request.edit')) {
            return false;
        }

        $assignedTo = $leaveRequest->assigned_to;

        if (is_string($assignedTo)) {
            $decoded = json_decode($assignedTo, true);
            $assignedTo = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($assignedTo)) {
            return false;
        }

        return in_array(
            (int) $user->id,
            array_map('intval', $assignedTo),
            true
        );
    }

    /**
     * Check whether the current user can view a request.
     */
    private function authorizeView(
        LeaveCancellationRequest $cancellationRequest
    ): void {
        $user = auth()->user();

        if ($this->isSuperAdmin($user)) {
            return;
        }

        if (
            (int) $cancellationRequest->requested_by === (int) $user->id
        ) {
            return;
        }

        abort_unless(
            $this->canReview(
                $cancellationRequest->leaveRequest,
                $user
            ),
            403,
            'You are not authorized to view this cancellation request.'
        );
    }

    /**
     * Determine whether the current user is a super admin.
     */
    private function isSuperAdmin($user): bool
    {
        return (bool) ($user->is_super_admin ?? false);
    }

    /**
     * Record leave history.
     *
     * IMPORTANT:
     * Match the field names here to your existing
     * LeaveRequestHistory migration and model.
     */
    private function recordLeaveHistory(
        LeaveRequest $leaveRequest,
        string $action,
        string $oldStatus,
        string $newStatus,
        string $reason,
        array $metadata = []
    ): void {
        LeaveRequestHistory::create([
            'leave_request_id' => $leaveRequest->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_from_date' => $leaveRequest->approved_from_date,
            'old_to_date' => $leaveRequest->approved_to_date,
            'new_from_date' => $leaveRequest->approved_from_date,
            'new_to_date' => $leaveRequest->approved_to_date,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}
