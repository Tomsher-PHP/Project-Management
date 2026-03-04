<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Support\Facades\DB;

class TeamService
{

    protected $attachmentService;
    protected $avatarDir = 'team_avatar';

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    public function createTeam(array $data)
    {
        return DB::transaction(function () use ($data) {

            // 1. Handle password
            $team = Team::create([
                ...collect($data)->only(['name'])->toArray(),
            ]);

            // Attach members if exists
            if (!empty($data['members'])) {
                $team->users()->attach($this->formatMembers($data['members']));
            }

            // 4. Image upload can be handled here if needed
            if (!empty($data['profile_image'])) {
                $this->attachmentService->upload($data['profile_image'], $this->avatarDir, $team, 'public', 'public', true);
            }

            return $team;
        });
    }

    public function updateTeam(Team $team, array $data)
    {
        return DB::transaction(function () use ($team, $data) {

            $team->update([
                'name' => $data['name'],
            ]);

            // Sync members
            if (!empty($data['members'])) {
                $team->users()->sync($this->formatMembers($data['members']));
            }

            // Handle Profile Image Upload or delete existing
            if (!empty($data['profile_image'])) {
                $this->updateProfileImage($team, $data['profile_image']);
            } elseif (!empty($data['remove_profile_image'])) {
                // Delete existing attachments
                $this->attachmentService->delete($team->attachments);
            }

            return $team->load(['users']);
        });
    }

    private function updateProfileImage(Team $team, $image): void
    {
        // Delete existing attachments
        $this->attachmentService->delete($team->attachments);

        // Upload new image
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
}
