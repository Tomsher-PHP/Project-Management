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
                $this->attachmentService->upload($data['profile_image'], 'user_profile', $user, auth()->id(), 'public', 'public', true);
            }

            return $user;
        });
    }

    public function updateUser(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            // 1. Update user table
            $updateUserData = [
                'name'  => $data['name'],
                'email' => $data['email'],
            ];

            if (!empty($data['password'])) {
                $updateUserData['password'] = Hash::make($data['password']);
            }

            $user->update($updateUserData);

            // 2. Update details table
            $user->details()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id'        => $data['department'] ?? null,
                    'designation_id'       => $data['designation'] ?? null,
                    'reporter_id'          => $data['reporting_to'] ?? null,
                    'manager_id'           => $data['manager'] ?? null,
                    'employee_id'          => $data['employee_id'] ?? null,
                    'gender'               => $data['gender'] ?? 'male',
                    'phone'         => $data['phone'] ?? null,
                    'whatsapp'      => $data['whatsapp'] ?? null,
                    'contact_person'       => $data['contact_person_name'] ?? null,
                    'contact_person_number' => $data['contact_person_number'] ?? null,
                    'joining_date'         => $data['joining_date'] ?? null,
                    'leaving_date'         => $data['leaving_date'] ?? null,
                    'dob'                  => $data['dob'] ?? null,
                    'address'              => $data['address'] ?? null,
                ]
            );

            return $user;
        });
    }
}
