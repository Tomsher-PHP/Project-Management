<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleShiftRequest;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\ScheduleShiftService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleShiftController extends Controller
{
    private const TEAM_FILTER_NOT_IN_TEAM = 'not_in_team';

    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Schedule Shift';
        $this->subTitle = 'Schedule shift subtitle here...';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request, ScheduleShiftService $scheduleService)
    {
        $selectedTeamFilter = $request->input('team_id');
        $selectedTeamFilter = filled($selectedTeamFilter) ? (string) $selectedTeamFilter : null;
        $notInTeamFilter = self::TEAM_FILTER_NOT_IN_TEAM;
        $perPage = max((int) $request->input('per_page', config('constants.per_page_count')), 1);
        $page = max((int) $request->input('page', 1), 1);
        $todayDate = now(config('constants.timezone'))->toDateString();

        // Get start/end of week
        [$startOfWeek, $endOfWeek] = $scheduleService->getWeekRange($request->week);

        // Generate week dates
        $weekDates = $scheduleService->getWeekDates($startOfWeek);

        // Get users and shifts
        [$allUsers, $shifts] = $scheduleService->getUsersAndShifts($selectedTeamFilter);
        $users = new LengthAwarePaginator(
            $allUsers->forPage($page, $perPage)->values(),
            $allUsers->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $teams = Team::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get assignments and build calendar
        $assignments = $scheduleService->getAssignments($startOfWeek, $endOfWeek, $users->getCollection()->pluck('id')->all());
        $calendar = $scheduleService->buildCalendar($users, $assignments, $startOfWeek, $endOfWeek);

        // Handle AJAX request for week navigation
        if ($request->ajax()) {
            $tableHtml = view('schedule-shift.partials.schedule-table', compact(
                'users',
                'shifts',
                'calendar',
                'weekDates',
                'perPage',
                'todayDate'
            ))->render();

            $dateFormat = config('constants.date_format');

            return response()->json([
                'html' => $tableHtml,
                'weekRange' => $startOfWeek->copy()->format($dateFormat)
                    . ' - ' .
                    $endOfWeek->copy()->format($dateFormat),
            ], Response::HTTP_OK);
        }

        // Normal page load
        return view('schedule-shift.index', compact(
            'users',
            'shifts',
            'assignments',
            'calendar',
            'weekDates',
            'startOfWeek',
            'endOfWeek',
            'teams',
            'selectedTeamFilter',
            'notInTeamFilter',
            'perPage',
            'todayDate'
        ));
    }

    public function create()
    {
        $users = app(UserService::class)->getAccessibleUsers(auth()->user());
        $shifts = Shift::active()->orderBy('is_default', 'desc')->orderBy('name', 'asc')->get();

        return view('schedule-shift.create', compact('users', 'shifts'));
    }

    public function store(ScheduleShiftRequest $request, ScheduleShiftService $scheduleShiftService)
    {
        $scheduleShiftService->schedule($request->validated());

        return redirect()
            ->route('schedule.shift.index')
            ->with('success', 'Shift scheduled successfully.');
    }

    public function updateSchedule(ScheduleShiftRequest $request, ScheduleShiftService $scheduleShiftService)
    {
        $scheduleShiftService->updateUserShift(
            $request->users[0],
            $request->date_from,
            $request->date_to,
            $request->shift_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Shift scheduled successfully.'
        ]);
    }

    public function preview(ScheduleShiftRequest $request)
    {
        $data = $request->validated();

        $dateFrom = Carbon::parse($data['date_from']);
        $dateTo = $data['date_to'] ? Carbon::parse($data['date_to']) : null;

        $assignments = UserShiftAssignment::query() //with(['shift', 'user'])
            ->whereIn('user_id', $data['users'])
            ->where(function ($q) use ($dateFrom, $dateTo) {
                if ($dateTo) {
                    $q->where('date_from', '<=', $dateTo);
                }

                $q->where(function ($q2) use ($dateFrom) {
                    $q2->where('date_to', '>=', $dateFrom)
                        ->orWhereNull('date_to');
                });
            })
            ->orderBy('user_id')
            ->orderBy('date_from')
            ->get()
            ->groupBy('user_id');


        return response()->json([
            'html' => view('schedule-shift.partials._preview', compact('assignments'))->render()
        ]);
    }
}
