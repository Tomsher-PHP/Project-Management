<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{

    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    public function createUser(array $data)
    {
        return DB::transaction(function () use ($data) {

            // Get user type from role
            $role = isset($data['role'])
                ? Role::select('id', 'name', 'user_type')->find($data['role'])
                : null;

            // 1. Handle password
            $user = User::create([
                ...collect($data)->only(['name', 'email'])->toArray(),
                'password'  => Hash::make($data['password']),
                'user_type' => $role?->user_type,
            ]);

            // Assign role to user
            if ($role) {
                $user->assignRole($role->name);
            }

            // 3. Create user details
            $user->details()->create(
                collect($data)->only((new UserDetail())->getFillable())->toArray()
            );

            // 4. Image upload can be handled here if needed
            if (!empty($data['profile_image'])) {
                $this->attachmentService->upload($data['profile_image'], 'user_profile', $user, 'public', 'public', true);
            }

            return $user;
        });
    }

    public function updateUser(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            // Resolve Role (if provided)
            $role = !empty($data['role'])
                ? Role::select('id', 'name', 'user_type')->find($data['role'])
                : null;

            // Prepare & Update User Data
            $userData = collect($data)
                ->only(['name', 'email'])
                ->when(!empty($data['password']), function ($collection) use ($data) {
                    $collection['password'] = Hash::make($data['password']);
                    return $collection;
                })
                ->toArray();

            if ($role) {
                $userData['user_type'] = $role->user_type;
            }

            $user->update($userData);

            // Assign role to user
            if ($role) {
                $user->syncRoles($role->name);
            }

            // Update or Create User Details (hasOne)
            $detailsData = collect($data)
                ->only((new UserDetail())->getFillable())
                ->toArray();

            $user->details()->updateOrCreate([], $detailsData);

            // Handle Profile Image Upload or delete existing
            if (!empty($data['profile_image'])) {
                $this->updateProfileImage($user, $data['profile_image']);
            } elseif (!empty($data['remove_profile_image'])) {
                // Delete existing attachments
                $this->attachmentService->delete($user->attachments);
            }

            return $user->load(['details']);
        });
    }

    private function updateProfileImage(User $user, $image): void
    {
        // Delete existing attachments
        $this->attachmentService->delete($user->attachments);

        // Upload new image
        $this->attachmentService->upload(
            $image,
            'user_profile',
            $user,
            'public',
            'public',
            true
        );
    }
}
