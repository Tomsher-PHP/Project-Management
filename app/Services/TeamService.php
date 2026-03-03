<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Support\Facades\DB;

class TeamService
{

    protected $attachmentService;

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

            // 3. Create user details
            // $user->details()->create(
            //     collect($data)->only((new UserDetail())->getFillable())->toArray()
            // );

            // 4. Image upload can be handled here if needed
            if (!empty($data['profile_image'])) {
                $this->attachmentService->upload($data['profile_image'], 'team_avatar', $team, 'public', 'public', true);
            }

            return $team;
        });
    }

    public function updateUser(Team $team, array $data)
    {
        return DB::transaction(function () use ($team, $data) {

            // Prepare & Update team Data
            $teamData = collect($data)->only(['name'])->toArray();

            $team->update($teamData);

            // Update or Create User Details (hasOne)
            $detailsData = collect($data)
                ->only((new TeamUser())->getFillable())
                ->toArray();

            $team->details()->updateOrCreate([], $detailsData);

            // Handle Profile Image Upload or delete existing
            if (!empty($data['profile_image'])) {
                $this->updateProfileImage($team, $data['profile_image']);
            } elseif (!empty($data['remove_profile_image'])) {
                // Delete existing attachments
                $this->attachmentService->delete($team->attachments);
            }

            return $team->load(['details']);
        });
    }

    private function updateProfileImage(Team $team, $image): void
    {
        // Delete existing attachments
        $this->attachmentService->delete($team->attachments);

        // Upload new image
        $this->attachmentService->upload(
            $image,
            'user_profile',
            $team,
            'public',
            'public',
            true
        );
    }
}
