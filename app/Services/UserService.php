<?php

namespace App\Services;

use App\Exports\UserDetailsExport;
use App\Jobs\SendWelcomeMailJob;
use App\Models\Configuration;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserGeneralSetting;
use App\Models\UserNotificationSetting;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Facades\LogBatch;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

            // Get selected roles using role IDs
            $roleNames = !empty($data['roles'])
                ? Role::whereIn('id', $data['roles'])
                ->pluck('name')
                ->toArray()
                : [];

            // Handle password
            $plainPassword = $data['password'];

            $user = User::create([
                ...collect($data)->only(['name', 'email'])->toArray(),
                'password' => Hash::make($plainPassword),
            ]);

            $this->createDefaultGeneralSettings($user);
            $this->createDefaultNotificationSettings($user);

            // Assign multiple roles to the user
            if (!empty($roleNames)) {
                $oldRoles = [];

                $user->syncRoles($roleNames);

                $this->logRoleActivity(
                    $user,
                    $oldRoles,
                    $this->getRoleNames($user)
                );
            }

            // Create user details
            $detailsData = $this->prepareUserDetailsData($data);
            $user->details()->create($detailsData);

            // Handle profile image upload
            if (!empty($data['profile_image'])) {
                $this->attachmentService->upload(
                    $data['profile_image'],
                    'user_profile',
                    $user,
                    $this->avatarDisk,
                    'public',
                    true
                );
            }

            // KPI sync
            if (!empty($data['kpi_id'])) {
                $user->kpis()->sync($data['kpi_id']);
            }

            $company = Configuration::first();

            // Send welcome email
            dispatch(new SendWelcomeMailJob(
                $user,
                $plainPassword,
                $company
            ));

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

            // Get existing role names before updating
            $oldRoles = $this->getRoleNames($user);

            // Prepare and update user data
            $userData = collect($data)
                ->only(['name', 'email', 'password'])
                ->toArray();

            // Avoid overwriting the existing password with an empty value
            if (empty($userData['password'])) {
                unset($userData['password']);
            } else {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user->update($userData);

            // Synchronize selected roles
            if (array_key_exists('roles', $data)) {
                $roleNames = !empty($data['roles'])
                    ? Role::whereIn('id', $data['roles'])
                    ->pluck('name')
                    ->toArray()
                    : [];

                // Assign selected roles and remove unselected roles
                $user->syncRoles($roleNames);

                // Log role changes
                $newRoles = $this->getRoleNames($user);

                if ($oldRoles != $newRoles) {
                    $this->logRoleActivity(
                        $user,
                        $oldRoles,
                        $newRoles
                    );
                }
            }

            // Update or create user details
            $detailsData = $this->prepareUserDetailsData($data);

            $user->details()->updateOrCreate([], $detailsData);

            // KPI sync
            if (isset($data['kpi_id'])) {
                $user->kpis()->sync($data['kpi_id'] ?? []);
            }

            // Handle profile image upload or removal
            if (!empty($data['profile_image'])) {
                $this->updateProfileImage(
                    $user,
                    $data['profile_image']
                );
            } elseif (!empty($data['remove_profile_image'])) {
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
     * @return \Illuminate\Support\Collection of User models.
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

            // Handle legacy contact_person fallback if emergency_contacts is not provided
            if (!array_key_exists('emergency_contacts', $data) && (array_key_exists('contact_person', $data) || array_key_exists('contact_person_number', $data))) {
                $existingContacts = $user->details?->emergency_contacts ?? [];
                if (!empty($data['contact_person']) || !empty($data['contact_person_number'])) {
                    if (!empty($existingContacts)) {
                        $existingContacts[0]['name'] = $data['contact_person'] ?? null;
                        $existingContacts[0]['number'] = $data['contact_person_number'] ?? null;
                    } else {
                        $existingContacts = [
                            [
                                'name' => $data['contact_person'] ?? null,
                                'number' => $data['contact_person_number'] ?? null,
                                'relation' => null,
                            ],
                        ];
                    }
                } else {
                    $existingContacts = null;
                }
                $data['emergency_contacts'] = $existingContacts;
            }

            $detailsData = $this->prepareUserDetailsData($data);

            $user->details()->updateOrCreate(
                ['user_id' => $user->id],
                $detailsData
            );

            if (!empty($data['profile_image'])) {
                $this->updateProfileImage($user, $data['profile_image']);
            } elseif (!empty($data['remove_profile_image'])) {
                $this->attachmentService->delete($user->attachments);
            }

            return $user->load(['details', 'primaryAttachment']);
        });
    }

    /**
     * Prepare and sanitize user details data for storage.
     */
    public function prepareUserDetailsData(array $data): array
    {
        $fillable = (new UserDetail())->getFillable();
        $detailsData = collect($data)->only($fillable)->toArray();

        // Process emergency contacts
        if (array_key_exists('emergency_contacts', $data)) {
            $contacts = [];
            if (is_array($data['emergency_contacts'])) {
                foreach ($data['emergency_contacts'] as $contact) {
                    if (!is_array($contact)) {
                        continue;
                    }
                    $name = isset($contact['name']) && trim((string)$contact['name']) !== '' ? trim((string)$contact['name']) : null;
                    $number = isset($contact['number']) && trim((string)$contact['number']) !== '' ? trim((string)$contact['number']) : null;
                    $relation = isset($contact['relation']) && trim((string)$contact['relation']) !== '' ? trim((string)$contact['relation']) : null;

                    if ($name !== null || $number !== null || $relation !== null) {
                        $contacts[] = [
                            'name' => $name,
                            'number' => $number,
                            'relation' => $relation,
                        ];
                    }
                }
            }

            $detailsData['emergency_contacts'] = !empty($contacts) ? array_values($contacts) : null;
        }

        // Process native contacts
        if (array_key_exists('native_contacts', $data)) {
            $contacts = [];
            if (is_array($data['native_contacts'])) {
                foreach ($data['native_contacts'] as $contact) {
                    if (!is_array($contact)) {
                        continue;
                    }
                    $name = isset($contact['name']) && trim((string)$contact['name']) !== '' ? trim((string)$contact['name']) : null;
                    $number = isset($contact['number']) && trim((string)$contact['number']) !== '' ? trim((string)$contact['number']) : null;
                    $relation = isset($contact['relation']) && trim((string)$contact['relation']) !== '' ? trim((string)$contact['relation']) : null;

                    if ($name !== null || $number !== null || $relation !== null) {
                        $contacts[] = [
                            'name' => $name,
                            'number' => $number,
                            'relation' => $relation,
                        ];
                    }
                }
            }

            $detailsData['native_contacts'] = !empty($contacts) ? array_values($contacts) : null;
        }

        // Process shirt sizes
        if (array_key_exists('shirt_sizes', $data)) {
            if (is_array($data['shirt_sizes'])) {
                $casual = isset($data['shirt_sizes']['casual']) && trim((string)$data['shirt_sizes']['casual']) !== '' ? trim((string)$data['shirt_sizes']['casual']) : null;
                $formal = isset($data['shirt_sizes']['formal']) && trim((string)$data['shirt_sizes']['formal']) !== '' ? trim((string)$data['shirt_sizes']['formal']) : null;

                if ($casual !== null || $formal !== null) {
                    $detailsData['shirt_sizes'] = [
                        'casual' => $casual,
                        'formal' => $formal,
                    ];
                } else {
                    $detailsData['shirt_sizes'] = null;
                }
            } else {
                $detailsData['shirt_sizes'] = null;
            }
        }

        // Process shoe sizes
        if (array_key_exists('shoe_sizes', $data)) {
            if (is_array($data['shoe_sizes'])) {
                $casual = isset($data['shoe_sizes']['casual']) && trim((string)$data['shoe_sizes']['casual']) !== '' ? trim((string)$data['shoe_sizes']['casual']) : null;
                $formal = isset($data['shoe_sizes']['formal']) && trim((string)$data['shoe_sizes']['formal']) !== '' ? trim((string)$data['shoe_sizes']['formal']) : null;

                if ($casual !== null || $formal !== null) {
                    $detailsData['shoe_sizes'] = [
                        'casual' => $casual,
                        'formal' => $formal,
                    ];
                } else {
                    $detailsData['shoe_sizes'] = null;
                }
            } else {
                $detailsData['shoe_sizes'] = null;
            }
        }

        // Enforce Marital Status & Children Count conditional rule
        if (array_key_exists('marital_status', $data)) {
            $maritalStatus = isset($data['marital_status']) && trim((string)$data['marital_status']) !== '' ? trim((string)$data['marital_status']) : null;
            $detailsData['marital_status'] = $maritalStatus;

            if ($maritalStatus !== 'Married') {
                $detailsData['children_count'] = null;
            } elseif (array_key_exists('children_count', $data)) {
                $childrenCount = $data['children_count'];
                $detailsData['children_count'] = ($childrenCount !== null && $childrenCount !== '') ? (int)$childrenCount : null;
            }
        } elseif (array_key_exists('children_count', $data)) {
            $childrenCount = $data['children_count'];
            $detailsData['children_count'] = ($childrenCount !== null && $childrenCount !== '') ? (int)$childrenCount : null;
        }

        return $detailsData;
    }

    // Disable dependentcies when user is deleted
    public function disableUserDependencies(User $user)
    {
        // Disable the user in project members
        ProjectMember::where('user_id', $user->id)->update([
            'is_active' => false,
        ]);
    }

    /**
     * Build the base query for listing and exporting eligible users.
     */
    public function getUsersListingQuery(User $authUser, array $filters = []): Builder
    {
        return User::accessibleBy($authUser)
            ->filter($filters)
            ->sort($filters)
            ->orderBy('users.id', 'desc')
            ->where('is_super_admin', false)
            ->where('delete_status', false)
            ->whereNot('id', $authUser->id);
    }

    /**
     * Export eligible users and their details to an Excel file.
     */
    public function exportUsersToExcel(User $authUser, array $filters = []): BinaryFileResponse
    {
        $users = $this->getUsersListingQuery($authUser, $filters)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.is_active',
            ])
            ->with([
                'details' => function ($query): void {
                    $query->select([
                        'id',
                        'user_id',
                        'department_id',
                        'designation_id',
                        'reporter_id',
                        'manager_id',
                        'employee_id',
                        'gender',
                        'dob',
                        'phone',
                        'whatsapp',
                        'joining_date',
                        'leaving_date',
                        'marital_status',
                        'children_count',
                        'favorite_color',
                        'address',
                        'native_address',
                        'emergency_contacts',
                        'native_contacts',
                        'shirt_sizes',
                        'shoe_sizes',
                    ]);
                },
                'details.department:id,name',
                'details.designation:id,name',
                'details.reporter:id,name',
                'details.manager:id,name',
            ])
            ->get();

        $timestamp = now((string) config('constants.timezone', config('app.timezone')))
            ->format('Y-m-d_H-i-s');

        $fileName = 'users_details_' . $timestamp . '.xlsx';

        return Excel::download(
            new UserDetailsExport($users),
            $fileName
        );
    }
}
