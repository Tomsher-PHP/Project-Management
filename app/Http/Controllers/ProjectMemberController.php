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
        // Prevent duplicate
        if ($project->members()->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'User already added.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $member = $project->members()->create([
            'user_id' => $request->user_id,
            'project_role' => $request->project_role,
        ]);
        $member->load('user');

        $memberCardHtml = view('projects.partials.member-card', compact('project', 'member'))->render();

        return response()->json([
            'status' => true,
            'message' => 'Member added successfully.',
            'member_card' => $memberCardHtml,
        ], Response::HTTP_OK);
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
