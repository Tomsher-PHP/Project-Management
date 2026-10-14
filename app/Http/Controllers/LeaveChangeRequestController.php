<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveChangeRequest;
use App\Models\LeaveChangeRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestHistory;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserLeaveBalance;
use App\Services\AttachmentService;
use App\Services\LeaveBalanceService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveChangeRequestController extends Controller
{
    protected string $pageTitle;
    public function __construct(
        protected LeaveBalanceService $leaveBalanceService,
        protected NotificationService $notificationService,
        protected AttachmentService $attachmentService
    ) {
        $this->pageTitle = 'Leave Change Requests';
        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);
    }

    /**
     * Display leave change requests.
     */
    public function index(): View
    {
        $user = auth()->user();

        $query = LeaveChangeRequest::with([
            'leaveRequest.user',
            'requestedBy',
            'oldLeaveType',
            'newLeaveType',
            'approvedBy',
            'rejectedBy',
        ]);

        if (
            !$user->is_super_admin
            && !$user->can('leave_change_request.approve')
        ) {
            $query->where(function ($q) use ($user) {
                $q->where('requested_by', $user->id)
                    ->orWhereJsonContains(
                        'assigned_to',
                        (int) $user->id
                    );
            });
        }

        $changeRequests = $query
            ->latest()
            ->paginate(20);

        return view(
            'leave-change-requests.index',
            compact('changeRequests')
        );
    }

    /**
     * Display the change request form.
     */
    public function create(LeaveRequest $leaveRequest): View
    {
        $this->ensureCanRequestChange($leaveRequest);

        $leaveTypes = LeaveType::query()
            ->orderBy('name')
            ->get();

        return view(
            'leave-change-requests.create',
            compact('leaveRequest', 'leaveTypes')
        );
    }

    /**
     * Store a leave change request.
     */
    public function store(
        StoreLeaveChangeRequest $request,
        LeaveRequest $leaveRequest
    ): RedirectResponse {
        $this->ensureCanRequestChange($leaveRequest);

        if ($leaveRequest->status !== 'approved') {
            return back()->with(
                'error',
                'Only approved leave can be changed.'
            );
        }

        $oldFrom = $leaveRequest->approved_from_date;
        $oldTo = $leaveRequest->approved_to_date;

        if (!$oldFrom || !$oldTo) {
            return back()->with(
                'error',
                'The original approved leave has no valid approved dates.'
            );
        }

        $employee = $leaveRequest->user;

        if (!$employee) {
            return back()->with(
                'error',
                'The employee associated with this leave could not be found.'
            );
        }

        $pendingExists = LeaveChangeRequest::query()
            ->where('leave_request_id', $leaveRequest->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            return back()->with(
                'error',
                'A leave change request is already pending for this leave.'
            );
        }

        $assignedTo = collect([
            $employee->reporter->id ?? null,
            $employee->manager->id ?? null,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Include active Super Admins among the approvers.
        $superAdminIds = User::query()
            ->where('is_super_admin', true)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $assignedTo = array_values(array_unique(array_merge(
            $assignedTo,
            $superAdminIds
        )));

        if (!$assignedTo) {
            return back()->with(
                'error',
                'No approver could be assigned to this request.'
            );
        }

        $changeRequest = null;

        try {
            DB::transaction(function () use (
                $request,
                $leaveRequest,
                $oldFrom,
                $oldTo,
                $assignedTo,
                &$changeRequest
            ) {
                // Recheck under a lock to avoid concurrent submissions.
                $lockedLeave = LeaveRequest::query()
                    ->whereKey($leaveRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedLeave->status !== 'approved') {
                    throw ValidationException::withMessages([
                        'leave_request' =>
                            'The original leave is no longer approved.',
                    ]);
                }

                $pendingExists = LeaveChangeRequest::query()
                    ->where('leave_request_id', $lockedLeave->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->exists();

                if ($pendingExists) {
                    throw ValidationException::withMessages([
                        'leave_request' =>
                            'A leave change request is already pending.',
                    ]);
                }

                $changeRequest = LeaveChangeRequest::create([
                    'leave_request_id' => $lockedLeave->id,
                    'requested_by' => auth()->id(),
                    'assigned_to' => $assignedTo,

                    'old_leave_type_id' => $lockedLeave->leave_type_id,
                    'old_from_date' => $oldFrom,
                    'old_to_date' => $oldTo,
                    'old_duration' => $lockedLeave->approved_duration
                        ?? $lockedLeave->duration,
                    'old_day_type' => $lockedLeave->type,
                    'old_half_day_type' => $lockedLeave->half_day_type,

                    'new_leave_type_id' => $request->new_leave_type_id,
                    'new_from_date' => $request->new_from_date,
                    'new_to_date' => $request->new_to_date,
                    'new_duration' => $request->new_duration,
                    'new_day_type' => $request->new_day_type,
                    'new_half_day_type' => $request->new_half_day_type,

                    'reason' => $request->reason,
                    'status' => 'pending',
                ]);

                if (
                    $request->hasFile('attachment')
                    && $request->file('attachment')->isValid()
                ) {
                    $this->attachmentService->upload(
                        $request->file('attachment'),
                        'leave_change_requests',
                        $changeRequest,
                        'public',
                        'public',
                        false,
                        'leave_change_request'
                    );
                }

                $this->addHistory(
                    $lockedLeave,
                    'change_submitted',
                    'approved',
                    'approved',
                    $oldFrom,
                    $oldTo,
                    $request->new_from_date,
                    $request->new_to_date,
                    $request->reason,
                    [
                        'leave_change_request_id' => $changeRequest->id,
                        'old_leave_type_id' => $lockedLeave->leave_type_id,
                        'new_leave_type_id' => $request->new_leave_type_id,
                    ]
                );
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Leave change request submission failed.', [
                'leave_request_id' => $leaveRequest->id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Unable to submit the leave change request.');
        }


        if (!$changeRequest instanceof LeaveChangeRequest) {
            Log::error('Leave change request was not created successfully.', [
                'leave_request_id' => $leaveRequest->id,
                'user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Unable to submit the leave change request.');
        }

        try {
            $this->sendSubmittedNotification($changeRequest);
        } catch (\Throwable $e) {
            Log::error('Leave change submission notification failed.', [
                'leave_change_request_id' => $changeRequest->id,
                'message' => $e->getMessage(),
            ]);
        }


        return redirect()
            ->route('leave-change-requests.show', $changeRequest)
            ->with('success', 'Leave change request submitted successfully.');
    }

    /**
     * Display change request details.
     */
    public function show(
        LeaveChangeRequest $leaveChangeRequest
    ): View {
        $this->authorizeView($leaveChangeRequest);

        $leaveChangeRequest->load([
            'leaveRequest.user',
            'requestedBy',
            'oldLeaveType',
            'newLeaveType',
            'approvedBy',
            'rejectedBy',
        ]);

        return view(
            'leave-change-requests.show',
            compact('leaveChangeRequest')
        );
    }

    /**
     * Approve a pending leave change request.
     */
    public function approve(
        LeaveChangeRequest $leaveChangeRequest
    ): RedirectResponse {
        $this->authorizeApprove($leaveChangeRequest);

        try {
            DB::transaction(function () use ($leaveChangeRequest) {
                // Lock and reload both records before changing balances.
                $change = LeaveChangeRequest::query()
                    ->whereKey($leaveChangeRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($change->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' =>
                            'Only pending leave change requests can be approved.',
                    ]);
                }

                $leave = LeaveRequest::query()
                    ->whereKey($change->leave_request_id)
                    ->lockForUpdate()
                    ->first();

                if (!$leave || $leave->status !== 'approved') {
                    throw ValidationException::withMessages([
                        'leave_request' =>
                            'The original leave could not be found or is no longer approved.',
                    ]);
                }

                $newFrom = Carbon::parse($change->new_from_date)->startOfDay();
                $newTo = Carbon::parse($change->new_to_date)->startOfDay();

                if ($newTo->lt($newFrom)) {
                    throw ValidationException::withMessages([
                        'new_to_date' =>
                            'The new end date must be on or after the start date.',
                    ]);
                }

                if (
                    $change->new_day_type === 'half_day'
                    && !$newFrom->isSameDay($newTo)
                ) {
                    throw ValidationException::withMessages([
                        'new_to_date' =>
                            'A half-day leave must start and end on the same date.',
                    ]);
                }

                $overlap = $this->findOverlappingLeave(
                    (int) $leave->user_id,
                    $newFrom,
                    $newTo,
                    (int) $leave->id
                );

                if ($overlap) {
                    throw ValidationException::withMessages([
                        'new_from_date' =>
                            'The new dates overlap with another pending or approved leave.',
                    ]);
                }

                // Calculate duration with the existing service.
                $duration = (float) $this->leaveBalanceService
                    ->calculateDuration(
                        $change->new_day_type,
                        $newFrom,
                        $newTo
                    );

                if ($duration <= 0) {
                    throw ValidationException::withMessages([
                        'new_duration' => 'The new leave duration is invalid.',
                    ]);
                }

                $oldFrom = $leave->approved_from_date;
                $oldTo = $leave->approved_to_date;
                $oldStatus = $leave->status;
                $oldDuration = (float) (
                    $leave->approved_duration ?? $leave->duration
                );
                $oldPaidDays = (float) ($leave->paid_days ?? 0);
                $oldUnpaidDays = (float) ($leave->unpaid_days ?? 0);

                /*
                 * Reverse the balance consumption for the old leave.
                 * Then apply the new leave's balance using the same
                 * monthly entitlement and paid/unpaid allocation rules.
                 */
                $this->restoreOldBalance(
                    $leave,
                    $oldFrom,
                    $oldTo,
                    $oldDuration,
                    $oldPaidDays,
                    $oldUnpaidDays
                );

                $balanceResult = $this->applyNewBalance(
                    $leave,
                    (int) $change->new_leave_type_id,
                    $change->new_day_type,
                    $newFrom,
                    $newTo,
                    $duration
                );

                $leave->leave_type_id = $change->new_leave_type_id;
                $leave->approved_from_date = $newFrom->toDateString();
                $leave->approved_to_date = $newTo->toDateString();
                $leave->approved_duration = $duration;
                $leave->duration = $duration;
                $leave->type = $change->new_day_type;
                $leave->half_day_type = $change->new_half_day_type;
                $leave->paid_days = $balanceResult['paid_days'];
                $leave->unpaid_days = $balanceResult['unpaid_days'];
                $leave->approved_by = auth()->id();
                $leave->approved_at = now();
                $leave->save();

                $change->status = 'approved';
                $change->approved_by = auth()->id();
                $change->approved_at = now();
                $change->save();

                $this->addHistory(
                    $leave,
                    'change_approved',
                    $oldStatus,
                    'approved',
                    $oldFrom,
                    $oldTo,
                    $newFrom,
                    $newTo,
                    $change->reason,
                    [
                        'leave_change_request_id' => $change->id,
                        'old_leave_type_id' => $change->old_leave_type_id,
                        'new_leave_type_id' => $change->new_leave_type_id,
                        'old_duration' => $oldDuration,
                        'new_duration' => $duration,
                        'paid_days' => $balanceResult['paid_days'],
                        'unpaid_days' => $balanceResult['unpaid_days'],
                    ]
                );

                $this->addHistory(
                    $leave,
                    'balance_adjusted',
                    'approved',
                    'approved',
                    $oldFrom,
                    $oldTo,
                    $newFrom,
                    $newTo,
                    'Leave balance adjusted after approving a leave change.',
                    [
                        'leave_change_request_id' => $change->id,
                        'old_paid_days' => $oldPaidDays,
                        'old_unpaid_days' => $oldUnpaidDays,
                        'new_paid_days' => $balanceResult['paid_days'],
                        'new_unpaid_days' => $balanceResult['unpaid_days'],
                        'remaining_balance' => $balanceResult['remaining_balance'],
                    ]
                );
            });
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Leave change request approval failed.', [
                'leave_change_request_id' => $leaveChangeRequest->id,
                'leave_request_id' => $leaveChangeRequest->leave_request_id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with(
                'error',
                'Unable to approve the leave change request. Please check the application log.'
            );
        }

        try {
            $leaveChangeRequest->refresh();
            $this->sendApprovedNotification($leaveChangeRequest);
        } catch (\Throwable $e) {
            Log::error('Leave change approval notification failed.', [
                'leave_change_request_id' => $leaveChangeRequest->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('leave-change-requests.show', $leaveChangeRequest)
            ->with('success', 'Leave change request approved successfully.');
    }

    /**
     * Reject a pending leave change request.
     */
    public function reject(
        Request $request,
        LeaveChangeRequest $leaveChangeRequest
    ): RedirectResponse {
        $this->authorizeReject($leaveChangeRequest);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use (
                $leaveChangeRequest,
                $validated
            ) {
                $change = LeaveChangeRequest::query()
                    ->whereKey($leaveChangeRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($change->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' =>
                            'Only pending leave change requests can be rejected.',
                    ]);
                }

                $leave = LeaveRequest::query()
                    ->whereKey($change->leave_request_id)
                    ->lockForUpdate()
                    ->first();

                if (!$leave) {
                    throw ValidationException::withMessages([
                        'leave_request' =>
                            'The original leave request could not be found.',
                    ]);
                }

                $change->status = 'rejected';
                $change->rejected_by = auth()->id();
                $change->rejected_at = now();
                $change->rejection_reason = $validated['rejection_reason'];
                $change->save();

                $this->addHistory(
                    $leave,
                    'change_rejected',
                    $leave->status,
                    $leave->status,
                    $change->old_from_date,
                    $change->old_to_date,
                    $change->new_from_date,
                    $change->new_to_date,
                    $validated['rejection_reason'],
                    ['leave_change_request_id' => $change->id]
                );
            });
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Leave change request rejection failed.', [
                'leave_change_request_id' => $leaveChangeRequest->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Unable to reject the leave change request.'
            );
        }

        try {
            $leaveChangeRequest->refresh();
            $this->sendRejectedNotification($leaveChangeRequest);
        } catch (\Throwable $e) {
            Log::error('Leave change rejection notification failed.', [
                'leave_change_request_id' => $leaveChangeRequest->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('leave-change-requests.show', $leaveChangeRequest)
            ->with('success', 'Leave change request rejected.');
    }

    /**
     * Cancel a pending change request.
     */
    public function cancel(
        LeaveChangeRequest $leaveChangeRequest
    ): RedirectResponse {
        $user = auth()->user();

        $isOwner = (int) $leaveChangeRequest->requested_by === (int) $user->id;

        if (
            !$user->is_super_admin
            && !$isOwner
        ) {
            abort(403);
        }

        if (
            !$user->is_super_admin
            && !$user->can('leave_change_request.cancel')
        ) {
            abort(403);
        }

        if ($leaveChangeRequest->status !== 'pending') {
            return back()->with(
                'error',
                'Only pending leave change requests can be cancelled.'
            );
        }

        try {
            DB::transaction(function () use ($leaveChangeRequest) {
                $change = LeaveChangeRequest::query()
                    ->whereKey($leaveChangeRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($change->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' =>
                            'Only pending leave change requests can be cancelled.',
                    ]);
                }

                $change->status = 'cancelled';
                $change->save();

                $leave = LeaveRequest::find($change->leave_request_id);

                if ($leave) {
                    $this->addHistory(
                        $leave,
                        'change_cancelled',
                        $leave->status,
                        $leave->status,
                        $change->old_from_date,
                        $change->old_to_date,
                        $change->new_from_date,
                        $change->new_to_date,
                        'Leave change request cancelled.',
                        ['leave_change_request_id' => $change->id]
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('Leave change request cancellation failed.', [
                'leave_change_request_id' => $leaveChangeRequest->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Unable to cancel the leave change request.'
            );
        }

        return redirect()
            ->route('leave-change-requests.show', $leaveChangeRequest)
            ->with('success', 'Leave change request cancelled.');
    }

    /**
     * Restore balance used by the original approved leave.
     */
    protected function restoreOldBalance(
        LeaveRequest $leave,
        $fromDate,
        $toDate,
        float $duration,
        float $paidDays,
        float $unpaidDays
    ): void {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        $balance = UserLeaveBalance::query()
            ->where('user_id', $leave->user_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('status', true)
            ->whereDate('valid_from', '<=', $from->toDateString())
            ->whereDate('valid_to', '>=', $to->toDateString())
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            throw new \RuntimeException(
                'Could not find the original leave balance to restore.'
            );
        }

        $balance->used_balance = round(
            max(0, (float) $balance->used_balance - $duration),
            2
        );

        $balance->paid_days_used = round(
            max(0, (float) $balance->paid_days_used - $paidDays),
            2
        );

        $balance->unpaid_days_used = round(
            max(0, (float) $balance->unpaid_days_used - $unpaidDays),
            2
        );

        $balance->current_balance = round(
            (float) $balance->current_balance + $paidDays,
            2
        );

        $balance->updated_by = auth()->id();
        $balance->save();
    }

    /**
     * Apply the new leave to the new leave type's balance.
     *
     * Follows the monthly entitlement and paid/unpaid allocation
     * pattern used by LeaveRequestController.
     */
    protected function applyNewBalance(
        LeaveRequest $leave,
        int $newLeaveTypeId,
        string $dayType,
        Carbon $from,
        Carbon $to,
        float $duration
    ): array {
        $balance = UserLeaveBalance::query()
            ->where('user_id', $leave->user_id)
            ->where('leave_type_id', $newLeaveTypeId)
            ->where('status', true)
            ->whereDate('valid_from', '<=', $from->toDateString())
            ->whereDate('valid_to', '>=', $to->toDateString())
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            throw new \RuntimeException(
                'No active balance exists for the new leave type and selected dates.'
            );
        }

        $monthlyEntitlement = (float) $balance->monthly_entitlement;
        $currentBalance = (float) $balance->current_balance;
        $paidDays = 0.0;
        $unpaidDays = 0.0;
        $monthlyAllocated = [];

        $date = $from->copy();

        while ($date->lte($to)) {
            $dayDuration = $dayType === 'half_day' ? 0.5 : 1.0;
            $monthKey = $date->format('Y-m');

            $alreadyPaid = $this->getApprovedMonthlyPaidUsage(
                $leave,
                $newLeaveTypeId,
                $date
            );

            $monthlyRemaining = max(
                0,
                $monthlyEntitlement
                    - $alreadyPaid
                    - ($monthlyAllocated[$monthKey] ?? 0)
            );

            $dayPaid = round(
                min($dayDuration, $monthlyRemaining, $currentBalance),
                2
            );

            $dayUnpaid = round($dayDuration - $dayPaid, 2);

            $paidDays += $dayPaid;
            $unpaidDays += $dayUnpaid;

            $monthlyAllocated[$monthKey] =
                ($monthlyAllocated[$monthKey] ?? 0) + $dayPaid;

            $currentBalance = max(0, $currentBalance - $dayPaid);

            $date->addDay();
        }

        $balance->used_balance = round(
            (float) $balance->used_balance + $duration,
            2
        );

        $balance->paid_days_used = round(
            (float) $balance->paid_days_used + $paidDays,
            2
        );

        $balance->unpaid_days_used = round(
            (float) $balance->unpaid_days_used + $unpaidDays,
            2
        );

        $balance->current_balance = round($currentBalance, 2);
        $balance->updated_by = auth()->id();
        $balance->save();

        return [
            'paid_days' => round($paidDays, 2),
            'unpaid_days' => round($unpaidDays, 2),
            'remaining_balance' => round($currentBalance, 2),
        ];
    }

    /**
     * Calculate paid leave already used in the given month.
     */
    protected function getApprovedMonthlyPaidUsage(
        LeaveRequest $currentLeave,
        int $leaveTypeId,
        Carbon $date
    ): float {
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $leaves = LeaveRequest::query()
            ->where('user_id', $currentLeave->user_id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->where('id', '!=', $currentLeave->id)
            ->whereNotNull('approved_from_date')
            ->whereNotNull('approved_to_date')
            ->whereDate('approved_from_date', '<=', $monthEnd->toDateString())
            ->whereDate('approved_to_date', '>=', $monthStart->toDateString())
            ->get();

        $used = 0.0;

        foreach ($leaves as $approvedLeave) {
            $leaveFrom = Carbon::parse($approvedLeave->approved_from_date)
                ->startOfDay();
            $leaveTo = Carbon::parse($approvedLeave->approved_to_date)
                ->startOfDay();

            $overlapFrom = $leaveFrom->greaterThan($monthStart)
                ? $leaveFrom
                : $monthStart->copy();

            $overlapTo = $leaveTo->lessThan($monthEnd)
                ? $leaveTo
                : $monthEnd->copy();

            if ($overlapTo->lt($overlapFrom)) {
                continue;
            }

            $totalDays = $leaveFrom->diffInDays($leaveTo) + 1;
            $overlapDays = $overlapFrom->diffInDays($overlapTo) + 1;

            $totalDuration = $approvedLeave->type === 'half_day'
                ? $totalDays * 0.5
                : $totalDays;

            $overlapDuration = $approvedLeave->type === 'half_day'
                ? $overlapDays * 0.5
                : $overlapDays;

            if ($totalDuration <= 0) {
                continue;
            }

            $paidRatio = (float) $approvedLeave->paid_days / $totalDuration;
            $used += $overlapDuration * $paidRatio;
        }

        return round($used, 2);
    }

    /**
     * Find a pending or approved leave overlapping the new dates.
     */
    protected function findOverlappingLeave(
        int $userId,
        Carbon $from,
        Carbon $to,
        ?int $ignoreLeaveRequestId = null
    ): ?LeaveRequest {
        $query = LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($pending) use ($from, $to) {
                    $pending->where('status', 'pending')
                        ->whereDate('requested_from_date', '<=', $to->toDateString())
                        ->whereDate('requested_to_date', '>=', $from->toDateString());
                })->orWhere(function ($approved) use ($from, $to) {
                    $approved->where('status', 'approved')
                        ->whereNotNull('approved_from_date')
                        ->whereNotNull('approved_to_date')
                        ->whereDate('approved_from_date', '<=', $to->toDateString())
                        ->whereDate('approved_to_date', '>=', $from->toDateString());
                });
            });

        if ($ignoreLeaveRequestId !== null) {
            $query->where('id', '!=', $ignoreLeaveRequestId);
        }

        return $query->first();
    }

    /**
     * Store history in the existing leave_request_history table.
     */
    protected function addHistory(
        LeaveRequest $leave,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        $oldFrom = null,
        $oldTo = null,
        $newFrom = null,
        $newTo = null,
        ?string $reason = null,
        array $metadata = []
    ): void {
        LeaveRequestHistory::create([
            'leave_request_id' => $leave->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_from_date' => $oldFrom
                ? Carbon::parse($oldFrom)->toDateString()
                : null,
            'old_to_date' => $oldTo
                ? Carbon::parse($oldTo)->toDateString()
                : null,
            'new_from_date' => $newFrom
                ? Carbon::parse($newFrom)->toDateString()
                : null,
            'new_to_date' => $newTo
                ? Carbon::parse($newTo)->toDateString()
                : null,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    protected function ensureCanRequestChange(
        LeaveRequest $leaveRequest
    ): void {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if (!$user->can('leave_change_request.create')) {
            abort(403);
        }

        if ($leaveRequest->status !== 'approved') {
            abort(403, 'Only approved leave requests can be changed.');
        }

        if (
            !$user->is_super_admin
            && (int) $leaveRequest->user_id !== (int) $user->id
        ) {
            abort(403, 'You can only request a change to your own leave.');
        }
    }

    protected function authorizeView(
        LeaveChangeRequest $change
    ): void {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if (
            $user->is_super_admin
            || (int) $change->requested_by === (int) $user->id
            || $user->can('leave_change_request.approve')
            || $this->isAssignedToUser($change, (int) $user->id)
        ) {
            return;
        }

        abort(403);
    }

    protected function authorizeApprove(
        LeaveChangeRequest $change
    ): void {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if ($user->is_super_admin) {
            return;
        }

        if (!$user->can('leave_change_request.approve')) {
            abort(403, 'You do not have permission to approve this request.');
        }

        if (!$this->isAssignedToUser($change, (int) $user->id)) {
            abort(403, 'You are not assigned to approve this request.');
        }

        if ((int) $change->requested_by === (int) $user->id) {
            abort(403, 'You cannot approve your own request.');
        }
    }

    protected function authorizeReject(
        LeaveChangeRequest $change
    ): void {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if ($user->is_super_admin) {
            return;
        }

        if (!$user->can('leave_change_request.reject')) {
            abort(403, 'You do not have permission to reject this request.');
        }

        if (!$this->isAssignedToUser($change, (int) $user->id)) {
            abort(403, 'You are not assigned to reject this request.');
        }

        if ((int) $change->requested_by === (int) $user->id) {
            abort(403, 'You cannot reject your own request.');
        }
    }

    protected function isAssignedToUser(
        LeaveChangeRequest $change,
        int $userId
    ): bool {
        $assignedTo = $change->assigned_to;

        if (is_string($assignedTo)) {
            $assignedTo = json_decode($assignedTo, true) ?? [];
        }

        if (!is_array($assignedTo)) {
            return false;
        }

        $ids = collect($assignedTo)
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item['id'] ?? $item['user_id'] ?? null;
                }

                return $item;
            })
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array($userId, $ids, true);
    }

    protected function sendSubmittedNotification(
        LeaveChangeRequest $change
    ): void {
        if (method_exists(
            $this->notificationService,
            'notifyLeaveChangeRequestSubmitted'
        )) {
            $this->notificationService
                ->notifyLeaveChangeRequestSubmitted($change);
        }
    }

    protected function sendApprovedNotification(
        LeaveChangeRequest $change
    ): void {
        if (method_exists(
            $this->notificationService,
            'notifyLeaveChangeRequestApproved'
        )) {
            $this->notificationService
                ->notifyLeaveChangeRequestApproved($change);
        }
    }

    protected function sendRejectedNotification(
        LeaveChangeRequest $change
    ): void {
        if (method_exists(
            $this->notificationService,
            'notifyLeaveChangeRequestRejected'
        )) {
            $this->notificationService
                ->notifyLeaveChangeRequestRejected($change);
        }
    }
}
