<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequestStoreRequest;
use App\Http\Requests\LeaveRequestUpdateRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestHistory;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserLeaveBalance;
use App\Services\AttachmentService;
use App\Services\LeaveBalanceService;
use App\Services\NotificationService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    protected string $pageTitle;

    protected LeaveBalanceService $leaveBalanceService;

    protected NotificationService $notificationService;

    protected UserService $userService;

    public function __construct(
        LeaveBalanceService $leaveBalanceService,
        NotificationService $notificationService,
        UserService $userService
    ) {
        $this->pageTitle = 'Leave Management';

        $this->leaveBalanceService = $leaveBalanceService;
        $this->notificationService = $notificationService;
        $this->userService = $userService;

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);
    }

    /**
     * Display leave requests.
     */
    public function index(Request $request): View
    {
        $perPage = $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        $leaveRequests = LeaveRequest::with([
            'user',
            'leaveType',
            'approvedBy',
            'rejectedBy',
            'cancelledBy',
            'addedBy',
        ])
            ->filter($request->all())
            ->sort($request->all())
            ->orderBy('leave_requests.id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        /*
         * Calendar events use the same filters as the list.
         */
        $calendarLeaveRequests = LeaveRequest::with([
            'user',
            'leaveType',
        ])
            ->filter($request->all())
            ->sort($request->all())
            ->orderBy('leave_requests.id', 'desc')
            ->get();

        $calendarEvents = $calendarLeaveRequests
            ->map(function (LeaveRequest $leaveRequest) {
                $calendarFromDate =
                    $leaveRequest->status === 'approved'
                    && $leaveRequest->approved_from_date
                    ? $leaveRequest->approved_from_date
                    : $leaveRequest->requested_from_date;

                $calendarToDate =
                    $leaveRequest->status === 'approved'
                    && $leaveRequest->approved_to_date
                    ? $leaveRequest->approved_to_date
                    : $leaveRequest->requested_to_date;

                return [
                    'id' => $leaveRequest->id,

                    'title' => ($leaveRequest->user?->name ?? 'Unknown')
                        . ' - '
                        . ($leaveRequest->leaveType?->name ?? 'Leave'),

                    'start' => $calendarFromDate,

                    'end' => $calendarToDate
                        ? Carbon::parse($calendarToDate)
                        ->addDay()
                        ->toDateString()
                        : null,

                    'url' => route(
                        'leave-requests.show',
                        $leaveRequest->id
                    ),

                    'extendedProps' => [
                        'status' => $leaveRequest->status,

                        'employee' =>
                        $leaveRequest->user?->name ?? '-',

                        'leaveType' =>
                        $leaveRequest->leaveType?->name ?? '-',

                        'duration' =>
                        $leaveRequest->status === 'approved'
                            && $leaveRequest->approved_duration !== null
                            ? $leaveRequest->approved_duration
                            : $leaveRequest->duration,

                        'type' =>
                        $leaveRequest->type,

                        'half_day_type' =>
                        $leaveRequest->half_day_type,
                    ],
                ];
            })
            ->values();

        $leaveTypes = LeaveType::query()
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $employees = User::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('leave_requests.index', compact(
            'leaveRequests',
            'calendarEvents',
            'leaveTypes',
            'employees',
            'users',
            'perPage'
        ) + [
            'pageTitle' => $this->pageTitle,
            'isPendingPage' => false,
        ]);
    }

    /**
     * Show leave application form.
     */
    public function create(): View
    {
        $leaveTypes = LeaveType::query()
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return view('leave_requests.create', [
            'pageTitle' => 'Apply Leave',
            'subTitle' => 'Submit a new leave request.',
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Store a new leave request.
     *
     * Normal Leave:
     * - user_id = logged-in user
     * - added_by = logged-in user
     * - status = pending
     *
     * Mark Attendance:
     * - user_id = selected employee
     * - added_by = logged-in user
     * - status = approved
     * - approved_by = logged-in user
     */
    public function store(
        LeaveRequestStoreRequest $request
    ): RedirectResponse {
        $loggedInUser = Auth::user();

        $createdFromAttendance =
            $request->boolean('created_from_attendance');

        /*
         * Determine employee.
         */
        if ($createdFromAttendance) {
            if (!$request->filled('user_id')) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Please select an employee.'
                    );
            }

            $selectedUserId = (int) $request->user_id;

            if (!$loggedInUser->is_super_admin) {
                $accessibleUserIds = $this->userService
                    ->getAccessibleUsers($loggedInUser)
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->all();

                if (!in_array(
                    $selectedUserId,
                    $accessibleUserIds,
                    true
                )) {
                    abort(
                        403,
                        'You are not authorized to mark attendance for this user.'
                    );
                }
            }

            $leaveUser = User::query()
                ->where('id', $selectedUserId)
                ->where('is_active', true)
                ->where('delete_status', false)
                ->whereNull('deleted_at')
                ->first();

            if (!$leaveUser) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'The selected user is not an active employee.'
                    );
            }
        } else {
            $leaveUser = $loggedInUser;
        }

        /*
         * Parse dates.
         */
        $fromDate = Carbon::parse(
            $request->requested_from_date
        )->startOfDay();

        $toDate = Carbon::parse(
            $request->requested_to_date
        )->startOfDay();

        if ($toDate->lt($fromDate)) {
            return back()
                ->withInput()
                ->withErrors([
                    'requested_to_date' =>
                    'The To Date must be after or equal to the From Date.',
                ]);
        }

        $type = $request->type;

        /*
         * Prevent overlapping pending/approved leave.
         */
        $existingLeave = $this->findOverlappingLeave(
            $leaveUser->id,
            $fromDate,
            $toDate
        );

        if ($existingLeave) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Leave already applied for this duration.'
                );
        }

        /*
         * Always calculate duration on the server.
         */
        $duration =
            $this->leaveBalanceService
            ->calculateDuration(
                $type,
                $fromDate,
                $toDate
            );

        /*
         * Eligibility is informational only.
         */
        $eligibility =
            $this->leaveBalanceService
            ->checkEligibility(
                $leaveUser,
                (int) $request->leave_type_id,
                $type,
                $fromDate,
                $toDate
            );

        $leaveType = LeaveType::findOrFail(
            $request->leave_type_id
        );

        /*
         * Assign reporter + manager.
         */
        $assignedTo = array_values(
            array_unique(
                array_filter([
                    $leaveUser->reporter->id ?? null,
                    $leaveUser->manager->id ?? null,
                ])
            )
        );

        /*
         * Super Admin fallback.
         */
        if (empty($assignedTo)) {
            $assignedTo = User::query()
                ->where('is_super_admin', true)
                ->where('is_active', true)
                ->where('delete_status', false)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();
        }

        $status =
            $createdFromAttendance
            ? 'approved'
            : 'pending';

        $leaveRequest = LeaveRequest::create([
            'user_id' =>
            $leaveUser->id,

            'added_by' =>
            $loggedInUser->id,

            'leave_type_id' =>
            $leaveType->id,

            'type' =>
            $type,

            'half_day_type' =>
            $type === 'half_day'
                ? $request->half_day_type
                : null,

            'requested_from_date' =>
            $fromDate->toDateString(),

            'requested_to_date' =>
            $toDate->toDateString(),

            'duration' =>
            $duration,

            'reason' =>
            $request->reason,

            'status' =>
            $status,

            'assigned_to' =>
            $assignedTo,

            'submitted_at' =>
            now(),
        ]);

        /*
         * Save attachment.
         */
        $this->saveAttachment(
            $request,
            $leaveRequest
        );

        /*
         * History: submitted.
         */
        $this->logLeaveHistory(
            $leaveRequest,
            'submitted',
            null,
            $status,
            null,
            null,
            $fromDate,
            $toDate,
            $request->reason,
            [
                'created_from_attendance' =>
                $createdFromAttendance,

                'duration' =>
                $duration,

                'added_by' =>
                $loggedInUser->id,

                'assigned_to' =>
                $assignedTo,
            ]
        );

        /*
         * Mark Attendance:
         * immediately approve and deduct balance.
         */
        if ($createdFromAttendance) {
            try {
                DB::transaction(function () use (
                    $leaveRequest,
                    $loggedInUser,
                    $fromDate,
                    $toDate,
                    $duration
                ) {
                    $balanceResult =
                        $this->updateLeaveBalanceAfterApproval(
                            $leaveRequest,
                            $duration,
                            $fromDate,
                            $toDate,
                            $loggedInUser->id
                        );

                    $leaveRequest->status =
                        'approved';

                    $leaveRequest->approved_by =
                        $loggedInUser->id;

                    $leaveRequest->approved_at =
                        now();

                    $leaveRequest->approved_from_date =
                        $fromDate->toDateString();

                    $leaveRequest->approved_to_date =
                        $toDate->toDateString();

                    $leaveRequest->approved_duration =
                        $duration;

                    $leaveRequest->paid_days =
                        $balanceResult['paid_days'];

                    $leaveRequest->unpaid_days =
                        $balanceResult['unpaid_days'];

                    $leaveRequest->save();

                    $this->logLeaveHistory(
                        $leaveRequest,
                        'approved',
                        'pending',
                        'approved',
                        $fromDate,
                        $toDate,
                        $fromDate,
                        $toDate,
                        'Leave approved through Mark Attendance.',
                        [
                            'approved_by' =>
                            $loggedInUser->id,

                            'approved_duration' =>
                            $duration,

                            'paid_days' =>
                            $balanceResult['paid_days'],

                            'unpaid_days' =>
                            $balanceResult['unpaid_days'],
                        ]
                    );

                    $this->logLeaveHistory(
                        $leaveRequest,
                        'balance_deducted',
                        'approved',
                        'approved',
                        $fromDate,
                        $toDate,
                        $fromDate,
                        $toDate,
                        'Leave balance deducted after approval.',
                        [
                            'approved_duration' =>
                            $duration,

                            'paid_days' =>
                            $balanceResult['paid_days'],

                            'unpaid_days' =>
                            $balanceResult['unpaid_days'],

                            'remaining_balance' =>
                            $balanceResult['remaining_balance'],
                        ]
                    );
                });
            } catch (\RuntimeException $e) {
                $leaveRequest->delete();

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        $e->getMessage()
                    );
            }

            return redirect()
                ->route(
                    'attendance.index',
                    [
                        'date' =>
                        $fromDate->toDateString(),
                    ]
                )
                ->with(
                    'success',
                    'Leave added and approved successfully.'
                );
        }

        /*
         * Normal leave notification.
         */
        $this->notificationService
            ->notifyLeaveRequestCreated(
                $leaveRequest
            );

        if (!$eligibility['eligible']) {
            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'warning',
                    $eligibility['message']
                );
        }

        return redirect()
            ->route(
                'leave-requests.index'
            )
            ->with(
                'success',
                'Leave request submitted successfully.'
            );
    }

    /**
     * Show leave request details.
     */
    public function show(
        LeaveRequest $leaveRequest
    ): View {
        $leaveRequest->load([
            'user',
            'leaveType',
            'approvedBy',
            'rejectedBy',
            'cancelledBy',
            'addedBy',
            'histories.user',
        ]);

        return view('leave_requests.show', [
            'pageTitle' =>
            'Leave Request Details',

            'subTitle' =>
            'View leave request details and status.',

            'leaveRequest' =>
            $leaveRequest,
        ]);
    }

    /**
     * Edit/review leave request.
     */
    public function edit(
        Request $request,
        string $id
    ): View|RedirectResponse {
        $leaveRequest = LeaveRequest::findOrFail($id);

        $authUser = auth()->user();

        /*
         * Approval mode.
         */
        $approvalMode =
            $request->boolean('approval_mode')
            || in_array(
                $request->action,
                [
                    'approve',
                    'reject',
                    'update_and_approve',
                ],
                true
            );

        $action = $request->input('action');

        if (!in_array(
            $action,
            [
                'approve',
                'reject',
                'update',
                'update_and_approve',
            ],
            true
        )) {
            $action = null;
        }

        $isSuperAdmin =
            (bool) ($authUser?->is_super_admin);

        /*
         * Permission allowing editing of any leave request.
         *
         * This is intentionally separate from approval permission.
         */
        $canEditAll =
            $isSuperAdmin
            || $authUser->can('leave_requests.edit_all');

        /*
         * Approval permission.
         */
        $isApprover =
            $leaveRequest->status === 'pending'
            && (
                $isSuperAdmin
                || (
                    $authUser->can('leave_request.approve')
                    && in_array(
                        (int) $authUser->id,
                        array_map(
                            'intval',
                            $leaveRequest->assigned_to ?? []
                        ),
                        true
                    )
                )
            );

        /*
         * Employee ownership.
         */
        $isOwner =
            (int) $leaveRequest->user_id
            === (int) $authUser->id;

        $canEditOwnRequest =
            $isOwner
            && $authUser->can('leave_request.edit');

        /*
         * Full edit:
         *
         * - employee can edit own employee-created pending request
         * - user with edit_all can edit pending request
         */
        $fullEdit =
            !$approvalMode
            && $leaveRequest->status === 'pending'
            && (
                $canEditAll
                || (
                    $canEditOwnRequest
                    && (
                        $leaveRequest->added_by === null
                        || (int) $leaveRequest->added_by
                        === (int) $authUser->id
                    )
                )
            );

        /*
         * Restricted edit:
         *
         * - owner can update reason/attachment for manager-created
         *   pending requests
         * - owner can update reason/attachment for approved requests
         *
         * edit_all users receive full edit instead.
         */
        $restrictedEdit =
            !$approvalMode
            && !$canEditAll
            && $canEditOwnRequest
            && !$fullEdit
            && in_array(
                $leaveRequest->status,
                [
                    'pending',
                    'approved',
                ],
                true
            );

        /*
         * Authorization.
         */
        if ($approvalMode) {
            if (!$isApprover) {
                abort(403);
            }
        } else {
            if (
                !$canEditAll
                && !$canEditOwnRequest
                && !$isApprover
            ) {
                abort(403);
            }

            if (
                !in_array(
                    $leaveRequest->status,
                    [
                        'pending',
                        'approved',
                    ],
                    true
                )
                && !$isApprover
            ) {
                return redirect()
                    ->route(
                        'leave-requests.show',
                        $leaveRequest->id
                    )
                    ->with(
                        'error',
                        'This leave request can no longer be edited.'
                    );
            }
        }

        $leaveTypes = LeaveType::query()
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $approvalFromDate =
            $leaveRequest->approved_from_date
            ? Carbon::parse(
                $leaveRequest->approved_from_date
            )->format('Y-m-d')
            : Carbon::parse(
                $leaveRequest->requested_from_date
            )->format('Y-m-d');

        $approvalToDate =
            $leaveRequest->approved_to_date
            ? Carbon::parse(
                $leaveRequest->approved_to_date
            )->format('Y-m-d')
            : Carbon::parse(
                $leaveRequest->requested_to_date
            )->format('Y-m-d');

        $approvalDuration =
            $leaveRequest->approved_duration !== null
            ? number_format(
                (float) $leaveRequest->approved_duration,
                2,
                '.',
                ''
            )
            : number_format(
                (float) $leaveRequest->duration,
                2,
                '.',
                ''
            );

        return view('leave_requests.edit', [
            'pageTitle' =>
            $approvalMode
                ? 'Review Leave Request'
                : 'Edit Leave Request',

            'subTitle' =>
            $approvalMode
                ? 'Review and process this leave request.'
                : 'Update your leave request details.',

            'leaveRequest' =>
            $leaveRequest,

            'leaveTypes' =>
            $leaveTypes,

            'approvalMode' =>
            $approvalMode,

            'action' =>
            $action,

            'isSuperAdmin' =>
            $isSuperAdmin,

            'isApprover' =>
            $isApprover,

            'canEditAll' =>
            $canEditAll,

            'fullEdit' =>
            $fullEdit,

            'restrictedEdit' =>
            $restrictedEdit,

            'approvalFromDate' =>
            $approvalFromDate,

            'approvalToDate' =>
            $approvalToDate,

            'approvalDuration' =>
            $approvalDuration,
        ]);
    }

    /**
     * Update / approve / reject a leave request.
     */
    public function update(
        LeaveRequestUpdateRequest $request,
        LeaveRequest $leaveRequest
    ): RedirectResponse {
        $authUser = auth()->user();

        /*
         * Determine approval mode.
         */
        $approvalMode =
            $request->boolean('approval_mode')
            || in_array(
                $request->action,
                [
                    'approve',
                    'reject',
                    'update_and_approve',
                ],
                true
            );

        $isSuperAdmin =
            (bool) ($authUser?->is_super_admin);

        /*
         * edit_all is separate from approval.
         */
        $canEditAll =
            $isSuperAdmin
            || $authUser->can('leave_requests.edit_all');

        /*
         * Determine approver.
         */
        $isApprover =
            $leaveRequest->status === 'pending'
            && (
                $isSuperAdmin
                || (
                    $authUser->can('leave_request.approve')
                    && in_array(
                        (int) $authUser->id,
                        array_map(
                            'intval',
                            $leaveRequest->assigned_to ?? []
                        ),
                        true
                    )
                )
            );

        /*
         * Ownership.
         */
        $isOwner =
            (int) $leaveRequest->user_id
            === (int) $authUser->id;

        $canEditOwnRequest =
            $isOwner
            && $authUser->can('leave_request.edit');

        /*
         * Full edit.
         */
        $fullEdit =
            !$approvalMode
            && $leaveRequest->status === 'pending'
            && (
                $canEditAll
                || (
                    $canEditOwnRequest
                    && (
                        $leaveRequest->added_by === null
                        || (int) $leaveRequest->added_by
                        === (int) $authUser->id
                    )
                )
            );

        /*
         * Restricted edit.
         */
        $restrictedEdit =
            !$approvalMode
            && !$canEditAll
            && $canEditOwnRequest
            && !$fullEdit
            && in_array(
                $leaveRequest->status,
                [
                    'pending',
                    'approved',
                ],
                true
            );

        /*
         * =========================================================
         * APPROVAL MODE
         * =========================================================
         */
        if ($approvalMode) {
            if (!$isApprover) {
                abort(403);
            }

            /*
             * -----------------------------------------------------
             * UPDATE ONLY
             * -----------------------------------------------------
             */
            if ($request->action === 'update') {
                $oldStatus =
                    $leaveRequest->status;

                $oldFromDate =
                    $leaveRequest->requested_from_date;

                $oldToDate =
                    $leaveRequest->requested_to_date;

                $oldLeaveTypeId =
                    $leaveRequest->leave_type_id;

                $oldType =
                    $leaveRequest->type;

                $oldDuration =
                    $leaveRequest->duration;

                $type =
                    $request->type
                    ?? $leaveRequest->type;

                $requestedFromDate = Carbon::parse(
                    $leaveRequest->requested_from_date
                )->startOfDay();

                $requestedToDate = Carbon::parse(
                    $leaveRequest->requested_to_date
                )->startOfDay();

                $duration =
                    $this->leaveBalanceService
                    ->calculateDuration(
                        $type,
                        $requestedFromDate,
                        $requestedToDate
                    );

                $leaveRequest->update([
                    'leave_type_id' =>
                    $request->leave_type_id,

                    'type' =>
                    $type,

                    'half_day_type' =>
                    $type === 'half_day'
                        ? $request->half_day_type
                        : null,

                    'duration' =>
                    $duration,

                    'reason' =>
                    $request->reason,

                    'approver_comment' =>
                    $request->approver_comment,
                ]);

                $this->saveAttachment(
                    $request,
                    $leaveRequest
                );

                $this->logLeaveHistory(
                    $leaveRequest,
                    'updated',
                    $oldStatus,
                    $leaveRequest->status,
                    $oldFromDate,
                    $oldToDate,
                    $leaveRequest->requested_from_date,
                    $leaveRequest->requested_to_date,
                    $request->reason,
                    [
                        'updated_by' =>
                        $authUser->id,

                        'updated_by_type' =>
                        'approver',

                        'old_leave_type_id' =>
                        $oldLeaveTypeId,

                        'new_leave_type_id' =>
                        $leaveRequest->leave_type_id,

                        'old_type' =>
                        $oldType,

                        'new_type' =>
                        $leaveRequest->type,

                        'old_duration' =>
                        $oldDuration,

                        'new_duration' =>
                        $duration,
                    ]
                );

                $this->notificationService
                    ->notifyLeaveRequestReviewed(
                        $leaveRequest,
                        $authUser,
                        'update'
                    );

                return redirect()
                    ->route(
                        'leave-requests.show',
                        $leaveRequest
                    )
                    ->with(
                        'success',
                        'Leave request updated successfully. The request is still pending approval.'
                    );
            }

            /*
             * -----------------------------------------------------
             * UPDATE & APPROVE
             * -----------------------------------------------------
             */
            if ($request->action === 'update_and_approve') {
                if (
                    !$request->filled('approved_from_date')
                    || !$request->filled('approved_to_date')
                ) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'approved_from_date' =>
                            'Approval From Date is required.',

                            'approved_to_date' =>
                            'Approval To Date is required.',
                        ]);
                }

                $approvedFromDate = Carbon::parse(
                    $request->approved_from_date
                )->startOfDay();

                $approvedToDate = Carbon::parse(
                    $request->approved_to_date
                )->startOfDay();

                if ($approvedToDate->lt($approvedFromDate)) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'approved_to_date' =>
                            'The Approval To Date must be after or equal to the Approval From Date.',
                        ]);
                }

                $existingLeave = $this->findOverlappingLeave(
                    $leaveRequest->user_id,
                    $approvedFromDate,
                    $approvedToDate,
                    $leaveRequest->id
                );

                if ($existingLeave) {
                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            'Leave already applied for this duration. The approved dates overlap with an existing leave request.'
                        );
                }

                $oldStatus =
                    $leaveRequest->status;

                $oldFromDate =
                    $leaveRequest->requested_from_date;

                $oldToDate =
                    $leaveRequest->requested_to_date;

                $oldLeaveTypeId =
                    $leaveRequest->leave_type_id;

                $oldType =
                    $leaveRequest->type;

                $type =
                    $request->type
                    ?? $leaveRequest->type;

                $approvedDuration =
                    $this->leaveBalanceService
                    ->calculateDuration(
                        $type,
                        $approvedFromDate,
                        $approvedToDate
                    );

                try {
                    DB::transaction(function () use (
                        $request,
                        $leaveRequest,
                        $authUser,
                        $approvedFromDate,
                        $approvedToDate,
                        $approvedDuration,
                        $type,
                        $oldStatus,
                        $oldFromDate,
                        $oldToDate,
                        $oldLeaveTypeId,
                        $oldType
                    ) {
                        $leaveRequest->leave_type_id =
                            $request->leave_type_id;

                        $leaveRequest->type =
                            $type;

                        $leaveRequest->half_day_type =
                            $type === 'half_day'
                            ? $request->half_day_type
                            : null;

                        $leaveRequest->reason =
                            $request->reason;

                        $leaveRequest->approver_comment =
                            $request->approver_comment;

                        $balanceResult =
                            $this->updateLeaveBalanceAfterApproval(
                                $leaveRequest,
                                $approvedDuration,
                                $approvedFromDate,
                                $approvedToDate,
                                $authUser->id
                            );

                        $leaveRequest->status =
                            'approved';

                        $leaveRequest->approved_by =
                            $authUser->id;

                        $leaveRequest->approved_at =
                            now();

                        $leaveRequest->approved_from_date =
                            $approvedFromDate->toDateString();

                        $leaveRequest->approved_to_date =
                            $approvedToDate->toDateString();

                        $leaveRequest->approved_duration =
                            $approvedDuration;

                        $leaveRequest->paid_days =
                            $balanceResult['paid_days'];

                        $leaveRequest->unpaid_days =
                            $balanceResult['unpaid_days'];

                        $leaveRequest->save();

                        $this->saveAttachment(
                            $request,
                            $leaveRequest
                        );

                        if (
                            $oldFromDate !== $approvedFromDate->toDateString()
                            || $oldToDate !== $approvedToDate->toDateString()
                        ) {
                            $this->logLeaveHistory(
                                $leaveRequest,
                                'date_changed',
                                $oldStatus,
                                'approved',
                                $oldFromDate,
                                $oldToDate,
                                $approvedFromDate,
                                $approvedToDate,
                                $request->reason,
                                [
                                    'changed_by' =>
                                    $authUser->id,

                                    'old_leave_type_id' =>
                                    $oldLeaveTypeId,

                                    'new_leave_type_id' =>
                                    $leaveRequest->leave_type_id,

                                    'old_type' =>
                                    $oldType,

                                    'new_type' =>
                                    $leaveRequest->type,

                                    'approved_duration' =>
                                    $approvedDuration,
                                ]
                            );
                        } else {
                            $this->logLeaveHistory(
                                $leaveRequest,
                                'updated',
                                $oldStatus,
                                'approved',
                                $oldFromDate,
                                $oldToDate,
                                $approvedFromDate,
                                $approvedToDate,
                                $request->reason,
                                [
                                    'changed_by' =>
                                    $authUser->id,

                                    'old_leave_type_id' =>
                                    $oldLeaveTypeId,

                                    'new_leave_type_id' =>
                                    $leaveRequest->leave_type_id,

                                    'old_type' =>
                                    $oldType,

                                    'new_type' =>
                                    $leaveRequest->type,

                                    'approved_duration' =>
                                    $approvedDuration,
                                ]
                            );
                        }

                        $this->logLeaveHistory(
                            $leaveRequest,
                            'approved',
                            $oldStatus,
                            'approved',
                            $oldFromDate,
                            $oldToDate,
                            $approvedFromDate,
                            $approvedToDate,
                            $request->approver_comment,
                            [
                                'approved_by' =>
                                $authUser->id,

                                'approved_duration' =>
                                $approvedDuration,

                                'paid_days' =>
                                $balanceResult['paid_days'],

                                'unpaid_days' =>
                                $balanceResult['unpaid_days'],
                            ]
                        );

                        $this->logLeaveHistory(
                            $leaveRequest,
                            'balance_deducted',
                            'approved',
                            'approved',
                            $approvedFromDate,
                            $approvedToDate,
                            $approvedFromDate,
                            $approvedToDate,
                            'Leave balance deducted after approval.',
                            [
                                'approved_duration' =>
                                $approvedDuration,

                                'paid_days' =>
                                $balanceResult['paid_days'],

                                'unpaid_days' =>
                                $balanceResult['unpaid_days'],

                                'remaining_balance' =>
                                $balanceResult['remaining_balance'],
                            ]
                        );
                    });
                } catch (\RuntimeException $e) {
                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            $e->getMessage()
                        );
                }

                $this->notificationService
                    ->notifyLeaveRequestReviewed(
                        $leaveRequest,
                        $authUser,
                        'approve'
                    );

                return redirect()
                    ->route(
                        'leave-requests.show',
                        $leaveRequest
                    )
                    ->with(
                        'success',
                        'Leave request approved successfully.'
                    );
            }

            /*
             * -----------------------------------------------------
             * REJECT
             * -----------------------------------------------------
             */
            if ($request->action === 'reject') {
                $oldStatus =
                    $leaveRequest->status;

                $oldFromDate =
                    $leaveRequest->requested_from_date;

                $oldToDate =
                    $leaveRequest->requested_to_date;

                $leaveRequest->update([
                    'status' =>
                    'rejected',

                    'rejected_by' =>
                    $authUser->id,

                    'rejected_at' =>
                    now(),

                    'approver_comment' =>
                    $request->approver_comment,
                ]);

                $this->saveAttachment(
                    $request,
                    $leaveRequest
                );

                $this->logLeaveHistory(
                    $leaveRequest,
                    'rejected',
                    $oldStatus,
                    'rejected',
                    $oldFromDate,
                    $oldToDate,
                    $oldFromDate,
                    $oldToDate,
                    $request->approver_comment,
                    [
                        'rejected_by' =>
                        $authUser->id,
                    ]
                );

                $this->notificationService
                    ->notifyLeaveRequestReviewed(
                        $leaveRequest,
                        $authUser,
                        'reject'
                    );

                return redirect()
                    ->route(
                        'leave-requests.show',
                        $leaveRequest
                    )
                    ->with(
                        'success',
                        'Leave request rejected successfully.'
                    );
            }

            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'error',
                    'Invalid approval action.'
                );
        }

        /*
         * =========================================================
         * NORMAL EMPLOYEE / EDIT-ALL EDIT
         * =========================================================
         */

        if (!$canEditAll && !$canEditOwnRequest) {
            abort(403);
        }

        /*
         * ---------------------------------------------------------
         * RESTRICTED EMPLOYEE EDIT
         * ---------------------------------------------------------
         */
        if ($restrictedEdit) {
            $oldStatus =
                $leaveRequest->status;

            $oldFromDate =
                $leaveRequest->status === 'approved'
                && $leaveRequest->approved_from_date
                ? $leaveRequest->approved_from_date
                : $leaveRequest->requested_from_date;

            $oldToDate =
                $leaveRequest->status === 'approved'
                && $leaveRequest->approved_to_date
                ? $leaveRequest->approved_to_date
                : $leaveRequest->requested_to_date;

            $oldReason =
                $leaveRequest->reason;

            $leaveRequest->reason =
                $request->reason;

            $leaveRequest->save();

            $attachmentUpdated =
                $this->saveAttachment(
                    $request,
                    $leaveRequest
                );

            $this->logLeaveHistory(
                $leaveRequest,
                'updated',
                $oldStatus,
                $leaveRequest->status,
                $oldFromDate,
                $oldToDate,
                $oldFromDate,
                $oldToDate,
                $request->reason,
                [
                    'changed_by' =>
                    $authUser->id,

                    'changed_by_type' =>
                    'employee_restricted',

                    'reason_changed' =>
                    $oldReason !== $leaveRequest->reason,

                    'attachment_updated' =>
                    $attachmentUpdated,

                    'restricted_fields' => [
                        'reason',
                        'attachment',
                    ],
                ]
            );

            $this->notificationService
                ->notifyLeaveRequestUpdated(
                    $leaveRequest,
                    $authUser->id
                );

            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'success',
                    'Leave request updated successfully.'
                );
        }

        /*
         * ---------------------------------------------------------
         * FULL EDIT
         * ---------------------------------------------------------
         */
        if (!$fullEdit) {
            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'error',
                    'This leave request can no longer be edited.'
                );
        }

        if ($leaveRequest->status !== 'pending') {
            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'error',
                    'This leave request can no longer be edited.'
                );
        }

        if ($request->action === 'update') {
            if (
                !$request->filled('requested_from_date')
                || !$request->filled('requested_to_date')
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'requested_from_date' =>
                        'The requested from date is required.',

                        'requested_to_date' =>
                        'The requested to date is required.',
                    ]);
            }

            $fromDate = Carbon::parse(
                $request->requested_from_date
            )->startOfDay();

            $toDate = Carbon::parse(
                $request->requested_to_date
            )->startOfDay();

            if ($toDate->lt($fromDate)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'requested_to_date' =>
                        'The To Date must be after or equal to the From Date.',
                    ]);
            }

            $existingLeave = $this->findOverlappingLeave(
                $leaveRequest->user_id,
                $fromDate,
                $toDate,
                $leaveRequest->id
            );

            if ($existingLeave) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Leave already applied for this duration. The selected dates overlap with an existing leave request.'
                    );
            }

            $type =
                $request->type
                ?? $leaveRequest->type;

            /*
             * Always calculate duration on server.
             */
            $duration =
                $this->leaveBalanceService
                ->calculateDuration(
                    $type,
                    $fromDate,
                    $toDate
                );

            $oldStatus =
                $leaveRequest->status;

            $oldFromDate =
                $leaveRequest->requested_from_date;

            $oldToDate =
                $leaveRequest->requested_to_date;

            $oldLeaveTypeId =
                $leaveRequest->leave_type_id;

            $oldType =
                $leaveRequest->type;

            $oldDuration =
                $leaveRequest->duration;

            $leaveRequest->update([
                'leave_type_id' =>
                $request->leave_type_id,

                'type' =>
                $type,

                'half_day_type' =>
                $type === 'half_day'
                    ? $request->half_day_type
                    : null,

                'requested_from_date' =>
                $fromDate->toDateString(),

                'requested_to_date' =>
                $toDate->toDateString(),

                'duration' =>
                $duration,

                'reason' =>
                $request->reason,

                'approver_comment' =>
                $request->approver_comment,
            ]);

            $this->saveAttachment(
                $request,
                $leaveRequest
            );

            if (
                $oldFromDate !== $fromDate->toDateString()
                || $oldToDate !== $toDate->toDateString()
            ) {
                $this->logLeaveHistory(
                    $leaveRequest,
                    'date_changed',
                    $oldStatus,
                    $leaveRequest->status,
                    $oldFromDate,
                    $oldToDate,
                    $fromDate,
                    $toDate,
                    $request->reason,
                    [
                        'changed_by' =>
                        $authUser->id,

                        'changed_by_type' =>
                        $canEditAll
                            ? 'edit_all'
                            : 'employee',

                        'old_leave_type_id' =>
                        $oldLeaveTypeId,

                        'new_leave_type_id' =>
                        $leaveRequest->leave_type_id,

                        'old_type' =>
                        $oldType,

                        'new_type' =>
                        $leaveRequest->type,

                        'old_duration' =>
                        $oldDuration,

                        'new_duration' =>
                        $duration,
                    ]
                );
            } else {
                $this->logLeaveHistory(
                    $leaveRequest,
                    'updated',
                    $oldStatus,
                    $leaveRequest->status,
                    $oldFromDate,
                    $oldToDate,
                    $fromDate,
                    $toDate,
                    $request->reason,
                    [
                        'changed_by' =>
                        $authUser->id,

                        'changed_by_type' =>
                        $canEditAll
                            ? 'edit_all'
                            : 'employee',

                        'old_leave_type_id' =>
                        $oldLeaveTypeId,

                        'new_leave_type_id' =>
                        $leaveRequest->leave_type_id,

                        'old_type' =>
                        $oldType,

                        'new_type' =>
                        $leaveRequest->type,

                        'old_duration' =>
                        $oldDuration,

                        'new_duration' =>
                        $duration,
                    ]
                );
            }

            $this->notificationService
                ->notifyLeaveRequestUpdated(
                    $leaveRequest,
                    $authUser->id
                );

            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'success',
                    'Leave request updated successfully.'
                );
        }

        return redirect()
            ->route(
                'leave-requests.show',
                $leaveRequest
            )
            ->with(
                'error',
                'Invalid action.'
            );
    }

    /**
     * Check leave balance through AJAX.
     *
     * This only reports eligibility.
     * It does not block leave application.
     */
    public function checkBalance(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'type' => [
                'required',
                'in:full_day,half_day',
            ],

            'leave_type_id' => [
                'required',
                'exists:leave_types,id',
            ],

            'requested_from_date' => [
                'required',
                'date',
            ],

            'requested_to_date' => [
                'required',
                'date',
                'after_or_equal:requested_from_date',
            ],
        ]);

        $eligibility =
            $this->leaveBalanceService
            ->checkEligibility(
                auth()->user(),
                (int) $validated['leave_type_id'],
                $validated['type'],
                $validated['requested_from_date'],
                $validated['requested_to_date']
            );

        return response()->json(
            $eligibility
        );
    }

    /**
     * Pending leave requests.
     */
    public function pending(Request $request): View
    {
        $user = auth()->user();

        $userId = $user->id;

        $isSuperAdmin = (bool) $user->is_super_admin;

        /*
    |--------------------------------------------------------------------------
    | Per Page
    |--------------------------------------------------------------------------
    */
        $perPage = $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        /*
    |--------------------------------------------------------------------------
    | Pending Leave Requests
    |--------------------------------------------------------------------------
    */
        $query = LeaveRequest::query()
            ->with([
                'user',
                'leaveType',
                'approvedBy',
                'rejectedBy',
                'cancelledBy',
                'addedBy',
            ])
            ->where('status', 'pending');

        /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    |
    | Super Admin:
    |     Can see all pending leave requests.
    |
    | Other users:
    |     Can see their own pending requests OR requests
    |     assigned to them for approval.
    |
    */
        if (!$isSuperAdmin) {
            $query->where(function ($q) use ($userId) {
                $q->whereJsonContains(
                    'assigned_to',
                    $userId
                )
                    ->orWhere(
                        'user_id',
                        $userId
                    );
            });
        }

        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */
        if ($request->filled('search')) {
            $search = trim(
                $request->input('search')
            );

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereHas(
                        'user',
                        function ($userQuery) use ($search) {
                            $userQuery->where(
                                'name',
                                'like',
                                '%' . $search . '%'
                            );
                        }
                    )
                        ->orWhereHas(
                            'leaveType',
                            function ($leaveTypeQuery) use ($search) {
                                $leaveTypeQuery->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                );
                            }
                        );
                });
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */
        $sortColumn = $request->input(
            'sort',
            'created_at'
        );

        $sortDirection = strtolower(
            $request->input(
                'direction',
                'desc'
            )
        );

        if (!in_array(
            $sortDirection,
            ['asc', 'desc'],
            true
        )) {
            $sortDirection = 'desc';
        }

        $allowedSortColumns = [
            'requested_from_date',
            'requested_to_date',
            'duration',
            'status',
            'created_at',
        ];

        if ($sortColumn === 'user.name') {
            $query->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn(
                        'users.id',
                        'leave_requests.user_id'
                    )
                    ->limit(1),
                $sortDirection
            );
        } elseif ($sortColumn === 'leaveType.name') {
            $query->orderBy(
                LeaveType::query()
                    ->select('name')
                    ->whereColumn(
                        'leave_types.id',
                        'leave_requests.leave_type_id'
                    )
                    ->limit(1),
                $sortDirection
            );
        } elseif (in_array(
            $sortColumn,
            $allowedSortColumns,
            true
        )) {
            $query->orderBy(
                'leave_requests.' . $sortColumn,
                $sortDirection
            );
        } else {
            $query->orderBy(
                'leave_requests.created_at',
                'desc'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
        $leaveRequests = $query
            ->paginate($perPage)
            ->withQueryString();

        /*
    |--------------------------------------------------------------------------
    | Leave Types
    |--------------------------------------------------------------------------
    */
        $leaveTypes = LeaveType::query()
            ->where('status', true)
            ->orderBy('name')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    */
        return view('leave_requests.index', [
            'pageTitle' =>
            'Leave Requests Management',

            'subTitle' =>
            'Leave requests awaiting your approval or rejection.',

            'leaveRequests' =>
            $leaveRequests,

            'leaveTypes' =>
            $leaveTypes,

            'perPage' =>
            $perPage,

            'isPendingPage' =>
            true,
        ]);
    }


    /**
     * Direct approve.
     *
     * Uses requested dates as approved dates.
     */
    public function approve(
        Request $request,
        string $id
    ): RedirectResponse {
        $leaveRequest =
            LeaveRequest::findOrFail($id);

        $authUser = auth()->user();

        $isSuperAdmin =
            (bool) $authUser->is_super_admin;

        $isApprover =
            $isSuperAdmin
            || (
                $authUser->can('leave_request.approve')
                && in_array(
                    (int) $authUser->id,
                    array_map(
                        'intval',
                        $leaveRequest->assigned_to ?? []
                    ),
                    true
                )
            );

        if (!$isApprover) {
            abort(403);
        }

        if ($leaveRequest->status !== 'pending') {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Only pending leave requests can be approved.'
                );
        }

        $approvedFromDate = Carbon::parse(
            $leaveRequest->requested_from_date
        )->startOfDay();

        $approvedToDate = Carbon::parse(
            $leaveRequest->requested_to_date
        )->startOfDay();

        $existingLeave = $this->findOverlappingLeave(
            $leaveRequest->user_id,
            $approvedFromDate,
            $approvedToDate,
            $leaveRequest->id
        );

        if ($existingLeave) {
            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'error',
                    'Leave cannot be approved because the selected duration overlaps with an existing leave request.'
                );
        }

        $approvedDuration =
            $this->leaveBalanceService
            ->calculateDuration(
                $leaveRequest->type,
                $approvedFromDate,
                $approvedToDate
            );

        $oldStatus =
            $leaveRequest->status;

        $oldFromDate =
            $leaveRequest->requested_from_date;

        $oldToDate =
            $leaveRequest->requested_to_date;

        try {
            DB::transaction(function () use (
                $leaveRequest,
                $authUser,
                $approvedFromDate,
                $approvedToDate,
                $approvedDuration,
                $oldStatus,
                $oldFromDate,
                $oldToDate
            ) {
                $balanceResult =
                    $this->updateLeaveBalanceAfterApproval(
                        $leaveRequest,
                        $approvedDuration,
                        $approvedFromDate,
                        $approvedToDate,
                        $authUser->id
                    );

                $leaveRequest->status =
                    'approved';

                $leaveRequest->approved_by =
                    $authUser->id;

                $leaveRequest->approved_at =
                    now();

                $leaveRequest->approved_from_date =
                    $approvedFromDate->toDateString();

                $leaveRequest->approved_to_date =
                    $approvedToDate->toDateString();

                $leaveRequest->approved_duration =
                    $approvedDuration;

                $leaveRequest->paid_days =
                    $balanceResult['paid_days'];

                $leaveRequest->unpaid_days =
                    $balanceResult['unpaid_days'];

                $leaveRequest->save();

                $this->logLeaveHistory(
                    $leaveRequest,
                    'approved',
                    $oldStatus,
                    'approved',
                    $oldFromDate,
                    $oldToDate,
                    $approvedFromDate,
                    $approvedToDate,
                    null,
                    [
                        'approved_by' =>
                        $authUser->id,

                        'approved_duration' =>
                        $approvedDuration,

                        'paid_days' =>
                        $balanceResult['paid_days'],

                        'unpaid_days' =>
                        $balanceResult['unpaid_days'],
                    ]
                );

                $this->logLeaveHistory(
                    $leaveRequest,
                    'balance_deducted',
                    'approved',
                    'approved',
                    $approvedFromDate,
                    $approvedToDate,
                    $approvedFromDate,
                    $approvedToDate,
                    'Leave balance deducted after approval.',
                    [
                        'approved_duration' =>
                        $approvedDuration,

                        'paid_days' =>
                        $balanceResult['paid_days'],

                        'unpaid_days' =>
                        $balanceResult['unpaid_days'],

                        'remaining_balance' =>
                        $balanceResult['remaining_balance'],
                    ]
                );
            });
        } catch (\RuntimeException $e) {
            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        $this->notificationService
            ->notifyLeaveRequestReviewed(
                $leaveRequest,
                $authUser,
                'approve'
            );

        return redirect()
            ->route(
                'leave-requests.show',
                $leaveRequest
            )
            ->with(
                'success',
                'Leave request approved successfully.'
            );
    }

    /**
     * Cancel leave request.
     */
    public function cancel(
        Request $request,
        LeaveRequest $leaveRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'cancellation_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $authUser = auth()->user();

        if (
            $leaveRequest->user_id !== $authUser->id
            && !$authUser->can('leave_request.cancel')
            && !$authUser->is_super_admin
        ) {
            abort(403);
        }

        if (
            !in_array(
                $leaveRequest->status,
                ['pending', 'approved'],
                true
            )
        ) {
            return redirect()
                ->route(
                    'leave-requests.index'
                )
                ->with(
                    'error',
                    'This leave request cannot be cancelled.'
                );
        }

        $oldStatus =
            $leaveRequest->status;

        $oldFromDate =
            $leaveRequest->status === 'approved'
            && $leaveRequest->approved_from_date
            ? $leaveRequest->approved_from_date
            : $leaveRequest->requested_from_date;

        $oldToDate =
            $leaveRequest->status === 'approved'
            && $leaveRequest->approved_to_date
            ? $leaveRequest->approved_to_date
            : $leaveRequest->requested_to_date;

        try {
            DB::transaction(function () use (
                $leaveRequest,
                $authUser,
                $validated,
                $oldStatus,
                $oldFromDate,
                $oldToDate
            ) {
                $leaveRequest->refresh();

                if (
                    !in_array(
                        $leaveRequest->status,
                        ['pending', 'approved'],
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        'This leave request has already been processed.'
                    );
                }

                /*
                 * Restore balance for approved leave.
                 */
                if ($leaveRequest->status === 'approved') {
                    if (
                        !$leaveRequest->approved_from_date
                        || !$leaveRequest->approved_to_date
                    ) {
                        throw new \RuntimeException(
                            'Approved leave dates are missing. Balance cannot be restored.'
                        );
                    }

                    $balance = UserLeaveBalance::query()
                        ->where(
                            'user_id',
                            $leaveRequest->user_id
                        )
                        ->where(
                            'leave_type_id',
                            $leaveRequest->leave_type_id
                        )
                        ->where(
                            'status',
                            true
                        )
                        ->whereDate(
                            'valid_from',
                            '<=',
                            Carbon::parse(
                                $leaveRequest->approved_from_date
                            )->toDateString()
                        )
                        ->whereDate(
                            'valid_to',
                            '>=',
                            Carbon::parse(
                                $leaveRequest->approved_to_date
                            )->toDateString()
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$balance) {
                        throw new \RuntimeException(
                            'No active leave balance was found for this user and leave type. Balance cannot be restored.'
                        );
                    }

                    $paidDaysToRestore = round(
                        (float) $leaveRequest->paid_days,
                        2
                    );

                    $unpaidDaysToRestore = round(
                        (float) $leaveRequest->unpaid_days,
                        2
                    );

                    $balance->current_balance = round(
                        (float) $balance->current_balance
                            + $paidDaysToRestore,
                        2
                    );

                    $balance->paid_days_used = round(
                        max(
                            0,
                            (float) $balance->paid_days_used
                                - $paidDaysToRestore
                        ),
                        2
                    );

                    $balance->unpaid_days_used = round(
                        max(
                            0,
                            (float) $balance->unpaid_days_used
                                - $unpaidDaysToRestore
                        ),
                        2
                    );

                    $balance->cancelled_days_restored = round(
                        (float) (
                            $balance->cancelled_days_restored ?? 0
                        )
                            + $paidDaysToRestore,
                        2
                    );

                    $balance->used_balance = round(
                        max(
                            0,
                            (float) $balance->used_balance
                                - (float) $leaveRequest->approved_duration
                        ),
                        2
                    );

                    $balance->updated_by =
                        $authUser->id;

                    $balance->save();

                    $this->logLeaveHistory(
                        $leaveRequest,
                        'balance_restored',
                        'approved',
                        'approved',
                        $leaveRequest->approved_from_date,
                        $leaveRequest->approved_to_date,
                        $leaveRequest->approved_from_date,
                        $leaveRequest->approved_to_date,
                        'Leave balance restored after cancellation.',
                        [
                            'cancelled_by' =>
                            $authUser->id,

                            'approved_duration' =>
                            (float) $leaveRequest->approved_duration,

                            'paid_days_restored' =>
                            $paidDaysToRestore,

                            'unpaid_days_reversed' =>
                            $unpaidDaysToRestore,

                            'remaining_balance' =>
                            (float) $balance->current_balance,
                        ]
                    );
                }

                $leaveRequest->status =
                    'cancelled';

                $leaveRequest->cancelled_by =
                    $authUser->id;

                $leaveRequest->cancelled_at =
                    now();

                $leaveRequest->cancellation_reason =
                    $validated['cancellation_reason'];

                $leaveRequest->save();

                $this->logLeaveHistory(
                    $leaveRequest,
                    'cancelled',
                    $oldStatus,
                    'cancelled',
                    $oldFromDate,
                    $oldToDate,
                    $oldFromDate,
                    $oldToDate,
                    $validated['cancellation_reason'],
                    [
                        'cancelled_by' =>
                        $authUser->id,

                        'balance_restored' =>
                        $oldStatus === 'approved',
                    ]
                );
            });
        } catch (\RuntimeException $e) {
            return redirect()
                ->route(
                    'leave-requests.show',
                    $leaveRequest
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        /*
         * Notify assigned approvers when employee cancels
         * their own request.
         */
        if ($leaveRequest->user_id === $authUser->id) {
            $this->notificationService
                ->notifyLeaveRequestUpdated(
                    $leaveRequest,
                    $authUser->id
                );
        }

        return redirect()
            ->route(
                'leave-requests.show',
                $leaveRequest
            )
            ->with(
                'success',
                'Leave request cancelled successfully.'
            );
    }

    /**
     * Apply approved leave to the user's leave balance.
     *
     * Rules:
     * - current/yearly balance controls paid leave.
     * - monthly entitlement controls paid leave per month.
     * - excess becomes unpaid.
     * - complete approved duration is added to used_balance.
     */
    private function updateLeaveBalanceAfterApproval(
        LeaveRequest $leaveRequest,
        float $approvedDuration,
        Carbon $approvedFromDate,
        Carbon $approvedToDate,
        int $updatedBy
    ): array {
        $balance = UserLeaveBalance::query()
            ->where(
                'user_id',
                $leaveRequest->user_id
            )
            ->where(
                'leave_type_id',
                $leaveRequest->leave_type_id
            )
            ->where(
                'status',
                true
            )
            ->whereDate(
                'valid_from',
                '<=',
                $approvedFromDate->toDateString()
            )
            ->whereDate(
                'valid_to',
                '>=',
                $approvedToDate->toDateString()
            )
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            throw new \RuntimeException(
                'No active leave balance was found for this user and leave type for the approved dates.'
            );
        }

        $monthlyEntitlement = round(
            (float) $balance->monthly_entitlement,
            2
        );

        $currentBalance = round(
            (float) $balance->current_balance,
            2
        );

        $paidDays = 0;

        $unpaidDays = 0;

        $monthlyPaidAllocated = [];

        $date = $approvedFromDate->copy();

        while ($date->lte($approvedToDate)) {
            $dayDuration =
                $leaveRequest->type === 'half_day'
                ? 0.5
                : 1.0;

            $monthKey =
                $date->format('Y-m');

            $alreadyPaidThisMonth =
                $this->getApprovedMonthlyPaidUsage(
                    $leaveRequest,
                    $date
                );

            $currentRequestPaidThisMonth =
                $monthlyPaidAllocated[$monthKey] ?? 0;

            $monthlyRemaining = max(
                0,
                $monthlyEntitlement
                    - $alreadyPaidThisMonth
                    - $currentRequestPaidThisMonth
            );

            $dayPaid = min(
                $dayDuration,
                $monthlyRemaining,
                $currentBalance
            );

            $dayPaid = round(
                max(0, $dayPaid),
                2
            );

            $dayUnpaid = round(
                $dayDuration - $dayPaid,
                2
            );

            $paidDays = round(
                $paidDays + $dayPaid,
                2
            );

            $unpaidDays = round(
                $unpaidDays + $dayUnpaid,
                2
            );

            $monthlyPaidAllocated[$monthKey] = round(
                ($monthlyPaidAllocated[$monthKey] ?? 0)
                    + $dayPaid,
                2
            );

            $currentBalance = round(
                max(
                    0,
                    $currentBalance - $dayPaid
                ),
                2
            );

            $date->addDay();
        }

        $paidDays = round(
            $paidDays,
            2
        );

        $unpaidDays = round(
            $unpaidDays,
            2
        );

        $balance->used_balance = round(
            (float) $balance->used_balance
                + $approvedDuration,
            2
        );

        $balance->paid_days_used = round(
            (float) $balance->paid_days_used
                + $paidDays,
            2
        );

        $balance->unpaid_days_used = round(
            (float) $balance->unpaid_days_used
                + $unpaidDays,
            2
        );

        $balance->current_balance =
            $currentBalance;

        $balance->updated_by =
            $updatedBy;

        $balance->save();

        return [
            'paid_days' =>
            $paidDays,

            'unpaid_days' =>
            $unpaidDays,

            'approved_duration' =>
            $approvedDuration,

            'remaining_balance' =>
            $currentBalance,
        ];
    }

    /**
     * Get paid leave already used in a calendar month.
     */
    private function getApprovedMonthlyPaidUsage(
        LeaveRequest $currentLeaveRequest,
        Carbon $date
    ): float {
        $monthStart = $date
            ->copy()
            ->startOfMonth();

        $monthEnd = $date
            ->copy()
            ->endOfMonth();

        $approvedLeaves = LeaveRequest::query()
            ->where(
                'user_id',
                $currentLeaveRequest->user_id
            )
            ->where(
                'leave_type_id',
                $currentLeaveRequest->leave_type_id
            )
            ->where(
                'status',
                'approved'
            )
            ->whereNotNull(
                'approved_from_date'
            )
            ->whereNotNull(
                'approved_to_date'
            )
            ->where(
                'id',
                '!=',
                $currentLeaveRequest->id
            )
            ->whereDate(
                'approved_from_date',
                '<=',
                $monthEnd->toDateString()
            )
            ->whereDate(
                'approved_to_date',
                '>=',
                $monthStart->toDateString()
            )
            ->get();

        $monthlyPaidUsage = 0;

        foreach ($approvedLeaves as $leave) {
            $leaveFrom = Carbon::parse(
                $leave->approved_from_date
            )->startOfDay();

            $leaveTo = Carbon::parse(
                $leave->approved_to_date
            )->startOfDay();

            $overlapFrom =
                $leaveFrom->greaterThan($monthStart)
                ? $leaveFrom
                : $monthStart;

            $overlapTo =
                $leaveTo->lessThan($monthEnd)
                ? $leaveTo
                : $monthEnd;

            if ($overlapTo->lt($overlapFrom)) {
                continue;
            }

            if (
                $leaveFrom->gte($monthStart)
                && $leaveTo->lte($monthEnd)
            ) {
                $monthlyPaidUsage +=
                    (float) $leave->paid_days;

                continue;
            }

            $totalDays =
                $leaveFrom->diffInDays(
                    $leaveTo
                ) + 1;

            $overlapDays =
                $overlapFrom->diffInDays(
                    $overlapTo
                ) + 1;

            if ($totalDays <= 0) {
                continue;
            }

            $totalDuration =
                $leave->type === 'half_day'
                ? $totalDays * 0.5
                : $totalDays;

            $overlapDuration =
                $leave->type === 'half_day'
                ? $overlapDays * 0.5
                : $overlapDays;

            if ($totalDuration > 0) {
                $paidRatio =
                    (float) $leave->paid_days
                    / $totalDuration;

                $monthlyPaidUsage +=
                    $overlapDuration
                    * $paidRatio;
            }
        }

        return round(
            $monthlyPaidUsage,
            2
        );
    }

    /**
     * Find an existing pending/approved leave that overlaps
     * the supplied date range.
     */
    private function findOverlappingLeave(
        int $userId,
        Carbon $fromDate,
        Carbon $toDate,
        ?int $ignoreLeaveRequestId = null
    ): ?LeaveRequest {
        $query = LeaveRequest::query()
            ->where(
                'user_id',
                $userId
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'approved',
                ]
            )
            ->where(
                function ($query) use (
                    $fromDate,
                    $toDate
                ) {
                    $query->where(
                        function ($q) use (
                            $fromDate,
                            $toDate
                        ) {
                            $q->where(
                                'status',
                                'pending'
                            )
                                ->whereDate(
                                    'requested_from_date',
                                    '<=',
                                    $toDate->toDateString()
                                )
                                ->whereDate(
                                    'requested_to_date',
                                    '>=',
                                    $fromDate->toDateString()
                                );
                        }
                    )
                        ->orWhere(
                            function ($q) use (
                                $fromDate,
                                $toDate
                            ) {
                                $q->where(
                                    'status',
                                    'approved'
                                )
                                    ->whereNotNull(
                                        'approved_from_date'
                                    )
                                    ->whereNotNull(
                                        'approved_to_date'
                                    )
                                    ->whereDate(
                                        'approved_from_date',
                                        '<=',
                                        $toDate->toDateString()
                                    )
                                    ->whereDate(
                                        'approved_to_date',
                                        '>=',
                                        $fromDate->toDateString()
                                    );
                            }
                        );
                }
            );

        if ($ignoreLeaveRequestId !== null) {
            $query->where(
                'id',
                '!=',
                $ignoreLeaveRequestId
            );
        }

        return $query->first();
    }

    /**
     * Save leave request attachment.
     *
     * Returns true when a new attachment was uploaded.
     */
    private function saveAttachment(
        Request $request,
        LeaveRequest $leaveRequest
    ): bool {
        if (
            !$request->hasFile('attachment')
            || !$request->file('attachment')->isValid()
        ) {
            return false;
        }

        $attachmentService =
            app(AttachmentService::class);

        $attachmentService->upload(
            $request->file('attachment'),
            'leave_requests',
            $leaveRequest,
            'public',
            'public',
            false,
            'leave_request'
        );

        return true;
    }

    /**
     * Store a leave request history entry.
     */
    private function logLeaveHistory(
        LeaveRequest $leaveRequest,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        $oldFromDate = null,
        $oldToDate = null,
        $newFromDate = null,
        $newToDate = null,
        ?string $reason = null,
        array $metadata = []
    ): void {
        LeaveRequestHistory::create([
            'leave_request_id' =>
            $leaveRequest->id,

            'user_id' =>
            auth()->id(),

            'action' =>
            $action,

            'old_status' =>
            $oldStatus,

            'new_status' =>
            $newStatus,

            'old_from_date' =>
            $oldFromDate
                ? Carbon::parse(
                    $oldFromDate
                )->toDateString()
                : null,

            'old_to_date' =>
            $oldToDate
                ? Carbon::parse(
                    $oldToDate
                )->toDateString()
                : null,

            'new_from_date' =>
            $newFromDate
                ? Carbon::parse(
                    $newFromDate
                )->toDateString()
                : null,

            'new_to_date' =>
            $newToDate
                ? Carbon::parse(
                    $newToDate
                )->toDateString()
                : null,

            'reason' =>
            $reason,

            'metadata' =>
            $metadata,
        ]);
    }
}
