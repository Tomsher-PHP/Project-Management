<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyUserSeeder extends Seeder
{
    private const USER_COUNT = 30;

    private const SYSTEM_USER_EMAILS = [
        'superadmin@gmail.com',
        'admin@projectmanagement.test',
        'admin@gmail.com',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['Team Member', 'Manager', 'Team Leader'];

        DB::transaction(function () use ($roles) {
            $this->truncateDummyUsers();

            $users = User::factory(self::USER_COUNT)->create();
            $managerPool = collect();

            $users->each(function (User $user, int $index) use ($roles, $managerPool) {
                $role = $roles[$index % count($roles)];

                $user->syncRoles([$role]);

                if (in_array($role, ['Manager', 'Team Leader'], true)) {
                    $managerPool->push($user->id);
                }
            });

            $users->each(function (User $user, int $index) use ($managerPool) {
                $availableManagers = $managerPool->reject(fn (int $managerId) => $managerId === $user->id)->values();
                $reporterId = $availableManagers->isNotEmpty() ? $availableManagers->random() : null;
                $managerId = $availableManagers->isNotEmpty() ? $availableManagers->random() : null;

                $user->details()->create([
                    'employee_id' => 'DMY-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'department_id' => null,
                    'designation_id' => null,
                    'reporter_id' => $reporterId,
                    'manager_id' => $managerId,
                    'gender' => fake()->randomElement(['male', 'female']),
                    'joining_date' => fake()->dateTimeBetween('-2 years', 'now'),
                ]);
            });
        });
    }

    private function truncateDummyUsers(): void
    {
        $userIds = User::withTrashed()
            ->where('is_super_admin', false)
            ->whereNotIn('email', self::SYSTEM_USER_EMAILS)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->whereIn('model_id', $userIds)
            ->delete();

        DB::table('model_has_permissions')
            ->where('model_type', User::class)
            ->whereIn('model_id', $userIds)
            ->delete();

        DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->delete();

        User::withTrashed()
            ->whereIn('id', $userIds)
            ->forceDelete();
    }
}
