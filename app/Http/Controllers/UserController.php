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
use App\Services\ScheduleShiftService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'User Management';
        $this->subTitle = 'Keep your team organized and secure';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
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

        return redirect()->route('users.index')
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

        $managers = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $managerIds);
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
        // dd($request->all());
        $service->updateUser($user, $request->validated());

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
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

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->is_active = ! $user->is_active;
        $user->save();

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
        ]);

        $userNotificationSettings = config('notification_settings');

        $generalSettings = $user->generalSettings;

        return view('users.show', compact('user', 'userNotificationSettings', 'generalSettings'));
    }

    public function updateNotificationSettings(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|string',
            'field' => 'required|in:in_app,mail',
            'value' => 'required|boolean',
        ]);

        $setting = UserNotificationSetting::firstOrCreate([
            'user_id' => $request->user_id,
            'action' => $request->action,
        ]);

        $setting->{$request->field} = $request->value;
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully'
        ], Response::HTTP_OK);
    }

    public function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'field' => 'required|in:kanban_view,theme',
            'value' => 'required|string'
        ]);

        UserGeneralSetting::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                $request->field => $request->value
            ]
        );

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
}
