<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Response;

class ProjectMemberController extends Controller
{
    public function addMember(ProjectMemberRequest $request, Project $project)
    {
        $userIds = $request->user_id;
        $role = $request->project_role;

        $members = [];
        $html = '';

        foreach ($userIds as $userId) {

            $member = $project->members()->create([
                'user_id' => $userId,
                'project_role' => $role,
            ]);

            $member->load('user');

            $members[] = $member;

            // Append rendered card
            $html .= view('projects.partials.member-card', compact('project', 'member'))->render();
        }

        if (empty($members)) {
            return response()->json([
                'status' => false,
                'message' => 'All selected users are already added.'
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Members added successfully.',
            'member_cards' => $html,
        ]);
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

        $member->delete();

        return response()->json([
            'status' => true,
            'message' => 'Member removed successfully.'
        ]);
    }
}
