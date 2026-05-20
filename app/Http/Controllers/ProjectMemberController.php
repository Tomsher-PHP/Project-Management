<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectMemberController extends Controller
{
    public function addMember(ProjectMemberRequest $request, Project $project, NotificationService $notificationService)
    {
        $notifications = [];

        $response = DB::transaction(function () use ($request, $project, &$notifications) {
            $userIds = (array) $request->user_id;
            $role = $request->project_role;
            $updatedCardIds = [];
            $roleName = $this->resolveProjectRoleName($role);

            if (in_array($role, ['team_leader', 'coordinator'], true) && count($userIds) > 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only one user can be assigned as team leader or coordinator at a time.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $existingMembers = $project->membersAll()
                ->with([
                    'details.designation',
                    'primaryAttachment',
                ])
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
                        $notifications[] = [
                            'type' => 'added',
                            'user_id' => (int) $userId,
                            'role_name' => $roleName,
                        ];
                    }
                } else {
                    $project->membersAll()->syncWithoutDetaching([
                        $userId => [
                            'project_role' => $role,
                            'is_active' => true,
                        ]
                    ]);

                    $newlyAddedIds[] = $userId;
                    $notifications[] = [
                        'type' => 'added',
                        'user_id' => (int) $userId,
                        'role_name' => $roleName,
                    ];
                }
            }

            if (in_array($role, ['team_leader', 'coordinator'], true) && !empty($newlyAddedIds)) {
                $roleUpdateResult = $this->assignExclusiveRole($project, (int) $newlyAddedIds[0], $role);
                $updatedCardIds = $roleUpdateResult['updated_user_ids'];

                foreach ($roleUpdateResult['role_notifications'] as $roleNotification) {
                    if (in_array($roleNotification['user_id'], $newlyAddedIds, true)) {
                        continue;
                    }

                    $notifications[] = [
                        'type' => 'role_updated',
                        ...$roleNotification,
                    ];
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
                ->with([
                    'details.designation',
                    'primaryAttachment',
                ])
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

        $this->dispatchProjectMemberNotifications($notificationService, $project, $notifications);

        return $response;
    }

    public function removeMember(int $projectId, int $userId, NotificationService $notificationService)
    {
        $member = ProjectMember::with('project')->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $roleName = $this->resolveProjectRoleName($member->project_role);

        $updated = $member->update([
            'is_active' => false,
            'removed_at' => now(),
            'removed_by' => Auth::id(),
        ]);

        if ($updated) {
            $notificationService->notifyProjectMemberRemoved($userId, $member->project, $roleName);
        }

        return response()->json([
            'status' => true,
            'message' => 'Member removed successfully.'
        ]);
    }

    public function toggleStatus(Project $project, int $userId, NotificationService $notificationService)
    {
        $member = $project->membersAll()
            ->where('user_id', $userId)
            ->whereNull('project_members.removed_at')
            ->firstOrFail();

        $previousIsActive = (bool) $member->pivot->is_active;
        $newIsActive = ! $previousIsActive;

        $updated = $member->pivot->update([
            'is_active' => $newIsActive,
        ]);
        if ($updated) {
            $member->pivot->is_active = $newIsActive;
        }

        if ($updated && $previousIsActive !== $newIsActive) {
            $notificationService->notifyProjectMemberStatusChanged(
                $userId,
                $project,
                $newIsActive,
                $this->resolveProjectRoleName($member->pivot->project_role)
            );
        }

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

    public function updateRole(Request $request, Project $project, int $userId, NotificationService $notificationService)
    {
        $validated = $request->validate([
            'project_role' => ['required', 'in:team_leader,coordinator'],
        ]);

        $notifications = [];

        $response = DB::transaction(function () use ($project, $userId, $validated, &$notifications) {
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

            $roleUpdateResult = $this->assignExclusiveRole($project, (int) $userId, $role);
            $updatedCardIds = $roleUpdateResult['updated_user_ids'];
            $notifications = array_map(function ($notification) {
                return [
                    'type' => 'role_updated',
                    ...$notification,
                ];
            }, $roleUpdateResult['role_notifications']);

            return response()->json([
                'status' => true,
                'message' => sprintf('%s assigned successfully.', ucwords(str_replace('_', ' ', $role))),
                'updated_cards' => $this->renderMemberCards($project, $updatedCardIds),
            ]);
        });

        $this->dispatchProjectMemberNotifications($notificationService, $project, $notifications);

        return $response;
    }

    private function assignExclusiveRole(Project $project, int $userId, string $role): array
    {
        $updatedUserIds = [$userId];
        $roleNotifications = [];
        $targetMember = $project->membersAll()
            ->where('users.id', $userId)
            ->whereNull('project_members.removed_at')
            ->first();
        $oldRole = $targetMember?->pivot->project_role;

        if (!in_array($role, ['team_leader', 'coordinator'], true)) {
            $project->membersAll()->updateExistingPivot($userId, [
                'project_role' => $role,
            ]);

            if ($oldRole !== $role) {
                $roleNotifications[] = [
                    'user_id' => $userId,
                    'old_role_name' => $this->resolveProjectRoleName($oldRole),
                    'new_role_name' => $this->resolveProjectRoleName($role),
                ];
            }

            return [
                'updated_user_ids' => $updatedUserIds,
                'role_notifications' => $roleNotifications,
            ];
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
            $roleNotifications[] = [
                'user_id' => (int) $existingRoleHolder->id,
                'old_role_name' => $this->resolveProjectRoleName($existingRoleHolder->pivot->project_role),
                'new_role_name' => $this->resolveProjectRoleName('member'),
            ];
        }

        $project->membersAll()->updateExistingPivot($userId, [
            'project_role' => $role,
        ]);

        if ($oldRole !== $role) {
            $roleNotifications[] = [
                'user_id' => $userId,
                'old_role_name' => $this->resolveProjectRoleName($oldRole),
                'new_role_name' => $this->resolveProjectRoleName($role),
            ];
        }

        return [
            'updated_user_ids' => array_values(array_unique($updatedUserIds)),
            'role_notifications' => $roleNotifications,
        ];
    }

    private function renderMemberCards(Project $project, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return $project->membersAll()
            ->with([
                'details.designation',
                'primaryAttachment',
            ])
            ->whereIn('users.id', $userIds)
            ->get()
            ->mapWithKeys(function ($member) use ($project) {
                return [
                    (string) $member->id => view('projects.partials.member-card', compact('project', 'member'))->render(),
                ];
            })
            ->all();
    }

    private function dispatchProjectMemberNotifications(NotificationService $notificationService, Project $project, array $notifications): void
    {
        foreach ($notifications as $notification) {
            $userId = (int) ($notification['user_id'] ?? 0);

            if (! $userId) {
                continue;
            }

            match ($notification['type'] ?? null) {
                'added' => $notificationService->notifyProjectMemberAdded(
                    $userId,
                    $project,
                    $notification['role_name'] ?? null
                ),
                'role_updated' => $notificationService->notifyProjectMemberRoleUpdated(
                    $userId,
                    $project,
                    $notification['old_role_name'] ?? null,
                    $notification['new_role_name'] ?? null
                ),
                default => null,
            };
        }
    }

    private function resolveProjectRoleName(?string $role): ?string
    {
        if (blank($role)) {
            return null;
        }

        $roleName = config('project_constants.project_roles.' . $role);

        return filled($roleName) ? (string) $roleName : null;
    }
}
