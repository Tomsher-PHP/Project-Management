<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::withTrashed()
            ->whereIn('email', ['admin@projectmanagement.test', 'admin@gmail.com'])
            ->first() ?? new User();

        $adminUser->fill([
            'name' => 'Demo Admin',
            'email' => 'admin@projectmanagement.test',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
            'is_super_admin' => false,
            'is_active' => true,
            'delete_status' => false,
        ]);

        $adminUser->save();

        if ($adminUser->trashed()) {
            $adminUser->restore();
        }

        $adminUser->syncRoles(['Admin']);

        $adminUser->details()->updateOrCreate([], [
            'employee_id' => 'ADM-001',
            'department_id' => null,
            'designation_id' => null,
            'gender' => 'male',
            'joining_date' => now()->startOfDay(),
        ]);
    }
}
