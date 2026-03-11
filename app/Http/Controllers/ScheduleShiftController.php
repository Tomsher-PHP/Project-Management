<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleShiftRequest;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\ScheduleShiftService;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $startOfWeek = Carbon::parse($request->week ?? now())->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SATURDAY);

        $weekDates = collect();

        for ($i = 0; $i < 7; $i++) {
            $weekDates->push($startOfWeek->copy()->addDays($i));
        }

        $users = User::where('user_type', '!=', 'super_admin')->whereStatus(1)->orderBy('name')->get();
        $shifts = Shift::whereStatus(1)->get();

        $assignments = UserShiftAssignment::where(function ($query) use ($startOfWeek, $endOfWeek) {

            $query->where('date_from', '<=', $endOfWeek)
                ->where(function ($q) use ($startOfWeek) {
                    $q->where('date_to', '>=', $startOfWeek)
                        ->orWhereNull('date_to');
                });
        })->get();

        $calendar = [];

        foreach ($assignments as $assignment) {

            $start = Carbon::parse($assignment->date_from);
            $end = $assignment->date_to ? Carbon::parse($assignment->date_to) : $endOfWeek;

            // Clamp range to current week
            if ($start->lt($startOfWeek)) {
                $start = $startOfWeek->copy();
            }

            if ($end->gt($endOfWeek)) {
                $end = $endOfWeek->copy();
            }

            $current = $start->copy();


            while ($current <= $end) {
                $calendar[$assignment->user_id][$current->toDateString()] = $assignment;
                $current->addDay();
            }
        }

        // ⭐ IMPORTANT PART
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
            ]);
        }

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
