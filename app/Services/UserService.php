<?php

namespace App\Services;

use App\Jobs\SendWelcomeMailJob;
use App\Models\Configuration;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserGeneralSetting;
use App\Models\UserNotificationSetting;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Facades\LogBatch;

class UserService
{

    protected AttachmentService $attachmentService;
    protected string $avatarDisk;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
        $this->avatarDisk = env('AVATAR_DISK', 'public');
    }

    public function createUser(array $data)
    {
        return $this->runInActivityBatch(function () use ($data) {

            // Get user type from role
            $role = isset($data['role'])
                ? Role::select('id', 'name')->find($data['role'])
                : null;

            // 1. Handle password
            $plainPassword = $data['password'];

            $user = User::create([
                ...collect($data)->only(['name', 'email'])->toArray(),
                'password'  => Hash::make($plainPassword),
            ]);

            $this->createDefaultGeneralSettings($user);
            $this->createDefaultNotificationSettings($user);

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
                $this->attachmentService->upload($data['profile_image'], 'user_profile', $user, $this->avatarDisk, 'public', true);
            }

            // KPI sync
            if (!empty($data['kpi_id'])) {
                $user->kpis()->sync($data['kpi_id']);
            }

            $company = Configuration::first();

            // Send mail
            dispatch(new SendWelcomeMailJob($user, $plainPassword, $company));
            return $user;
        });
    }

    private function createDefaultGeneralSettings(User $user): void
    {
        UserGeneralSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'kanban_view' => 'agile',
                'theme' => 'light',
            ]
        );

        // Add user settings for this user
        $userSettings = config('constants.user_settings', []);
        foreach ($userSettings as $key => $label) {
            UserSetting::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                ],
                [
                    'value' => false,
                ]
            );
        }
    }

    private function createDefaultNotificationSettings(User $user): void
    {
        $settings = config('notification_settings', []);

        foreach ($settings as $setting) {
            $action = $setting['action'] ?? null;

            if (blank($action)) {
                continue;
            }

            UserNotificationSetting::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'action' => $action,
                ],
                [
                    'in_app' => true,
                    'mail' => true,
                ]
            );
        }
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
                ->only(['name', 'email', 'password'])
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

            // kpis
            if (isset($data['kpi_id'])) {
                $user->kpis()->sync($data['kpi_id'] ?? []);
            }

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
            $this->avatarDisk,
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
            ->when(!empty($includeIds), fn($query) => $query->withTrashed())
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

    /**
     * Get accessible user IDs for the given user.
     */
    public function getAccessibleUserIds(User $authUser, array $excludeIds = [], array $includeIds = []): array
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
            ->pluck('id')
            ->values()
            ->all();
    }

    public function getRequestAccessibleUsers(User $user, array $excludeIds = [], array $includeIds = [])
    {
        $excludeIds = User::getReporterChainUserIds($user->id);
        return collect($this->getAccessibleUserIds($user, $excludeIds))
            ->push($user->id)
            ->unique()
            ->values()
            ->all();
    }

    public function getNavSelectableUsers(User $authUser)
    {
        return User::query()
            ->accessibleBy($authUser)
            ->where('id', '!=', $authUser->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updateModalUser(User $user, array $data)
    {
        return $this->runInActivityBatch(function () use ($user, $data) {
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

            if (!empty($data['profile_image'])) {
                $this->updateProfileImage($user, $data['profile_image']);
            } elseif (!empty($data['remove_profile_image'])) {
                $this->attachmentService->delete($user->attachments);
            }

            return $user->load(['details', 'primaryAttachment']);
        });
    }

    // Disable dependentcies when user is deleted
    public function disableUserDependencies(User $user)
    {
        // Disable the user in project members
        ProjectMember::where('user_id', $user->id)->update([
            'is_active' => false,
        ]);
    }
}
