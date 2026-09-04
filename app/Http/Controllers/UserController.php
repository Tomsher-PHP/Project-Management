<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\InitialShiftAssignmentRequest;
use App\Http\Requests\UserRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Kpi;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGeneralSetting;
use App\Models\UserNotificationSetting;
use App\Models\UserSetting;
use App\Services\ScheduleShiftService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use App\Models\UserLeaveBalance;
use App\Models\LeaveType;

class UserController extends Controller
{
    protected string $pageTitle;
    protected UserService $service;

    public function __construct()
    {
        $this->service = app(UserService::class);

        $this->pageTitle = 'User Management';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $users = User::accessibleBy(auth()->user())
            ->filter($request->all())
            ->sort($request->all())
            ->orderBy('users.id', 'desc')
            ->with([
                'details',
                'details.department',
                'details.designation',
                'primaryAttachment'
            ])
            ->where('is_super_admin', false)
            ->where('delete_status', false)
            ->whereNot('id', auth()->id())
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::get();
        $departments = Department::withTrashed()->orderBy('sort_order', 'asc')->get();
        $designations = Designation::withTrashed()->orderBy('sort_order', 'asc')->get();

        return view('users.index', compact('users', 'perPage', 'roles', 'departments', 'designations'));
    }

    public function create()
    {
        //get roles
        $roles = Role::active()->get();

        //Department and Designation can be added later if needed
        $departments = Department::active()->orderBy('sort_order', 'asc')->get();
        $designations = Designation::active()->orderBy('sort_order', 'asc')->get();
        $nextDepartmentSortOrder = ((int) Department::max('sort_order')) + 1;
        $nextDesignationSortOrder = ((int) Designation::max('sort_order')) + 1;
        $kpis = Kpi::active()->orderBy('id', 'asc')->get();

        // Get reporter and managers
        $managers = app(UserService::class)->getAccessibleUsers(auth()->user());

        return view('users.create', compact(
            'roles',
            'departments',
            'designations',
            'managers',
            'nextDepartmentSortOrder',
            'nextDesignationSortOrder',
            'kpis'
        ));
    }

    public function store(UserRequest $request, UserService $service)
    {
        $user = $service->createUser($request->validated());

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.')
            ->with('initial_shift_user_id', $user->id);
    }

    public function storeInitialShift(InitialShiftAssignmentRequest $request, User $user, ScheduleShiftService $scheduleShiftService)
    {
        $data = $request->validated();
        $data['users'] = [$user->id];

        $scheduleShiftService->schedule($data);

        return redirect()
            ->route('users.index', [
                'leave_assignment_user_id' => $user->id,
            ])
            ->with('success', 'Initial shift assigned successfully.');
    }

    public function edit(User $user)
    {
        $user->loadMissing('details');

        $selectedRoleId = $user->role_id;
        $selectedDepartmentId = $user->details?->department_id;
        $selectedDesignationId = $user->details?->designation_id;

        $roles = Role::query()
            ->where(function ($query) use ($selectedRoleId) {
                $query->active();

                if (filled($selectedRoleId)) {
                    $query->orWhere('id', $selectedRoleId);
                }
            })
            ->get();

        $departments = Department::forForm($selectedDepartmentId, ['order_by' => 'sort_order', 'direction' => 'asc'])->get();
        $designations = Designation::forForm($selectedDesignationId, ['order_by' => 'sort_order', 'direction' => 'asc'])->get();

        $nextDepartmentSortOrder = ((int) Department::max('sort_order')) + 1;
        $nextDesignationSortOrder = ((int) Designation::max('sort_order')) + 1;
        $managerIds = collect([
            $user->details?->reporter_id,
            $user->details?->manager_id,
        ])->filter()->unique()->values()->all();

        $DownLevelUserIds = User::getReporterHierarchyUserIds($user->id); // Get downlevel users for exclude in manager select
        $excludeIds = array_merge($DownLevelUserIds, [$user->id]);
        $managers = app(UserService::class)->getAccessibleUsers(auth()->user(), $excludeIds, $managerIds);
        $kpis = Kpi::active()->orderBy('id', 'asc')->get();

        return view('users.edit', compact(
            'user',
            'roles',
            'departments',
            'designations',
            'managers',
            'nextDepartmentSortOrder',
            'nextDesignationSortOrder',
            'kpis'
        ));
    }

    public function update(UserRequest $request, User $user, UserService $service)
    {
        $service->updateUser($user, $request->validated());

        return redirect(session('users_return_url', route('users.index')))->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->isRunningTask()) {
            return redirect()->back()->with('error', 'Stop running tasks before deleting the user.');
        }

