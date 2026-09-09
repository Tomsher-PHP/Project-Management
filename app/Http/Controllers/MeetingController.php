<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\MeetingStatus;
use App\Models\MeetingTag;
use App\Models\MeetingType;
use App\Models\Project;
use App\Services\MeetingService;
use App\Services\UserService;
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

        $projects = Project::accessibleBy($authUser)->orderBy('name')->get();
        $meetingTypes = MeetingType::active()->orderBy('sort_order')->get();
        $meetingLocations = MeetingLocation::active()->orderBy('sort_order')->get();
        $meetingStatuses = MeetingStatus::active()->orderBy('sort_order')->get();
        $users = $this->userService->getAccessibleUsers($authUser)->values();
        $meetingTags = MeetingTag::active()->orderBy('sort_order')->get();

        $defaultStatusId = MeetingStatus::where('code', MeetingStatus::STATUS_SCHEDULED)->value('id')
            ?? $meetingStatuses->first()?->id;

        return view('meetings.index', compact(
            'meetings',
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
     * Display the specified meeting.
     */
    public function show(Request $request, Meeting $meeting): View|JsonResponse
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => $meeting,
            ]);
        }

        return view('meetings.show', compact('meeting'));
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
