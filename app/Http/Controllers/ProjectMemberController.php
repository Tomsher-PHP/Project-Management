<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
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

            $existingMembers = $project->membersAll()
                ->whereIn('users.id', $userIds)
                ->get()
                ->keyBy('id');

            $newlyAddedIds = [];

            foreach ($userIds as $userId) {
                $existing = $existingMembers->get($userId);

                if ($existing) {
                    if ($existing->pivot->removed_at) {
                        // If the member was removed before, re-add them
                        $project->membersAll()->updateExistingPivot($userId, [
                            'project_role' => $role,
                            'is_active' => true,
                            'removed_at' => null,
                            'removed_by' => null,
                        ]);

                        $newlyAddedIds[] = $userId;
                    }
                } else {
                    // Attach new user to project
                    $project->membersAll()->syncWithoutDetaching([
                        $userId => [
                            'project_role' => $role,
                            'is_active' => true,
                        ]
                    ]);

                    $newlyAddedIds[] = $userId;
                }
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

            return response()->json([
                'status' => true,
                'message' => 'Members added successfully.',
                'member_cards' => $html,
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
        $member = $project->membersAll()->where('user_id', $userId)->whereNull('removed_at')->firstOrFail();

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
}
