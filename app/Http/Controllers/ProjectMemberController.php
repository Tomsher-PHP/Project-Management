<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectMemberController extends Controller
{
    public function addMember(ProjectMemberRequest $request, Project $project)
    {
        return DB::transaction(function () use ($request, $project) {
            $userIds = (array) $request->user_id;
            $role = $request->project_role;
            $updatedCardIds = [];

            if (in_array($role, ['team_leader', 'coordinator'], true) && count($userIds) > 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only one user can be assigned as team leader or coordinator at a time.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $existingMembers = $project->membersAll()
                ->whereIn('users.id', $userIds)
                ->get()
                ->keyBy('id');

            $newlyAddedIds = [];

            foreach ($userIds as $userId) {
                $existing = $existingMembers->get($userId);

                if ($existing) {
                    if ($existing->pivot->removed_at) {
                        $project->membersAll()->updateExistingPivot($userId, [
                            'project_role' => $role,
                            'is_active' => true,
                            'removed_at' => null,
                            'removed_by' => null,
                        ]);

                        $newlyAddedIds[] = $userId;
                    }
                } else {
                    $project->membersAll()->syncWithoutDetaching([
                        $userId => [
                            'project_role' => $role,
                            'is_active' => true,
                        ]
                    ]);

                    $newlyAddedIds[] = $userId;
                }
            }

            if (in_array($role, ['team_leader', 'coordinator'], true) && !empty($newlyAddedIds)) {
                $updatedCardIds = $this->assignExclusiveRole($project, (int) $newlyAddedIds[0], $role);
            }

            if (empty($newlyAddedIds)) {
                return response()->json([
                    'status' => false,
                    'message' => 'All selected users are already active members.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Fetch all members
            $members = $project->membersAll()
                ->whereIn('users.id', $newlyAddedIds)
                ->get();

            $html = '';

            foreach ($members as $member) {
                $html .= view('projects.partials.member-card', compact('project', 'member'))->render();
            }

            $updatedCards = $this->renderMemberCards($project, array_diff($updatedCardIds, $newlyAddedIds));

            return response()->json([
                'status' => true,
                'message' => 'Members added successfully.',
                'member_cards' => $html,
                'updated_cards' => $updatedCards,
            ]);
        });
    }

    public function removeMember($projectId, $userId)
    {
        $member = ProjectMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $member->update([
            'is_active' => false,
            'removed_at' => now(),
            'removed_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Member removed successfully.'
        ]);
    }

    public function toggleStatus(Project $project, $userId)
    {
        $member = $project->membersAll()
            ->where('user_id', $userId)
            ->whereNull('project_members.removed_at')
            ->firstOrFail();

        $member->pivot->update([
            'is_active' => !$member->pivot->is_active,
        ]);

        // Render the updated member card
        $cardHtml = view('projects.partials.member-card', compact('project', 'member'))->render();

        return response()->json([
            'status' => true,
            'message' => $member->pivot->is_active ? 'Member enabled.' : 'Member disabled.',
            'is_active' => $member->pivot->is_active,
            'member_card' => $cardHtml,
            'user_id' => $userId,
        ]);
    }

    public function updateRole(Request $request, Project $project, $userId)
    {
        $validated = $request->validate([
            'project_role' => ['required', 'in:team_leader,coordinator'],
        ]);

        return DB::transaction(function () use ($project, $userId, $validated) {
            $member = $project->membersAll()
                ->where('users.id', $userId)
                ->whereNull('project_members.removed_at')
                ->firstOrFail();

            $role = $validated['project_role'];

            if ($member->pivot->project_role === $role) {
                return response()->json([
                    'status' => true,
                    'message' => 'Member role is already up to date.',
                    'updated_cards' => $this->renderMemberCards($project, [(int) $userId]),
                ]);
            }

            $updatedCardIds = $this->assignExclusiveRole($project, (int) $userId, $role);

            return response()->json([
                'status' => true,
                'message' => sprintf('%s assigned successfully.', ucwords(str_replace('_', ' ', $role))),
                'updated_cards' => $this->renderMemberCards($project, $updatedCardIds),
            ]);
        });
    }

    private function assignExclusiveRole(Project $project, int $userId, string $role): array
    {
        $updatedUserIds = [$userId];

        if (!in_array($role, ['team_leader', 'coordinator'], true)) {
            $project->membersAll()->updateExistingPivot($userId, [
                'project_role' => $role,
            ]);

            return $updatedUserIds;
        }

        $existingRoleHolder = $project->membersAll()
            ->where('users.id', '!=', $userId)
            ->whereNull('project_members.removed_at')
            ->wherePivot('project_role', $role)
            ->first();

        if ($existingRoleHolder) {
            $project->membersAll()->updateExistingPivot($existingRoleHolder->id, [
                'project_role' => 'member',
            ]);

            $updatedUserIds[] = (int) $existingRoleHolder->id;
        }

        $project->membersAll()->updateExistingPivot($userId, [
            'project_role' => $role,
        ]);

        return array_values(array_unique($updatedUserIds));
    }

    private function renderMemberCards(Project $project, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return $project->membersAll()
            ->whereIn('users.id', $userIds)
            ->get()
            ->mapWithKeys(function ($member) use ($project) {
                return [
                    (string) $member->id => view('projects.partials.member-card', compact('project', 'member'))->render(),
                ];
            })
            ->all();
    }
}
