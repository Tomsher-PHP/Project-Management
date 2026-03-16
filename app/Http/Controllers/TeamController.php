<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{

    protected $pageTitle;

    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Team Management';
        $this->subTitle = 'Keep your team organized and secure';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $teams = Team::filter($request->all())
            ->with([
                'users',
                'primaryAttachment'
            ])->paginate($perPage)->withQueryString();

        return view('teams.index', compact('teams', 'perPage'));
    }

    public function create()
    {
        // Get users for team members
        $users = User::where('is_super_admin', false)->where('status', true)->get();

        $teamRoles = config('constants.team_roles');

        return view('teams.create', compact('users', 'teamRoles'));
    }

    public function store(TeamRequest $request, TeamService $service)
    {
        $service->createTeam($request->validated());

        return redirect()->route('teams.index')
            ->with('success', 'Team created successfully.');
    }

    public function edit(int $id)
    {
        $team = Team::findOrFail($id);
        $teamUsers = $team->users()->get();

        $teamUsersIds = $teamUsers->pluck('id')->toArray();

        // Get users for team members
        $users = User::where('is_super_admin', false)->whereNotIn('id', $teamUsersIds)->where('status', true)->get();

        $teamRoles = config('constants.team_roles');


        return view('teams.edit', compact('team', 'teamUsers', 'users', 'teamRoles'));
    }

    public function update(TeamRequest $request, Team $team, TeamService $service)
    {
        $service->updateTeam($team, $request->validated());

        return redirect()->back()->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team, AttachmentService $attachmentService)
    {
        DB::transaction(function () use ($team, $attachmentService) {
            $attachmentService->delete($team->attachments);
            $team->users()->detach();
            $team->forceDelete();
        });

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $team = Team::findOrFail($request->id);
        $team->status = !$team->status;
        $team->save();

        return response()->json([
            'success' => true,
            'status' => $team->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
