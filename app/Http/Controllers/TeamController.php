<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Models\Team;
use App\Models\TeamUser;
use App\Services\AttachmentService;
use App\Services\TeamService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{

    protected string $pageTitle;
    protected string $subTitle;

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
            ->sort($request->all())
            ->orderBy('teams.id', 'desc')
            ->with([
                'users',
                'primaryAttachment'
            ])->paginate($perPage)->withQueryString();

        // Get users for filter
        $users = app(UserService::class)->getAccessibleUsers(auth()->user());

        return view('teams.index', compact('teams', 'perPage', 'users'));
    }

    public function create()
    {
        $users = $this->getAvailableTeamUsers();

        $teamRoles = config('constants.team_roles');

        return view('teams.create', compact('users', 'teamRoles'));
    }

    public function store(TeamRequest $request, TeamService $service)
    {
        $service->createTeam([
            ...$request->validated(),
            'members' => $request->input('members', []),
        ]);

        return redirect()->route('teams.index')
            ->with('success', 'Team created successfully.');
    }

    public function edit(int $id)
    {
        $team = Team::findOrFail($id);
        $teamUsers = $team->users()->with('primaryAttachment')->get();

        $users = $this->getAvailableTeamUsers($team);

        $teamRoles = config('constants.team_roles');


        return view('teams.edit', compact('team', 'teamUsers', 'users', 'teamRoles'));
    }

    public function update(TeamRequest $request, Team $team, TeamService $service)
    {
        $service->updateTeam($team, [
            ...$request->validated(),
            'members' => $request->input('members', []),
        ]);

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
        $team->is_active = !$team->is_active;
        $team->save();

        return response()->json([
            'success' => true,
            'is_active' => $team->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    private function getAvailableTeamUsers(?Team $team = null)
    {
        $assignedUserIds = TeamUser::query()
            ->whereNull('deleted_at')
            ->when($team, fn($query) => $query->where('team_id', '!=', $team->id))
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->all();

        return app(UserService::class)
            ->getAccessibleUsers(auth()->user())
            ->whereNotIn('id', $assignedUserIds)
            ->values();
    }
}