        $user->update([
            'delete_status' => true,
            'is_active' => false,
        ]);

        $this->service->disableUserDependencies($user);

        return redirect(session('users_return_url', route('users.index')))->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        DB::transaction(function () use ($request, &$user) {
            $user = User::findOrFail($request->id);

            $user->is_active = ! $user->is_active;
            $user->save();

            // only disabled users
            if ($user->is_active == false) {
                $this->service->disableUserDependencies($user);
            }
        });

        return response()->json([
            'success' => true,
            'is_active' => $user->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Function to view user details.
     *
     * @param User $user
     */
    public function show(User $user)
    {
        $user->load([
            'details',
            'details.department',
            'details.designation',
            'roles',
            'primaryAttachment',
            'generalSettings',
            'settings',
        ]);

        $userNotificationSettings = config('notification_settings');
        $userSettings = config('constants.user_settings');

        $generalSettings = $user->generalSettings;

        return view('users.show', compact('user', 'userNotificationSettings', 'userSettings', 'generalSettings'));
    }

    public function updateNotificationSettings(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|string',
            'field' => 'required|in:in_app,mail',
            'value' => 'required|boolean',
        ]);

        $boolVal = filter_var($request->value, FILTER_VALIDATE_BOOLEAN);

        if ($request->action === 'all') {
            $userNotificationSettings = config('notification_settings', []);
            $actions = collect($userNotificationSettings)->pluck('action')->filter()->unique();

            foreach ($actions as $act) {
                $setting = UserNotificationSetting::firstOrCreate([
                    'user_id' => $request->user_id,
                    'action' => $act,
                ]);

                $setting->{$request->field} = $boolVal;
                $setting->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully'
            ], Response::HTTP_OK);
        }

        $setting = UserNotificationSetting::firstOrCreate([
            'user_id' => $request->user_id,
            'action' => $request->action,
        ]);

