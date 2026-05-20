<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

class TeamService
{

    protected $attachmentService;
    protected $notificationService;
    protected $avatarDir = 'team_avatar';

    public function __construct(AttachmentService $attachmentService, NotificationService $notificationService)
    {
        $this->attachmentService = $attachmentService;
        $this->notificationService = $notificationService;
    }

    public function createTeam(array $data)
    {
        $notificationBatches = [];

        $team = DB::transaction(function () use ($data, &$notificationBatches) {

            $team = Team::create([
                ...collect($data)->only(['name'])->toArray(),
            ]);

            $members = $data['members'] ?? [];

            if ($members !== []) {
                $team->users()->attach($this->formatMembers($members));
                $notificationBatches = $this->groupMembersByRoleName($members);
            }

            if (!empty($data['profile_image'])) {
                $this->attachmentService->upload($data['profile_image'], $this->avatarDir, $team, 'public', 'public', true);
            }

            return $team;
        });

        $this->notifyGroupedMembers($team, $notificationBatches, 'added');

        return $team;
    }

    public function updateTeam(Team $team, array $data)
    {
        $notifications = [
            'added' => [],
            'removed' => [],
        ];

        $team = DB::transaction(function () use ($team, $data, &$notifications) {
            $existingMembers = $this->getExistingMemberRoles($team);

            $team->update([
                'name' => $data['name'],
            ]);

            $members = $data['members'] ?? [];
            $team->users()->sync($this->formatMembers($members));

            $notifications = $this->buildMembershipNotifications($existingMembers, $members);

            if (!empty($data['profile_image'])) {
                $this->updateProfileImage($team, $data['profile_image']);
            } elseif (!empty($data['remove_profile_image'])) {
                $this->attachmentService->delete($team->attachments);
            }

            return $team->load(['users']);
        });

        $this->notifyGroupedMembers($team, $notifications['added'], 'added');
        $this->notifyGroupedMembers($team, $notifications['removed'], 'removed');

        return $team;
    }

    private function updateProfileImage(Team $team, $image): void
    {
        $this->attachmentService->delete($team->attachments);

        $this->attachmentService->upload(
            $image,
            $this->avatarDir,
            $team,
            'public',
            'public',
            true
        );
    }

    private function formatMembers(array $members): array
    {
        $data = [];

        foreach ($members as $member) {
            $data[$member['user_id']] = [
                'team_role' => $member['team_role'],
                'joined_at' => now(),
                'added_by' => auth()->id(),
            ];
        }

        return $data;
    }

    private function getExistingMemberRoles(Team $team): array
    {
        return $team->users()
            ->select('users.id')
            ->get()
            ->mapWithKeys(function ($user) {
                return [
                    (int) $user->id => $this->resolveTeamRoleName($user->pivot->team_role),
                ];
            })
            ->all();
    }

    private function buildMembershipNotifications(array $existingMembers, array $members): array
    {
        $newMembers = collect($members)
            ->mapWithKeys(function ($member) {
                $userId = (int) ($member['user_id'] ?? 0);

                if (! $userId) {
                    return [];
                }

                return [
                    $userId => $this->resolveTeamRoleName($member['team_role'] ?? null),
                ];
            })
            ->all();

        $added = [];
        foreach (array_diff(array_keys($newMembers), array_keys($existingMembers)) as $userId) {
            $added[$userId] = $newMembers[$userId] ?? null;
        }

        $removed = [];
        foreach (array_diff(array_keys($existingMembers), array_keys($newMembers)) as $userId) {
            $removed[$userId] = $existingMembers[$userId] ?? null;
        }

        return [
            'added' => $this->groupNotificationsByRoleName($added),
            'removed' => $this->groupNotificationsByRoleName($removed),
        ];
    }

    private function groupMembersByRoleName(array $members): array
    {
        $groupedMembers = collect($members)
            ->map(function ($member) {
                return [
                    'user_id' => (int) ($member['user_id'] ?? 0),
                    'role_name' => $this->resolveTeamRoleName($member['team_role'] ?? null),
                ];
            })
            ->filter(fn ($member) => $member['user_id'] > 0)
            ->groupBy('role_name');

        return $groupedMembers->map(function ($members, $roleName) {
            return [
                'user_ids' => $members->pluck('user_id')->values()->all(),
                'role_name' => $roleName ?: null,
            ];
        })->values()->all();
    }

    private function groupNotificationsByRoleName(array $membersByUserId): array
    {
        return collect($membersByUserId)
            ->groupBy(fn ($roleName) => $roleName, true)
            ->map(function ($roleNames, $roleName) {
                return [
                    'user_ids' => $roleNames->keys()->map(fn ($userId) => (int) $userId)->values()->all(),
                    'role_name' => $roleName ?: null,
                ];
            })
            ->values()
            ->all();
    }

    private function notifyGroupedMembers(Team $team, array $notificationBatches, string $action): void
    {
        foreach ($notificationBatches as $batch) {
            $userIds = $batch['user_ids'] ?? [];
            $roleName = $batch['role_name'] ?? null;

            if ($userIds === []) {
                continue;
            }

            if ($action === 'removed') {
                $this->notificationService->notifyTeamMemberRemoved($userIds, $team, $roleName);
                continue;
            }

            $this->notificationService->notifyTeamMemberAdded($userIds, $team, $roleName);
        }
    }

    private function resolveTeamRoleName(?string $teamRole): ?string
    {
        if (blank($teamRole)) {
            return null;
        }

        $roleName = config('constants.team_roles.' . $teamRole);

        return filled($roleName) ? (string) $roleName : null;
    }
}
