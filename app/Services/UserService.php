<?php

namespace App\Services;

use App\Jobs\SendWelcomeMailJob;
use App\Mail\WelcomeUserMail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Activitylog\Facades\LogBatch;

class UserService
{

    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    public function createUser(array $data)
    {
        return $this->runInActivityBatch(function () use ($data) {

            // Get user type from role
            $role = isset($data['role'])
                ? Role::select('id', 'name')->find($data['role'])
                : null;

            // 1. Handle password
            $user = User::create([
                ...collect($data)->only(['name', 'email'])->toArray(),
                'password'  => Hash::make($data['password']),
            ]);

            // Assign role to user
            if ($role) {
                $oldRoles = [];
                $user->assignRole($role->name);
                $this->logRoleActivity($user, $oldRoles, $this->getRoleNames($user));
            }

            // 3. Create user details
            $user->details()->create(
                collect($data)->only((new UserDetail())->getFillable())->toArray()
            );

            // 4. Image upload can be handled here if needed
            if (!empty($data['profile_image'])) {
                $this->attachmentService->upload($data['profile_image'], 'user_profile', $user, 'public', 'public', true);
            }

            // Generate password
            $plainPassword = Str::random(8);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($plainPassword),
            ]);

            // Send mail
            dispatch(new SendWelcomeMailJob($user, $plainPassword));
            return $user;
        });
    }

    public function updateUser(User $user, array $data)
    {
        return $this->runInActivityBatch(function () use ($user, $data) {

            // Resolve Role (if provided)
            $role = !empty($data['role'])
                ? Role::select('id', 'name')->find($data['role'])
                : null;
            $oldRoles = $this->getRoleNames($user);

            // Prepare & Update User Data
            $userData = collect($data)
                ->only(['name', 'email'])
                ->when(!empty($data['password']), function ($collection) use ($data) {
                    $collection['password'] = Hash::make($data['password']);
                    return $collection;
                })
                ->toArray();

            $user->update($userData);

            // Assign role to user
            if ($role) {
                $user->syncRoles($role->name);
                $this->logRoleActivity($user, $oldRoles, $this->getRoleNames($user));
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

    private function runInActivityBatch(callable $callback)
    {
        return DB::transaction(function () use ($callback) {
            LogBatch::startBatch();

            try {
                return $callback();
            } finally {
                LogBatch::endBatch();
            }
        });
    }

    private function getRoleNames(User $user): array
    {
        return $user->roles()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    private function logRoleActivity(User $user, array $oldRoles, array $newRoles): void
    {
        $oldRoles = collect($oldRoles)
            ->filter()
            ->sort()
            ->values()
            ->all();

        $newRoles = collect($newRoles)
            ->filter()
            ->sort()
            ->values()
            ->all();

        if ($oldRoles === $newRoles) {
            return;
        }

        $oldRole = $this->formatRoleNames($oldRoles);
        $newRole = $this->formatRoleNames($newRoles);

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'old' => [
                    'role' => $oldRole,
                ],
                'attributes' => [
                    'role' => $newRole,
                ],
                'labels' => [
                    'role' => 'Role',
                ],
                'display_old' => [
                    'role' => $oldRole,
                ],
                'display_attributes' => [
                    'role' => $newRole,
                ],
            ])
            ->log('users.role_updated');
    }

    private function formatRoleNames(array $roles): ?string
    {
        $roles = collect($roles)
            ->filter()
            ->values();

        return $roles->isNotEmpty()
            ? $roles->implode(', ')
            : null;
    }

    /**
     * Get users accessible to the given user, with options to exclude or include specific user IDs.
     *
     * @param User $authUser The user for whom to retrieve accessible users.
     * @param array $excludeIds Optional array of user IDs to exclude from the results.
     * @param array $includeIds Optional array of user IDs to include in the results (even if not accessible).
     * @return \Illuminate\Support\Collection Collection of User models.
     */
    public function getAccessibleUsers(User $authUser, array $excludeIds = [], array $includeIds = [])
    {
        $excludeIds = collect($excludeIds)
            ->flatten()
            ->filter(fn($id) => filled($id))
            ->unique()
            ->values()
            ->all();

        $includeIds = collect($includeIds)
            ->flatten()
            ->filter(fn($id) => filled($id))
            ->unique()
            ->values()
            ->all();

        $accessibleIds = User::accessibleBy($authUser)->select('id');

        return User::query()
            ->when(!empty($includeIds), fn ($query) => $query->withTrashed())
            ->select('id', 'name', 'email', 'is_active')
            ->with('primaryAttachment')
            ->where(function ($q) use ($accessibleIds, $includeIds) {
                $q->whereIn('id', $accessibleIds);

                if (!empty($includeIds)) {
                    $q->orWhereIn('id', $includeIds);
                }
            })
            ->where(function ($q) use ($includeIds) {
                $q->where(function ($q) {
                    $q->where('is_active', true)
                        ->where('delete_status', false)
                        ->whereNull('deleted_at');
                });

                if (!empty($includeIds)) {
                    $q->orWhereIn('id', $includeIds);
                }
            })
            ->when(!empty($excludeIds), function ($q) use ($excludeIds) {
                $q->whereNotIn('id', $excludeIds);
            })
            ->orderBy('name')
            ->get();
    }

    public function updateModalUser(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'],
        ]);

        $user->details()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_person_number' => $data['contact_person_number'] ?? null,
                'address' => $data['address'] ?? null,
            ]
        );
    }
}