        $setting->{$request->field} = $boolVal;
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully'
        ], Response::HTTP_OK);
    }

    public function updateGeneralSettings(Request $request)
    {
        $userSettingKeys = array_keys(config('constants.user_settings', []));

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'field' => 'required|in:kanban_view,theme,' . implode(',', $userSettingKeys),
        ]);

        if (in_array($request->field, $userSettingKeys, true)) {
            if (!auth()->user()->can('user.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update user settings.'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'value' => 'required|boolean',
            ]);

            UserSetting::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'key' => $request->field,
                ],
                [
                    'value' => filter_var($request->value, FILTER_VALIDATE_BOOLEAN),
                ]
            );
        } else {
            $request->validate([
                'value' => 'required|string',
            ]);

            UserGeneralSetting::updateOrCreate(
                ['user_id' => $request->user_id],
                [
                    $request->field => $request->value
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'General settings updated successfully'
        ]);
    }

    /**
     * Function to change password for a user. Only super admin can change password of other users.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = User::findOrFail($request->user_id);

        if (!auth()->user()->is_super_admin) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'errors' => [
                        'current_password' => [
                            'Current password is incorrect.'
                        ]
                    ]
                ], 422);
            }
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    public function updateModal(Request $request, User $user, UserService $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'remove_profile_image' => 'nullable',
        ]);

        $service->updateModalUser($user, $validated);

        return response()->json([
            'message' => 'User updated successfully.'
        ]);
    }

    /**
     * Get shift assignment data for the calendar.
     */
    public function shifts(User $user)
    {
        if (auth()->id() !== $user->id && !auth()->user()->is_super_admin) {
            Gate::authorize('view', $user);
        }

        $startLimit = request('start') ? Carbon::parse(request('start'))->startOfDay() : Carbon::now()->startOfMonth();
        $endLimit = request('end') ? Carbon::parse(request('end'))->endOfDay() : Carbon::now()->endOfMonth();

        $shifts = $user->shiftAssignments()
            ->with('weekends')
            ->select(['id', 'user_id', 'shift_id', 'shift_name', 'color_code', 'date_from', 'date_to', 'time_from', 'time_to'])
            ->get();

        $events = $this->generateEventsForRange($shifts, $startLimit, $endLimit);

        return response()->json($events);
    }

    /**
     * Get shift calendar data for a specific user and month.
     */
    public function shiftCalendarData(User $user)
    {
        if (auth()->id() !== $user->id && !auth()->user()->is_super_admin) {
            Gate::authorize('view', $user);
        }

        $year = request('year') ? (int) request('year') : (int) date('Y');
        $month = request('month') ? (int) request('month') : (int) date('m');

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $shifts = $user->shiftAssignments()
            ->with('weekends')
            ->whereDate('date_from', '<=', $endOfMonth->toDateString())
            ->where(function ($query) use ($startOfMonth) {
                $query->whereNull('date_to')
                    ->orWhereDate('date_to', '>=', $startOfMonth->toDateString());
            })
            ->select(['id', 'user_id', 'shift_id', 'shift_name', 'color_code', 'date_from', 'date_to', 'time_from', 'time_to'])
            ->get();

        $events = $this->generateEventsForRange($shifts, $startOfMonth, $endOfMonth);

        return response()->json($events);
    }

    private function generateEventsForRange($shifts, $startLimit, $endLimit)
    {
        $events = collect();

        foreach ($shifts as $assignment) {
            $dateFrom = Carbon::parse($assignment->date_from)->startOfDay();
            $dateTo = $assignment->date_to ? Carbon::parse($assignment->date_to)->endOfDay() : null;

            // Determine the overlap range
            $loopStart = $dateFrom->greaterThan($startLimit) ? $dateFrom->copy() : $startLimit->copy();
            $loopEnd = $dateTo && $dateTo->lessThan($endLimit) ? $dateTo->copy() : $endLimit->copy();

            if ($loopStart->greaterThan($loopEnd)) {
                continue;
            }

            // Grouping state variables
            $currentType = null; // 'working' or 'weekend'
            $segmentStart = null;

            $currentDate = $loopStart->copy();
            while ($currentDate->lessThanOrEqualTo($loopEnd)) {
                $weekOfMonth = (int) ceil($currentDate->day / 7);
                $dayOfWeek = $currentDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

                // Check if weekend day-off
                $isWeekend = $assignment->weekends
                    ->where('weekday', $dayOfWeek)
                    ->where('week_number', $weekOfMonth)
                    ->isNotEmpty();

                $dayType = $isWeekend ? 'weekend' : 'working';

                if ($currentType === null) {
                    $currentType = $dayType;
                    $segmentStart = $currentDate->copy();
                } elseif ($currentType !== $dayType) {
                    // Type changed, push the previous segment
                    $events->push(
                        $this->createEventPayload($assignment, $currentType, $segmentStart, $currentDate)
                    );
                    $currentType = $dayType;
                    $segmentStart = $currentDate->copy();
                }

                $currentDate->addDay();
            }

            // Push the final segment
            if ($currentType !== null && $segmentStart !== null) {
                $events->push(
                    $this->createEventPayload($assignment, $currentType, $segmentStart, $currentDate)
                );
            }
        }

        return $events;
    }

    private function createEventPayload($assignment, $type, $start, $end)
    {
        if ($type === 'weekend') {
            return [
                'title' => 'Day Off',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'className' => 'fc-event-day-off',
                'textColor' => '#b45309',
                'allDay' => true,
            ];
        }

        // Working day
        $title = $assignment->shift_name;
        if ($assignment->time_from && $assignment->time_to) {
            $timeFrom = Carbon::parse($assignment->time_from)->format('h:i A');
            $timeTo = Carbon::parse($assignment->time_to)->format('h:i A');
            $title .= " ({$timeFrom} - {$timeTo})";
        }

        $rgbaColor = $this->hexToRgba($assignment->color_code ?? '#e5e7eb', 0.8);

        return [
            'title' => $title,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'backgroundColor' => $rgbaColor,
            'borderColor' => $assignment->color_code ?? '#e5e7eb',
            'textColor' => '#000000',
            'allDay' => true,
        ];
    }

    private function hexToRgba($hex, $opacity = 0.8)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } elseif (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            $r = 229;
            $g = 231;
            $b = 235;
        }
        return "rgba($r, $g, $b, $opacity)";
    }

    public function skipInitialShift(User $user)
    {
        return redirect()
            ->route('users.index')
            ->with('leave_assignment_user_id', $user->id);
    }

   public function leaveDetails(User $user, Request $request)
    {
        /*
        * --------------------------------------------------------------------------
        * Get all leave balances for the user.
        * --------------------------------------------------------------------------
        */
        $leaveBalances = UserLeaveBalance::query()
            ->with('leaveType')
            ->where('user_id', $user->id)
            ->orderByDesc('year')
            ->orderBy('valid_from')
            ->orderBy('leave_type_id')
            ->get();


        /*
        * --------------------------------------------------------------------------
        * Group leave balances by entitlement year.
        * --------------------------------------------------------------------------
        */
        $leavePeriods = $leaveBalances->groupBy('year');


        /*
        * --------------------------------------------------------------------------
        * Get active leave types.
        * --------------------------------------------------------------------------
        */
        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();


        /*
        * --------------------------------------------------------------------------
        * Check whether Add New Leave Year modal should be shown.
        * --------------------------------------------------------------------------
        */
        $showLeaveAssignmentModal = $request->boolean('add');


        /*
        * --------------------------------------------------------------------------
        * Check whether Edit Leave Assignment modal should be shown.
        * --------------------------------------------------------------------------
        */
        $editYear = $request->input('edit_year');


        /*
        * --------------------------------------------------------------------------
        * Edit balances.
        * --------------------------------------------------------------------------
        */
        $editBalances = collect();


        /*
        * --------------------------------------------------------------------------
        * Previous year balances used for carry forward.
        * This is needed for both:
        *
        * 1. Add New Leave Year
        * 2. Edit Existing Leave Year
        * --------------------------------------------------------------------------
        */
        $previousBalances = collect();


        /*
        * --------------------------------------------------------------------------
        * Default dates for a new leave period.
        *
        * If the user has no leave details:
        *
        *     From = current year's first day
        *     To   = current year's last day
        *
        * Example:
        *
        *     01-Jan-2026 → 31-Dec-2026
        * --------------------------------------------------------------------------
        */
        $defaultValidFrom = now()->startOfYear();
        $defaultValidTo = now()->endOfYear();


        /*
        * --------------------------------------------------------------------------
        * Get the latest entitlement record.
        *
        * All leave types for the same period have the same valid_from/valid_to,
        * so we can use the latest record to determine the next entitlement period.
        * --------------------------------------------------------------------------
        */
        $latestBalance = UserLeaveBalance::query()
            ->where('user_id', $user->id)
            ->orderByDesc('valid_to')
            ->first();


        /*
        * --------------------------------------------------------------------------
        * If an existing leave period exists, determine the next period.
        * --------------------------------------------------------------------------
        */
        if ($latestBalance) {

            $latestValidFrom = Carbon::parse(
                $latestBalance->valid_from
            );

            $latestValidTo = Carbon::parse(
                $latestBalance->valid_to
            );


            /*
            * New period starts immediately after the latest period.
            */
            $defaultValidFrom = $latestValidTo
                ->copy()
                ->addDay();


            /*
            * Calculate the number of months in the existing period.
            *
            * Example:
            *
            * 01-Jan-2026 → 31-Dec-2026 = 12 months
            *
            * 01-Apr-2026 → 31-Mar-2027 = 12 months
            */
            $periodMonths = $latestValidFrom
                ->diffInMonths($latestValidTo) + 1;


            /*
            * Create the next period using the same month duration.
            */
            $defaultValidTo = $defaultValidFrom
                ->copy()
                ->addMonthsNoOverflow($periodMonths)
                ->subDay();
        }


        /*
        * --------------------------------------------------------------------------
        * EDIT EXISTING LEAVE YEAR
        * --------------------------------------------------------------------------
        */
        if ($editYear) {

            /*
            * Get current year's leave balances.
            */
            $editBalances = UserLeaveBalance::query()
                ->with('leaveType')
                ->where('user_id', $user->id)
                ->where('year', $editYear)
                ->orderBy('leave_type_id')
                ->get();


            /*
            * If no balances exist for the selected year,
            * redirect back with an error.
            */
            if ($editBalances->isEmpty()) {

                return redirect()
                    ->route(
                        'users.leave-details',
                        $user->id
                    )
                    ->with(
                        'error',
                        'The selected leave entitlement period was not found.'
                    );
            }


            /*
            * Do not show Add New Year modal while editing.
            */
            $showLeaveAssignmentModal = false;


            /*
            * Get the current period start date.
            */
            $currentValidFrom = $editBalances
                ->first()
                ->valid_from;


            /*
            * Find the immediately previous entitlement period.
            */
            $previousValidTo = UserLeaveBalance::query()
                ->where('user_id', $user->id)
                ->whereDate(
                    'valid_to',
                    '<',
                    $currentValidFrom
                )
                ->max('valid_to');


            /*
            * Load the previous period balances.
            *
            * We intentionally get balances even when current_balance = 0
            * so that the previous year's details can still be displayed.
            */
            if ($previousValidTo) {

                $previousBalances = UserLeaveBalance::query()
                    ->with('leaveType')
                    ->where('user_id', $user->id)
                    ->whereDate(
                        'valid_to',
                        $previousValidTo
                    )
                    ->orderBy('leave_type_id')
                    ->get();
            }
        }


        /*
        * --------------------------------------------------------------------------
        * ADD NEW LEAVE YEAR
        * --------------------------------------------------------------------------
        */
        if ($showLeaveAssignmentModal && !$editYear) {

            /*
            * Find the previous entitlement period.
            *
            * This is the period immediately before the newly generated period.
            */
            $previousValidTo = UserLeaveBalance::query()
                ->where('user_id', $user->id)
                ->whereDate(
                    'valid_to',
                    '<',
                    $defaultValidFrom->toDateString()
                )
                ->max('valid_to');


            /*
            * Get balances from the previous entitlement period.
            *
            * Only balances greater than zero are shown as
            * available for carry forward.
            */
            if ($previousValidTo) {

                $previousBalances = UserLeaveBalance::query()
                    ->with('leaveType')
                    ->where('user_id', $user->id)
                    ->whereDate(
                        'valid_to',
                        $previousValidTo
                    )
                    ->where('current_balance', '>', 0)
                    ->orderBy('leave_type_id')
                    ->get();
            }
        }


        /*
        * --------------------------------------------------------------------------
        * Return view.
        * --------------------------------------------------------------------------
        */
        return view('users.leave-details', [
            'user' => $user,

            'leaveBalances' => $leaveBalances,

            'leavePeriods' => $leavePeriods,

            'leaveTypes' => $leaveTypes,

            'showLeaveAssignmentModal' => $showLeaveAssignmentModal,

            'editYear' => $editYear,

            'editBalances' => $editBalances,

            /*
            * Previous year's balances.
            * Used in both create and edit modes.
            */
            'previousBalances' => $previousBalances,

            /*
            * Defaults for Add New Leave Year.
            */
            'defaultValidFrom' => $defaultValidFrom,

            'defaultValidTo' => $defaultValidTo,
        ]);
    }

    public function storeLeaveAssignment(Request $request, User $user)
    {
        $validated = $request->validate([
            'valid_from' => [
                'required',
                'date',
            ],

            'valid_to' => [
                'required',
                'date',
                'after_or_equal:valid_from',
            ],

            'leave_balances' => [
                'required',
                'array',
            ],

            'leave_balances.*.leave_type_id' => [
                'required',
                'exists:leave_types,id',
            ],

            'leave_balances.*.yearly_entitlement' => [
                'required',
                'numeric',
                'min:0',
            ],

            'leave_balances.*.monthly_entitlement' => [
                'required',
                'numeric',
                'min:0',
            ],

            'leave_balances.*.opening_balance' => [
                'required',
                'numeric',
                'min:0',
            ],

            /*
            * Carry-forward selection.
            */
            'carry_forward' => [
                'nullable',
                'in:yes,no',
            ],

            'carry_forward_items' => [
                'nullable',
                'array',
            ],

            'carry_forward_items.*.selected' => [
                'nullable',
                'boolean',
            ],

            'carry_forward_items.*.amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $validFrom = Carbon::parse($validated['valid_from']);
        $validTo = Carbon::parse($validated['valid_to']);

        /*
        * The entitlement year is based on the
        * starting year of the period.
        *
        * Example:
        * 01-Apr-2027 → 31-Mar-2028
        * year = 2027
        */
        $year = $validFrom->year;


        /*
        * Validate each leave entitlement.
        */
        foreach ($validated['leave_balances'] as $balance) {

            $yearlyEntitlement = (float) $balance['yearly_entitlement'];
            $monthlyEntitlement = (float) $balance['monthly_entitlement'];
            $openingBalance = (float) $balance['opening_balance'];

            /*
            * Monthly entitlement cannot exceed yearly entitlement.
            */
            if ($monthlyEntitlement > $yearlyEntitlement) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Monthly entitlement cannot be greater than yearly entitlement.'
                    );
            }

            /*
            * Opening balance cannot exceed yearly entitlement.
            */
            if ($openingBalance > $yearlyEntitlement) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Opening balance cannot be greater than yearly entitlement.'
                    );
            }
        }


        /*
        * Check whether the user already has an overlapping
        * entitlement period.
        *
        * Example:
        * Existing: 01-Jan-2027 → 31-Dec-2027
        * New:      01-Jun-2027 → 31-May-2028
        *
        * This should not be allowed.
        */
        $overlappingPeriod = UserLeaveBalance::query()
            ->where('user_id', $user->id)
            ->whereDate(
                'valid_from',
                '<=',
                $validTo->toDateString()
            )
            ->whereDate(
                'valid_to',
                '>=',
                $validFrom->toDateString()
            )
            ->exists();

        if ($overlappingPeriod) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'A leave entitlement already exists for this user within the selected date range. Please edit the existing entitlement or choose a different period.'
                );
        }


        /*
        * Find the immediately previous entitlement period.
        *
        * We look for the latest valid_to date before
        * the new period starts.
        */
        $previousValidTo = UserLeaveBalance::query()
            ->where('user_id', $user->id)
            ->whereDate(
                'valid_to',
                '<',
                $validFrom->toDateString()
            )
            ->max('valid_to');


        /*
        * Load all leave balances belonging to the
        * previous entitlement period.
        */
        $previousBalances = collect();

        if ($previousValidTo) {

            $previousBalances = UserLeaveBalance::query()
                ->where('user_id', $user->id)
                ->whereDate('valid_to', $previousValidTo)
                ->get()
                ->keyBy('leave_type_id');
        }


        /*
        * Prepare carry-forward values.
        */
        $carryForwardItems = [];

        if (
            $request->input('carry_forward') === 'yes' &&
            $previousBalances->isNotEmpty()
        ) {

            foreach (
                $request->input('carry_forward_items', [])
                as $leaveTypeId => $item
            ) {

                /*
                * Ignore unselected rows.
                */
                if (
                    empty($item['selected']) ||
                    (float) ($item['amount'] ?? 0) <= 0
                ) {
                    continue;
                }

                $carryForwardAmount = (float) $item['amount'];

                /*
                * Find previous balance for this leave type.
                */
                $previousBalance = $previousBalances->get(
                    (int) $leaveTypeId
                );

                if (!$previousBalance) {
                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            'Invalid carry-forward leave type selected.'
                        );
                }

                $previousCurrentBalance =
                    (float) $previousBalance->current_balance;

                /*
                * Never allow carrying forward more than
                * the previous year's remaining balance.
                */
                if ($carryForwardAmount > $previousCurrentBalance) {

                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            'The carry-forward amount for ' .
                            ($previousBalance->leaveType->name ?? 'the selected leave type') .
                            ' cannot be greater than the previous remaining balance of ' .
                            number_format($previousCurrentBalance, 2) .
                            ' days.'
                        );
                }

                $carryForwardItems[(int) $leaveTypeId] =
                    $carryForwardAmount;
            }
        }


        /*
        * Save everything in one transaction.
        */
        DB::transaction(function () use (
            $validated,
            $user,
            $year,
            $validFrom,
            $validTo,
            $carryForwardItems
        ) {

            foreach ($validated['leave_balances'] as $balance) {

                $leaveTypeId = (int) $balance['leave_type_id'];

                $openingBalance =
                    (float) $balance['opening_balance'];

                /*
                * Carry-forward for this particular leave type.
                *
                * If nothing was selected, it will be 0.
                */
                $carryForwardBalance =
                    (float) ($carryForwardItems[$leaveTypeId] ?? 0);

                /*
                * New current balance:
                *
                * Opening Balance
                * +
                * Carry Forward
                */
                $currentBalance = $openingBalance + $carryForwardBalance;

                $isCarryForward = $carryForwardBalance > 0;

                UserLeaveBalance::create([
                    'user_id' => $user->id,

                    'leave_type_id' => $leaveTypeId,

                    'year' => $year,

                    'valid_from' => $validFrom->toDateString(),
                    'valid_to' => $validTo->toDateString(),

                    'yearly_entitlement' => $balance['yearly_entitlement'],
                    'monthly_entitlement' => $balance['monthly_entitlement'],

                    /*
                    * New year's own allocation.
                    */
                    'opening_balance' => $openingBalance,

                    /*
                    * Previous year's carried balance.
                    */
                    'carry_forward_balance' => $carryForwardBalance,

                    'is_carry_forward' => $isCarryForward,


                    /*
                    * Combined available balance.
                    */
                    'current_balance' => $currentBalance,

                    /*
                    * No leave has been used in the new period yet.
                    */
                    'used_balance' => 0,

                    'paid_days_used' => 0,
                    'unpaid_days_used' => 0,

                    'status' => true,

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });


        /*
        * Return to Leave Details if this was opened
        * from the user's Leave Details page.
        */
        if ($request->input('return_to') === 'leave_details') {

            return redirect()
                ->route(
                    'users.leave-details',
                    $user->id
                )
                ->with(
                    'success',
                    'Leave assignment saved successfully.'
                );
        }


        /*
        * Initial leave assignment after user creation.
        */
        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Leave assignment saved successfully for ' .
                $user->name .
                '.'
            );
    }


 public function updateLeaveAssignment(
    Request $request,
    User $user,
    int $year
) {
    $validated = $request->validate([
        'valid_from' => [
            'required',
            'date',
        ],

        'valid_to' => [
            'required',
            'date',
            'after_or_equal:valid_from',
        ],

        'leave_balances' => [
            'required',
            'array',
        ],

        'leave_balances.*.leave_type_id' => [
            'required',
            'exists:leave_types,id',
        ],

        'leave_balances.*.yearly_entitlement' => [
            'required',
            'numeric',
            'min:0',
        ],

        'leave_balances.*.monthly_entitlement' => [
            'required',
            'numeric',
            'min:0',
        ],

        'leave_balances.*.opening_balance' => [
            'required',
            'numeric',
            'min:0',
        ],

        'leave_balances.*.carry_forward_balance' => [
            'nullable',
            'numeric',
            'min:0',
        ],
    ]);

    $validFrom = Carbon::parse($validated['valid_from']);
    $validTo = Carbon::parse($validated['valid_to']);

    /*
     * The existing entitlement year must not be changed
     * through the Edit screen.
     */
    if ($validFrom->year !== (int) $year) {
        return back()
            ->withInput()
            ->with(
                'year_change_warning',
                "You are trying to change the entitlement year from {$year} to {$validFrom->year}. To create a new year's leave entitlement, close this form and use the \"Add New Leave Year\" option."
            );
    }

    /*
     * Get all existing balances for this year.
     */
    $existingBalances = UserLeaveBalance::query()
        ->where('user_id', $user->id)
        ->where('year', $year)
        ->get()
        ->keyBy('leave_type_id');

    if ($existingBalances->isEmpty()) {
        return redirect()
            ->route('users.leave-details', $user->id)
            ->with(
                'error',
                'The selected leave entitlement period was not found.'
            );
    }

    /*
     * Find the previous entitlement period.
     */
    $previousValidTo = UserLeaveBalance::query()
        ->where('user_id', $user->id)
        ->whereDate('valid_to', '<', $validFrom->toDateString())
        ->max('valid_to');

    $previousBalances = collect();

    if ($previousValidTo) {
        $previousBalances = UserLeaveBalance::query()
            ->where('user_id', $user->id)
            ->whereDate('valid_to', $previousValidTo)
            ->get()
            ->keyBy('leave_type_id');
    }

    /*
     * Validate each leave type.
     */
    foreach ($validated['leave_balances'] as $balanceData) {

        $yearlyEntitlement =
            (float) $balanceData['yearly_entitlement'];

        $monthlyEntitlement =
            (float) $balanceData['monthly_entitlement'];

        $openingBalance =
            (float) $balanceData['opening_balance'];

        $carryForwardBalance =
            (float) ($balanceData['carry_forward_balance'] ?? 0);


        /*
         * Monthly cannot exceed yearly.
         */
        if ($monthlyEntitlement > $yearlyEntitlement) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Monthly entitlement cannot be greater than yearly entitlement.'
                );
        }


        /*
         * Opening cannot exceed yearly.
         */
        if ($openingBalance > $yearlyEntitlement) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Opening balance cannot be greater than yearly entitlement.'
                );
        }


        /*
         * Carry forward must come from the previous period.
         */
        if ($carryForwardBalance > 0) {

            $previousBalance =
                $previousBalances->get(
                    (int) $balanceData['leave_type_id']
                );

            $previousAvailable =
                $previousBalance
                    ? (float) $previousBalance->current_balance
                    : 0;

            if ($carryForwardBalance > $previousAvailable) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Carry forward balance cannot be greater than the previous year available balance.'
                    );
            }
        }
    }


    /*
     * Check whether the edited date range overlaps another
     * entitlement period for this user.
     */
    $overlappingPeriod = UserLeaveBalance::query()
        ->where('user_id', $user->id)
        ->where('year', '!=', $year)
        ->whereDate(
            'valid_from',
            '<=',
            $validTo->toDateString()
        )
        ->whereDate(
            'valid_to',
            '>=',
            $validFrom->toDateString()
        )
        ->exists();

    if ($overlappingPeriod) {

        return back()
            ->withInput()
            ->with(
                'error',
                'The selected date range overlaps another leave entitlement period for this user.'
            );
    }


    /*
     * Save all changes in one transaction.
     */
    DB::transaction(function () use (
        $validated,
        $user,
        $year,
        $validFrom,
        $validTo,
        $existingBalances
    ) {

        foreach ($validated['leave_balances'] as $balanceData) {

            $leaveTypeId =
                (int) $balanceData['leave_type_id'];

            /*
             * Find the current record.
             */
            $balance =
                $existingBalances->get($leaveTypeId);


            /*
             * If the leave type did not exist in the
             * original period, create it.
             */
            if (!$balance) {

                $openingBalance =
                    (float) $balanceData['opening_balance'];

                $carryForwardBalance =
                    (float) (
                        $balanceData['carry_forward_balance'] ?? 0
                    );

                $isCarryForward =
                    $carryForwardBalance > 0;

                UserLeaveBalance::create([
                    'user_id' => $user->id,

                    'leave_type_id' => $leaveTypeId,

                    'year' => $year,

                    'valid_from' =>
                        $validFrom->toDateString(),

                    'valid_to' =>
                        $validTo->toDateString(),

                    'yearly_entitlement' =>
                        $balanceData['yearly_entitlement'],

                    'monthly_entitlement' =>
                        $balanceData['monthly_entitlement'],

                    'opening_balance' =>
                        $openingBalance,

                    'carry_forward_balance' =>
                        $carryForwardBalance,

                    'is_carry_forward' =>
                        $isCarryForward,

                    'current_balance' =>
                        $openingBalance +
                        $carryForwardBalance,

                    'used_balance' => 0,

                    'paid_days_used' => 0,

                    'unpaid_days_used' => 0,

                    'status' => true,

                    'created_by' => auth()->id(),

                    'updated_by' => auth()->id(),
                ]);

                continue;
            }


            /*
             * Read the existing used balance.
             */
            $usedBalance =
                (float) $balance->used_balance;


            /*
             * Read the new values from the form.
             */
            $openingBalance =
                (float) $balanceData['opening_balance'];

            $carryForwardBalance =
                (float) (
                    $balanceData['carry_forward_balance'] ?? 0
                );


            /*
             * Determine whether carry forward is enabled.
             */
            $isCarryForward =
                $carryForwardBalance > 0;


            /*
             * Base update.
             */
            $updateData = [

                'valid_from' =>
                    $validFrom->toDateString(),

                'valid_to' =>
                    $validTo->toDateString(),

                'yearly_entitlement' =>
                    $balanceData['yearly_entitlement'],

                'monthly_entitlement' =>
                    $balanceData['monthly_entitlement'],

                /*
                 * Carry-forward must always be updated
                 * from the submitted value.
                 */
                'carry_forward_balance' =>
                    $carryForwardBalance,

                'is_carry_forward' =>
                    $isCarryForward,

                'updated_by' =>
                    auth()->id(),
            ];


            /*
             * If no leave has been used yet, the opening
             * balance and current balance can be changed.
             */
            if ($usedBalance == 0) {

                $updateData['opening_balance'] =
                    $openingBalance;

                $updateData['current_balance'] =
                    $openingBalance +
                    $carryForwardBalance;
            }

            /*
             * If leave has already been used:
             *
             * Don't overwrite the current balance.
             *
             * The carry-forward flag/value is still preserved,
             * but current balance remains based on the existing
             * leave usage.
             */
            else {

                $updateData['current_balance'] =
                    $openingBalance +
                    $carryForwardBalance -
                    $usedBalance;
            }


            $balance->update($updateData);
        }
    });


    return redirect()
        ->route(
            'users.leave-details',
            $user->id
        )
        ->with(
            'success',
            'Leave assignment updated successfully.'
        );
}
    public function destroyLeaveAssignment(User $user, int $year)
    {
        $leaveBalances = UserLeaveBalance::query()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->get();

        if ($leaveBalances->isEmpty()) {
            return redirect()
                ->route('users.leave-details', $user->id)
                ->with('error', 'The selected leave assignment was not found.');
        }

        /*
        * Do not allow deletion if any leave has already been used.
        */
        $hasUsedLeave = $leaveBalances->contains(function ($balance) {
            return (float) $balance->used_balance > 0;
        });

        if ($hasUsedLeave) {
            return redirect()
                ->route('users.leave-details', $user->id)
                ->with(
                    'error',
                    'This leave assignment cannot be deleted because leave has already been used in this period.'
                );
        }

        /*
        * Delete all leave type balances for this period.
        */
        DB::transaction(function () use ($leaveBalances) {
            foreach ($leaveBalances as $balance) {
                $balance->delete();
            }
        });

        return redirect()
            ->route('users.leave-details', $user->id)
            ->with(
                'success',
                "{$year} leave assignment deleted successfully."
            );
    }
}
