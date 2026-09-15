<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Http\Requests\RescheduleMeetingRequest;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use Carbon\Carbon;
use App\Models\MeetingStatus;
use App\Models\MeetingTag;
use App\Models\MeetingType;
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

        $rescheduledStatusId = MeetingStatus::query()
            ->where('code', MeetingStatus::STATUS_RESCHEDULED)
            ->value('id');

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
            ->when(! empty($request->input('project_id')), function (Builder $q) use ($request) {
                $val = $request->input('project_id');
                is_array($val) ? $q->whereIn('project_id', array_filter($val)) : $q->where('project_id', $val);
            })
            ->when(! empty($request->input('meeting_type_id')), function (Builder $q) use ($request) {
                $val = $request->input('meeting_type_id');
                is_array($val) ? $q->whereIn('meeting_type_id', array_filter($val)) : $q->where('meeting_type_id', $val);
            })
            ->when(! empty($request->input('meeting_location_id')), function (Builder $q) use ($request) {
                $val = $request->input('meeting_location_id');
                is_array($val) ? $q->whereIn('meeting_location_id', array_filter($val)) : $q->where('meeting_location_id', $val);
            })
            ->when(! empty($request->input('meeting_status_id')), function (Builder $q) use ($request) {
                $val = $request->input('meeting_status_id');
                is_array($val) ? $q->whereIn('meeting_status_id', array_filter($val)) : $q->where('meeting_status_id', $val);
            })
            ->when(empty($request->input('meeting_status_id')), function (Builder $q) use ($rescheduledStatusId) {
                if ($rescheduledStatusId) {
                    $q->where('meeting_status_id', '!=', $rescheduledStatusId);
                } else {
                    $q->whereDoesntHave('meetingStatus', fn($sq) => $sq->where('code', MeetingStatus::STATUS_RESCHEDULED));
                }
            })
            ->when(! empty($request->input('organizer_id')), function (Builder $q) use ($request) {
                $val = $request->input('organizer_id');
                is_array($val) ? $q->whereIn('organizer_id', array_filter($val)) : $q->where('organizer_id', $val);
            })
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
     * Get rendered day meetings content for modal.
     */
    public function dayMeetings(Request $request): JsonResponse
    {
        $dateStr = $request->input('date');
        if (! $dateStr) {
            return response()->json([
                'status' => false,
                'message' => 'Date parameter is required.',
            ], 400);
        }

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid date format.',
            ], 400);
        }

        $formattedDate = $date->format('d M Y');
        $authUser = auth()->user();

        $rescheduledStatusId = MeetingStatus::query()
            ->where('code', MeetingStatus::STATUS_RESCHEDULED)
            ->value('id');

        $meetings = Meeting::query()
            ->with([
                'project:id,name,project_code',
                'meetingType:id,name,color',
                'meetingLocation:id,name',
                'meetingStatus:id,name,code,color,type',
                'organizer:id,name,email',
            ])
            ->when($authUser, fn(Builder $q) => $q->accessibleBy($authUser))
            ->when($rescheduledStatusId, function (Builder $q) use ($rescheduledStatusId) {
                $q->where('meeting_status_id', '!=', $rescheduledStatusId);
            }, function (Builder $q) {
                $q->whereDoesntHave('meetingStatus', fn($sq) => $sq->where('code', MeetingStatus::STATUS_RESCHEDULED));
            })
            ->whereDate('start_at', '<=', $date->toDateString())
            ->whereDate('end_at', '>=', $date->toDateString())
            ->orderBy('start_at', 'asc')
            ->get();

        $html = view('meetings.partials.day-meetings-modal-content', [
            'meetings' => $meetings,
            'dateKey' => $date->format('Y-m-d'),
        ])->render();

        return response()->json([
            'status' => true,
            'title' => 'Meetings - ' . $formattedDate,
            'html' => $html,
        ]);
    }

    /**
     * Store a newly created meeting.
     */
    public function store(MeetingRequest $request): JsonResponse|RedirectResponse
    {
        $meeting = $this->meetingService->create($request->validated(), $request->user(), $request->allFiles());

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
     * Reschedule an existing meeting.
     */
    public function reschedule(RescheduleMeetingRequest $request, Meeting $meeting): JsonResponse|RedirectResponse
    {
        $authUser = auth()->user();

        if (! Meeting::query()->where('id', $meeting->id)->accessibleBy($authUser)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to edit this meeting.',
            ], 403);
        }

        if (! $meeting->canBeRescheduled()) {
            return response()->json([
                'status' => false,
                'message' => 'This meeting cannot be rescheduled because its current status does not allow rescheduling.',
            ], 422);
        }

        try {
            $validated = $request->validated();
            $validated['participants'] = $request->input('participants', []);

            $newMeeting = $this->meetingService->reschedule(
                $meeting,
                $validated,
                $request->user(),
                $request->allFiles()
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Meeting rescheduled successfully.',
                    'data' => $newMeeting,
                    'original_meeting_id' => $meeting->id,
                ]);
            }

            return redirect()->route('meetings.index')->with('success', 'Meeting rescheduled successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            logger()->error('Meeting reschedule failed: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to reschedule meeting. Please try again.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to reschedule meeting.');
        }
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
            'attachments',
        ]);

        $data = $meeting->toArray();
        $data['attachments'] = $meeting->attachments->map(function ($att) use ($meeting) {
            return [
                'id' => $att->id,
                'original_name' => $att->original_name,
                'file_size' => $att->file_size,
                'file_type' => $att->file_type,
                'url' => $att->url,
                'delete_url' => route('meetings.attachments.delete', ['meeting' => $meeting->id, 'attachment' => $att->id]),
            ];
        })->values()->all();

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update the specified meeting.
     */
    public function update(MeetingRequest $request, Meeting $meeting): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $validated['participants'] = $request->input('participants', []);

        $updatedMeeting = $this->meetingService->update($meeting, $validated, $request->user(), $request->allFiles());

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
        if ($meeting->start_at && $meeting->start_at->isPast()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only future meetings can be deleted.',
                ], 422);
            }

            return redirect()->route('meetings.index')->with('error', 'Only future meetings can be deleted.');
        }

        $this->meetingService->delete($meeting, $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Meeting deleted successfully.',
            ]);
        }

        return redirect()->route('meetings.index')->with('success', 'Meeting deleted successfully.');
    }

    /**
     * Remove an attachment from a meeting.
     */
    public function deleteAttachment(Meeting $meeting, Attachment $attachment): JsonResponse
    {
        $deleted = $this->meetingService->deleteAttachment($meeting, $attachment);

        if ($deleted) {
            return response()->json([
                'status' => true,
                'message' => 'Attachment removed successfully.',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to remove attachment.',
        ], 400);
    }

    /**
     * Get rendered preview drawer content for a meeting.
     */
    public function preview(Meeting $meeting): JsonResponse
    {
        $authUser = auth()->user();

        if (! Meeting::query()->where('id', $meeting->id)->accessibleBy($authUser)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to view this meeting.',
            ], 403);
        }

        $meeting->load([
            'project',
            'meetingType',
            'meetingLocation',
            'meetingStatus',
            'organizer',
            'addedBy',
            'participants.user',
            'tags',
            'attachments',
            'rescheduledFrom' => function ($q) use ($authUser) {
                $q->accessibleBy($authUser)->with('meetingStatus');
            },
            'rescheduledTo' => function ($q) use ($authUser) {
                $q->accessibleBy($authUser)->with('meetingStatus');
            },
        ]);

        $meetingStatuses = MeetingStatus::active()->notRescheduled()->orderBy('sort_order')->get();

        $html = view('meetings.partials.preview-drawer-content', [
            'meeting' => $meeting,
            'canAddMinutes' => $meeting->canAddMinutes(),
            'meetingStatuses' => $meetingStatuses,
        ])->render();

        return response()->json([
            'status' => true,
            'html' => $html,
        ]);
    }

    /**
     * Update meeting minutes for a meeting.
     */
    public function updateMinutes(Request $request, Meeting $meeting): JsonResponse
    {
        $authUser = auth()->user();

        if (! Meeting::query()->where('id', $meeting->id)->accessibleBy($authUser)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to edit this meeting.',
            ], 403);
        }

        if (! $meeting->canAddMinutes()) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting minutes cannot be added or edited for this meeting.',
            ], 422);
        }

        $validated = $request->validate([
            'minutes' => ['nullable', 'string'],
        ]);

        $meeting->minutes = $validated['minutes'] ?? null;
        $meeting->save();

        $meeting->load([
            'project',
            'meetingType',
            'meetingLocation',
            'meetingStatus',
            'organizer',
            'addedBy',
            'participants.user',
            'tags',
            'attachments',
        ]);

        $meetingStatuses = MeetingStatus::active()->orderBy('sort_order')->get();

        $html = view('meetings.partials.preview-drawer-content', [
            'meeting' => $meeting,
            'canAddMinutes' => $meeting->canAddMinutes(),
            'meetingStatuses' => $meetingStatuses,
        ])->render();

        return response()->json([
            'status' => true,
            'message' => 'Meeting minutes updated successfully.',
            'minutes' => $meeting->minutes,
            'html' => $html,
        ]);
    }

    /**
     * Update status for a meeting via AJAX.
     */
    public function updateStatus(Request $request, Meeting $meeting): JsonResponse
    {
        $authUser = auth()->user();

        if (! Meeting::query()->where('id', $meeting->id)->accessibleBy($authUser)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to edit this meeting.',
            ], 403);
        }

        $request->validate([
            'meeting_status_id' => ['required', 'integer', 'exists:meeting_statuses,id'],
        ]);

        $updatedMeeting = $this->meetingService->update($meeting, [
            'meeting_status_id' => (int) $request->input('meeting_status_id'),
        ], $authUser);

        $updatedMeeting->load([
            'project',
            'meetingType',
            'meetingLocation',
            'meetingStatus',
            'organizer',
            'addedBy',
            'participants.user',
            'tags',
            'attachments',
        ]);

        $meetingStatuses = MeetingStatus::active()->orderBy('sort_order')->get();

        $html = view('meetings.partials.preview-drawer-content', [
            'meeting' => $updatedMeeting,
            'canAddMinutes' => $updatedMeeting->canAddMinutes(),
            'meetingStatuses' => $meetingStatuses,
        ])->render();

        return response()->json([
            'status' => true,
            'message' => 'Meeting status updated successfully.',
            'meeting_status_id' => $updatedMeeting->meeting_status_id,
            'status_name' => $updatedMeeting->meetingStatus?->name,
            'status_color' => $updatedMeeting->meetingStatus?->color ?: '#6B7280',
            'html' => $html,
        ]);
    }
}
