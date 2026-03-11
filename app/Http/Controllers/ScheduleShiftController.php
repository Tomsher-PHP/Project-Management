<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleShiftRequest;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\ScheduleShiftService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleShiftController extends Controller
{

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
        // Get start/end of week
        [$startOfWeek, $endOfWeek] = $scheduleService->getWeekRange($request->week);

        // Generate week dates
        $weekDates = $scheduleService->getWeekDates($startOfWeek);

        // Get users and shifts
        [$users, $shifts] = $scheduleService->getUsersAndShifts();

        // Get assignments and build calendar
        $assignments = $scheduleService->getAssignments($startOfWeek, $endOfWeek);
        $calendar = $scheduleService->buildCalendar($users, $assignments, $startOfWeek, $endOfWeek);

        // Handle AJAX request for week navigation
        if ($request->ajax()) {
            $tableHtml = view('schedule-shift.partials.schedule-table', compact(
                'users',
                'shifts',
                'calendar',
                'weekDates'
            ))->render();

            return response()->json([
                'html' => $tableHtml,
                'weekRange' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),
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
            'endOfWeek'
        ));
    }

    public function create()
    {
        $users = User::where('user_type', '!=', 'super_admin')->whereStatus(1)->orderBy('name')->get();
        $shifts = Shift::whereStatus(1)->orderBy('is_default', 'desc')->orderBy('name', 'asc')->get();

        return view('schedule-shift.create', compact('users', 'shifts'));
    }

    public function store(ScheduleShiftRequest $request, ScheduleShiftService $scheduleShiftService)
    {

        $scheduleShiftService->schedule($request->validated());

        return redirect()
            ->route('schedule.shift.index')
            ->with('success', 'Shift scheduled successfully.');
    }

    public function updateShift(Request $request)
    {
        dd('Update shift', $request->all());
        UserShiftAssignment::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'shift_date' => $request->date,
            ],
            [
                'shift_id' => $request->shift_id,
            ]
        );

        return response()->json(['success' => true]);
    }
}
