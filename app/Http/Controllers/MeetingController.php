<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use Carbon\Carbon;
use App\Models\MeetingStatus;
use App\Models\MeetingTag;
use App\Models\MeetingType;
use App\Models\Project;
use App\Services\MeetingService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    protected MeetingService $meetingService;
    protected UserService $userService;

    public function __construct(MeetingService $meetingService, UserService $userService)
    {
        $this->meetingService = $meetingService;
        $this->userService = $userService;
        view()->share(['pageTitle' => "Meetings"]);
    }

    /**
     * Display a listing of meetings.
     */
    public function index(Request $request): View|JsonResponse
    {
        $authUser = auth()->user();

        $meetings = $this->meetingService->list($request->all(), $authUser);

        if ($request->ajax() && ! $request->pjax()) {
            return response()->json([
                'status' => true,
                'data' => $meetings,
            ]);
        }

        $projects = [];
        $meetingTypes = MeetingType::active()->orderBy('sort_order')->get();
        $meetingLocations = MeetingLocation::active()->orderBy('sort_order')->get();
        $meetingStatuses = MeetingStatus::active()->orderBy('sort_order')->get();
        $users = $this->userService->getAccessibleUsers($authUser)->values();
        $meetingTags = MeetingTag::active()->orderBy('sort_order')->get();

        $defaultStatusId = $meetingStatuses->firstWhere('is_default', true)?->id;

        $selectedDate = $request->filled('calendar_date')
            ? Carbon::parse($request->calendar_date)
            : today();

        $calendarStart = $selectedDate->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $selectedDate->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $totalDays = (int) $calendarStart->diffInDays($calendarEnd) + 1;

        $calendarMeetings = Meeting::query()
            ->with([
                'project:id,name,project_code',
                'meetingType:id,name,color',
                'meetingLocation:id,name',
                'meetingStatus:id,name,code,color,type',
                'organizer:id,name,email',
                'tags:id,name,color',
            ])
            ->when($authUser, fn(Builder $q) => $q->accessibleBy($authUser))
            ->when(! empty($request->input('project_id')), fn(Builder $q) => $q->where('project_id', $request->input('project_id')))
            ->when(! empty($request->input('meeting_type_id')), fn(Builder $q) => $q->where('meeting_type_id', $request->input('meeting_type_id')))
            ->when(! empty($request->input('meeting_location_id')), fn(Builder $q) => $q->where('meeting_location_id', $request->input('meeting_location_id')))
            ->when(! empty($request->input('meeting_status_id')), fn(Builder $q) => $q->where('meeting_status_id', $request->input('meeting_status_id')))
            ->when(! empty($request->input('organizer_id')), fn(Builder $q) => $q->where('organizer_id', $request->input('organizer_id')))
            ->when(! empty($request->input('search')), function (Builder $q) use ($request) {
                $search = $request->input('search');
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location_details', 'like', "%{$search}%");
                });
            })
            ->whereDate('end_at', '>=', $calendarStart->toDateString())
            ->whereDate('start_at', '<=', $calendarEnd->toDateString())
            ->orderBy('start_at', 'asc')
            ->get();

        $meetingsByDate = collect();
        foreach ($calendarMeetings as $m) {
            $startDate = $m->start_at->copy()->startOfDay();
            $endDate = $m->end_at->copy()->startOfDay();
            $curr = $startDate->copy();
            while ($curr->lessThanOrEqualTo($endDate)) {
                $dateKey = $curr->format('Y-m-d');
                if (! $meetingsByDate->has($dateKey)) {
                    $meetingsByDate->put($dateKey, collect());
                }
                $meetingsByDate->get($dateKey)->push($m);
                $curr->addDay();
            }
        }

        $calendarMeetingsForJs = [];
        foreach ($meetingsByDate as $dateKey => $mCollection) {
            $sortedCollection = $mCollection->sortBy('start_at')->values();
            $meetingsByDate->put($dateKey, $sortedCollection);

            $calendarMeetingsForJs[$dateKey] = $sortedCollection->map(function ($m) {
                $color = $m->meetingType?->color ?: ($m->meetingStatus?->color ?: '#3B82F6');
                return [
                    'id' => $m->id,
                    'title' => $m->title,
                    'organizer' => $m->organizer?->name ?? 'N/A',
                    'type' => $m->meetingType?->name ?? 'Meeting',
                    'status' => $m->meetingStatus?->name ?? 'Scheduled',
                    'color' => $color,
                    'start' => $m->start_at->format('Y-m-d H:i'),
                    'end' => $m->end_at->format('Y-m-d H:i'),
                    'time_range' => $m->start_at->format('H:i') . ' - ' . $m->end_at->format('H:i'),
                    'edit_url' => route('meetings.edit', $m->id),
                    'update_url' => route('meetings.update', $m->id),
                ];
            })->values()->all();
        }

        return view('meetings.index', compact(
            'meetings',
            'selectedDate',
            'calendarStart',
            'calendarEnd',
            'totalDays',
            'meetingsByDate',
            'calendarMeetingsForJs',
            'projects',
            'meetingTypes',
            'meetingLocations',
            'meetingStatuses',
            'users',
            'meetingTags',
            'defaultStatusId'
        ));
    }

    /**
     * Get calendar events JSON data for FullCalendar.
     */
    public function calendarData(Request $request): JsonResponse
    {
        $authUser = auth()->user();
        $start = $request->input('start');
        $end = $request->input('end');

        $events = $this->meetingService->getCalendarEvents($request->all(), $authUser, $start, $end);

        return response()->json($events);
    }

    /**
     * Store a newly created meeting.
     */
    public function store(MeetingRequest $request): JsonResponse|RedirectResponse
    {
        $meeting = $this->meetingService->create($request->validated(), $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Meeting created successfully.',
                'data' => $meeting,
            ]);
        }

        return redirect()->route('meetings.index')->with('success', 'Meeting created successfully.');
    }

    /**
     * Show the form for editing the specified meeting (AJAX modal data endpoint).
     */
    public function edit(Meeting $meeting): JsonResponse
    {
        $meeting->load([
            'project',
            'meetingType',
            'meetingLocation',
            'meetingStatus',
            'organizer',
            'participants.user',
            'tags',
        ]);

        return response()->json([
            'status' => true,
            'data' => $meeting,
        ]);
    }

    /**
     * Update the specified meeting.
     */
    public function update(MeetingRequest $request, Meeting $meeting): JsonResponse|RedirectResponse
    {
        $updatedMeeting = $this->meetingService->update($meeting, $request->validated(), $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Meeting updated successfully.',
                'data' => $updatedMeeting,
            ]);
        }

        return redirect()->route('meetings.index')->with('success', 'Meeting updated successfully.');
    }

    /**
     * Remove the specified meeting from storage.
     */
    public function destroy(Request $request, Meeting $meeting): JsonResponse|RedirectResponse
    {
        $this->meetingService->delete($meeting);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Meeting deleted successfully.',
            ]);
        }

        return redirect()->route('meetings.index')->with('success', 'Meeting deleted successfully.');
    }
}
