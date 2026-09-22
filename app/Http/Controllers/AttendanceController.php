<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;

class AttendanceController extends Controller
{
    protected string $pageTitle = 'Attendance';

    protected string $subTitle =
    'Manage and track employee attendance.';

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
 * Attendance index.
 */
public function index(Request $request): View
{
    $selectedDate = $request->filled('calendar_date')
        ? Carbon::parse($request->calendar_date)
        : today();

    $loggedInUser = auth()->user();

    /*
     * ==========================================================
     * Users
     * ==========================================================
     *
     * Keep all users for the filter dropdown.
     */
    $users = User::query()
        ->orderBy('name')
        ->get();

    /*
     * Users that the logged-in user is allowed to manage/mark
     * attendance for.
     */
    $attendanceUsers = $this->userService
        ->getAccessibleUsers($loggedInUser)
        ->values();

    /*
     * ==========================================================
     * Calendar Range
     * ==========================================================
     */
    $calendarStart = $selectedDate
        ->copy()
        ->startOfMonth()
        ->startOfWeek(Carbon::MONDAY);

    $calendarEnd = $selectedDate
        ->copy()
        ->endOfMonth()
        ->endOfWeek(Carbon::SUNDAY);

    $totalDays = (int) $calendarStart->diffInDays(
        $calendarEnd
    ) + 1;

    /*
     * ==========================================================
     * Common Filters
     * ==========================================================
     */
    $userId = $request->input('user_id');
    $leaveTypeId = $request->input('leave_type_id');
    $search = trim((string) $request->input('search'));

    /*
     * ==========================================================
     * Attendance
     * ==========================================================
     *
     * Attendance is still loaded for the selected date.
     */
    $attendances = Attendance::query()
        ->with([
            'user',
            'markedBy',
            'leaveRequest.leaveType',
        ])
        ->when(! empty($userId), function (Builder $query) use ($userId) {
            $value = $userId;

            is_array($value)
                ? $query->whereIn('user_id', array_filter($value))
                : $query->where('user_id', $value);
        })
        ->when(! empty($search), function (Builder $query) use ($search) {
            $query->whereHas('user', function (Builder $userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->whereDate(
            'attendance_date',
            $selectedDate->toDateString()
        )
        ->get()
        ->keyBy('user_id');

    /*
     * ==========================================================
     * Approved Leaves - Selected Date
     * ==========================================================
     */
    $approvedLeaves = LeaveRequest::query()
        ->with([
            'user',
            'leaveType',
        ])
        ->where('status', 'approved')
        ->whereNotNull('approved_from_date')
        ->whereNotNull('approved_to_date')
        ->when(! empty($userId), function (Builder $query) use ($userId) {
            $value = $userId;

            is_array($value)
                ? $query->whereIn('user_id', array_filter($value))
                : $query->where('user_id', $value);
        })
        ->when(! empty($leaveTypeId), function (Builder $query) use ($leaveTypeId) {
            $value = $leaveTypeId;

            is_array($value)
                ? $query->whereIn('leave_type_id', array_filter($value))
                : $query->where('leave_type_id', $value);
        })
        ->when(! empty($search), function (Builder $query) use ($search) {
            $query->whereHas('user', function (Builder $userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->whereDate(
            'approved_from_date',
            '<=',
            $selectedDate->toDateString()
        )
        ->whereDate(
            'approved_to_date',
            '>=',
            $selectedDate->toDateString()
        )
        ->get()
        ->keyBy('user_id');

    /*
     * ==========================================================
     * Calendar Leave Requests
     * ==========================================================
     *
     * These are used to display leaves across the complete
     * Monday -> Sunday calendar range.
     */
    $calendarLeaveRequests = LeaveRequest::query()
        ->with([
            'user',
            'leaveType',
        ])
        ->whereIn('status', [
            'approved',
            'pending',
        ])
        ->when(! empty($userId), function (Builder $query) use ($userId) {
            $value = $userId;

            is_array($value)
                ? $query->whereIn('user_id', array_filter($value))
                : $query->where('user_id', $value);
        })
        ->when(! empty($leaveTypeId), function (Builder $query) use ($leaveTypeId) {
            $value = $leaveTypeId;

            is_array($value)
                ? $query->whereIn('leave_type_id', array_filter($value))
                : $query->where('leave_type_id', $value);
        })
        ->when(! empty($search), function (Builder $query) use ($search) {
            $query->whereHas('user', function (Builder $userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->where(function (Builder $query) use (
            $calendarStart,
            $calendarEnd
        ) {

            /*
            * ======================================================
            * Approved Leave
            * ======================================================
            */
            $query->where(function (Builder $approvedQuery) use (
                $calendarStart,
                $calendarEnd
            ) {
                $approvedQuery
                    ->where('status', 'approved')
                    ->whereNotNull('approved_from_date')
                    ->whereNotNull('approved_to_date')
                    ->whereDate(
                        'approved_from_date',
                        '<=',
                        $calendarEnd->toDateString()
                    )
                    ->whereDate(
                        'approved_to_date',
                        '>=',
                        $calendarStart->toDateString()
                    );
            })

            /*
            * ======================================================
            * Pending Leave
            * ======================================================
            */
            ->orWhere(function (Builder $pendingQuery) use (
                $calendarStart,
                $calendarEnd
            ) {
                $pendingQuery
                    ->where('status', 'pending')
                    ->whereNotNull('requested_from_date')
                    ->whereNotNull('requested_to_date')
                    ->whereDate(
                        'requested_from_date',
                        '<=',
                        $calendarEnd->toDateString()
                    )
                    ->whereDate(
                        'requested_to_date',
                        '>=',
                        $calendarStart->toDateString()
                    );
            });
        })
        ->get();

    /*
    * ==========================================================
    * Build Calendar Leaves
    * ==========================================================
    */
    $calendarLeaves = collect();

    foreach ($calendarLeaveRequests as $leave) {

        /*
        * ----------------------------------------------------------
        * Determine the correct date range.
        * ----------------------------------------------------------
        *
        * Approved:
        *   approved_from_date -> approved_to_date
        *
        * Pending:
        *   requested_from_date -> requested_to_date
        */
        if ($leave->status === 'approved') {
            $leaveStartDate = $leave->approved_from_date;
            $leaveEndDate = $leave->approved_to_date;
        } else {
            $leaveStartDate = $leave->requested_from_date;
            $leaveEndDate = $leave->requested_to_date;
        }

        /*
        * Skip if the leave does not have valid dates.
        */
        if (
            empty($leaveStartDate)
            || empty($leaveEndDate)
        ) {
            continue;
        }

        $leaveStart = Carbon::parse($leaveStartDate);
        $leaveEnd = Carbon::parse($leaveEndDate);

        /*
        * Restrict the leave to the visible calendar range.
        */
        if ($leaveStart->lt($calendarStart)) {
            $leaveStart = $calendarStart->copy();
        }

        if ($leaveEnd->gt($calendarEnd)) {
            $leaveEnd = $calendarEnd->copy();
        }

        /*
        * Add the leave to every date covered by the request.
        */
        for (
            $leaveDate = $leaveStart->copy();
            $leaveDate->lte($leaveEnd);
            $leaveDate->addDay()
        ) {

            $dateKey = $leaveDate->format('Y-m-d');

            if (! $calendarLeaves->has($dateKey)) {
                $calendarLeaves->put(
                    $dateKey,
                    collect()
                );
            }

            $calendarLeaves
                ->get($dateKey)
                ->push($leave);
        }
    }

    /*
    * ==========================================================
    * Calendar Leaves For JavaScript
    * ==========================================================
    */
    $calendarLeavesForJs = $calendarLeaves
        ->map(function ($leaves) {

            return $leaves
                ->map(function ($leave) {

                    /*
                    * Use requested dates for pending leaves
                    * and approved dates for approved leaves.
                    */
                    if ($leave->status === 'approved') {
                        $fromDate = $leave->approved_from_date;
                        $toDate = $leave->approved_to_date;
                    } else {
                        $fromDate = $leave->requested_from_date;
                        $toDate = $leave->requested_to_date;
                    }

                    return [
                        'employee' => $leave->user?->name
                            ?? 'Employee',

                        'leave_type' => $leave->leaveType?->name
                            ?? 'Leave',

                        'color' => $leave->leaveType?->color
                            ?? '#3B82F6',

                        'from_date' => $fromDate
                            ? Carbon::parse($fromDate)->format('d M Y')
                            : '',

                        'to_date' => $toDate
                            ? Carbon::parse($toDate)->format('d M Y')
                            : '',

                        'reason' => $leave->reason ?? '',

                        'type' => $leave->type,

                        'half_day_type' => $leave->half_day_type,

                        'approved_duration' => $leave->approved_duration,

                        'duration' => $leave->duration,

                        /*
                        * Useful for distinguishing pending
                        * and approved leaves in JavaScript.
                        */
                        'status' => $leave->status,
                    ];
                })
                ->values();
        })
        ->toArray();

    /*
    * ==========================================================
    * Calendar Events
    * ==========================================================
    */
    $calendarEvents = $this->getCalendarEvents();

    /*
    * ==========================================================
    * Leave Types
    * ==========================================================
    */
    $leaveTypes = LeaveType::query()
        ->where('status', true)
        ->orderBy('name')
        ->get();

    /*
    * ==========================================================
    * View
    * ==========================================================
    */
    return view(
        'attendance.index',
        [
            'pageTitle' => $this->pageTitle,
            'subTitle' => $this->subTitle,

            'users' => $users,
            'attendanceUsers' => $attendanceUsers,

            'attendances' => $attendances,
            'approvedLeaves' => $approvedLeaves,

            'selectedDate' => $selectedDate,

            'calendarEvents' => $calendarEvents,

            'calendarStart' => $calendarStart,
            'calendarEnd' => $calendarEnd,
            'totalDays' => $totalDays,

            'calendarLeaves' => $calendarLeaves,
            'calendarLeavesForJs' => $calendarLeavesForJs,

            'leaveTypes' => $leaveTypes,
        ]
    );
}

    /**
     * Store attendance.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
            ],

            'attendance_date' => [
                'required',
                'date',
            ],

            'status' => [
                'required',
                'in:present,absent,half_day,leave,holiday,weekend,not_marked',
            ],

            'leave_source' => [
                'nullable',
                'in:reported,approved',
            ],

            'leave_request_id' => [
                'nullable',
                'exists:leave_requests,id',
            ],

            'check_in' => [
                'nullable',
                'date_format:H:i',
            ],

            'check_out' => [
                'nullable',
                'date_format:H:i',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        /*
         * ==========================================================
         * Authorize selected user
         * ==========================================================
         *
         * This is important even though the dropdown only displays
         * accessible users.
         *
         * The backend must not trust the submitted user_id.
         */
        $loggedInUser = auth()->user();

        $accessibleUserIds = $this->userService
            ->getAccessibleUsers($loggedInUser)
            ->pluck('id')
            ->map(
                fn($id) => (int) $id
            )
            ->all();

        if (
            !$loggedInUser->is_super_admin
            && !in_array(
                (int) $validated['user_id'],
                $accessibleUserIds,
                true
            )
        ) {
            abort(
                403,
                'You are not authorized to mark attendance for this user.'
            );
        }

        /*
         * Make sure the selected user is an active user.
         */
        $selectedUser = User::query()
            ->where(
                'id',
                $validated['user_id']
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'delete_status',
                false
            )
            ->whereNull(
                'deleted_at'
            )
            ->first();

        if (!$selectedUser) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'The selected user is not an active employee.'
                );
        }

        /*
         * If attendance is leave, default source to reported.
         */
        if (
            $validated['status'] === 'leave'
        ) {
            if (
                empty($validated['leave_source'])
            ) {
                $validated['leave_source'] =
                    'reported';
            }
        } else {
            $validated['leave_source'] =
                null;

            $validated['leave_request_id'] =
                null;
        }

        /*
         * User who marked the attendance.
         */
        $validated['marked_by'] =
            auth()->id();

        /*
         * Calculate working hours.
         */
        $validated['working_hours'] =
            $this->calculateWorkingHours(
                $validated['check_in'] ?? null,
                $validated['check_out'] ?? null
            );

        Attendance::updateOrCreate(
            [
                'user_id' =>
                $validated['user_id'],

                'attendance_date' =>
                $validated['attendance_date'],
            ],
            $validated
        );

        return redirect()
            ->route(
                'attendance.index',
                [
                    'date' =>
                    $validated['attendance_date'],
                ]
            )
            ->with(
                'success',
                'Attendance updated successfully.'
            );
    }

    /**
     * Update attendance.
     */
    public function update(
        Request $request,
        Attendance $attendance
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:present,absent,half_day,leave,holiday,weekend,not_marked',
            ],

            'leave_source' => [
                'nullable',
                'in:reported,approved',
            ],

            'leave_request_id' => [
                'nullable',
                'exists:leave_requests,id',
            ],

            'check_in' => [
                'nullable',
                'date_format:H:i',
            ],

            'check_out' => [
                'nullable',
                'date_format:H:i',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        /*
         * Only leave attendance can have a leave source/request.
         */
        if (
            $validated['status'] !== 'leave'
        ) {
            $validated['leave_source'] =
                null;

            $validated['leave_request_id'] =
                null;
        }

        /*
         * User who updated the attendance.
         */
        $validated['marked_by'] =
            auth()->id();

        /*
         * Calculate working hours.
         */
        $validated['working_hours'] =
            $this->calculateWorkingHours(
                $validated['check_in'] ?? null,
                $validated['check_out'] ?? null
            );

        $attendance->update(
            $validated
        );

        return redirect()
            ->route(
                'attendance.index',
                [
                    'date' =>
                    $attendance
                        ->attendance_date
                        ->toDateString(),
                ]
            )
            ->with(
                'success',
                'Attendance updated successfully.'
            );
    }

    /**
     * Get attendance calendar events.
     */
    protected function getCalendarEvents()
    {
        /*
         * ==========================================================
         * Existing attendance events.
         * ==========================================================
         */
        $attendances = Attendance::query()
            ->with([
                'user',
                'leaveRequest.leaveType',
            ])
            ->get();

        $events = $attendances
            ->map(function ($attendance) {
                return [
                    'id' =>
                    'attendance-'
                        . $attendance->id,

                    'title' => ($attendance->user->name
                        ?? 'Employee')
                        . ' - '
                        . ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $attendance->status
                            )
                        ),

                    'start' =>
                    $attendance
                        ->attendance_date
                        ->toDateString(),

                    'allDay' => true,

                    'url' =>
                    route(
                        'attendance.index',
                        [
                            'date' =>
                            $attendance
                                ->attendance_date
                                ->toDateString(),
                        ]
                    ),

                    'extendedProps' => [
                        'employee' =>
                        $attendance
                            ->user
                            ->name
                            ?? '-',

                        'status' =>
                        $attendance->status,

                        'leave_source' =>
                        $attendance
                            ->leave_source,

                        'remarks' =>
                        $attendance->remarks,
                    ],
                ];
            });

        /*
         * ==========================================================
         * Approved leave events.
         * ==========================================================
         *
         * IMPORTANT:
         *
         * Use approved dates, not requested dates.
         */
        $approvedLeaves =
            LeaveRequest::query()
            ->with([
                'user',
                'leaveType',
            ])
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
            ->get();

        foreach (
            $approvedLeaves as $leave
        ) {
            $approvedFromDate =
                Carbon::parse(
                    $leave->approved_from_date
                );

            $approvedToDate =
                Carbon::parse(
                    $leave->approved_to_date
                );

            /*
             * ======================================================
             * Full-day leave
             * ======================================================
             *
             * For calendar display we still use the entire
             * approved date range.
             */
            if (
                $leave->type === 'full_day'
            ) {
                $events->push([
                    'id' =>
                    'leave-'
                        . $leave->id,

                    'title' => ($leave->user->name
                        ?? 'Employee')
                        . ' - Approved Leave',

                    'start' =>
                    $approvedFromDate
                        ->toDateString(),

                    'end' =>
                    $approvedToDate
                        ->copy()
                        ->addDay()
                        ->toDateString(),

                    'allDay' => true,

                    'url' =>
                    route(
                        'leave-requests.show',
                        $leave->id
                    ),

                    'extendedProps' => [
                        'status' =>
                        'approved',

                        'employee' =>
                        $leave
                            ->user
                            ->name
                            ?? '-',

                        'leaveType' =>
                        $leave
                            ->leaveType
                            ->name
                            ?? 'Leave',

                        'source' =>
                        'leave_request',

                        'type' =>
                        $leave->type,

                        'leave_period' =>
                        $leave->leave_period,

                        'approved_duration' =>
                        $leave
                            ->approved_duration,
                    ],
                ]);

                continue;
            }

            /*
             * ======================================================
             * Multi-day half-day leave
             * ======================================================
             *
             * Create one calendar event per date.
             *
             * Example:
             *
             * 04 Sep - 09 Sep
             * Morning
             *
             * creates six half-day entries.
             */
            for (
                $leaveDate =
                    $approvedFromDate->copy();

                $leaveDate->lte(
                    $approvedToDate
                );

                $leaveDate->addDay()
            ) {
                $events->push([
                    'id' =>
                    'leave-'
                        . $leave->id
                        . '-'
                        . $leaveDate
                        ->format('Y-m-d'),

                    'title' => ($leave->user->name
                        ?? 'Employee')
                        . ' - Approved Half Day',

                    'start' =>
                    $leaveDate
                        ->toDateString(),

                    /*
                     * One day event.
                     */
                    'end' =>
                    $leaveDate
                        ->copy()
                        ->addDay()
                        ->toDateString(),

                    'allDay' => true,

                    'url' =>
                    route(
                        'leave-requests.show',
                        $leave->id
                    ),

                    'extendedProps' => [
                        'status' =>
                        'approved',

                        'employee' =>
                        $leave
                            ->user
                            ->name
                            ?? '-',

                        'leaveType' =>
                        $leave
                            ->leaveType
                            ->name
                            ?? 'Leave',

                        'source' =>
                        'leave_request',

                        'type' =>
                        $leave->type,

                        'leave_period' =>
                        $leave->leave_period,

                        'approved_duration' =>
                        $leave
                            ->approved_duration,

                        'approved_date' =>
                        $leaveDate
                            ->toDateString(),
                    ],
                ]);
            }
        }

        return $events->values();
    }

    /**
     * Calculate working hours.
     */
    protected function calculateWorkingHours(
        ?string $checkIn,
        ?string $checkOut
    ): ?float {
        if (
            !$checkIn
            || !$checkOut
        ) {
            return null;
        }

        $start =
            Carbon::createFromFormat(
                'H:i',
                $checkIn
            );

        $end =
            Carbon::createFromFormat(
                'H:i',
                $checkOut
            );

        if (
            $end->lessThan($start)
        ) {
            $end->addDay();
        }

        return round(
            $start->diffInMinutes($end)
                / 60,
            2
        );
    }
}
