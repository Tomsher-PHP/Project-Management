<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // truncate existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        Permission::truncate();
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $permissions = config('system_permissions');
        $userTypes = array_keys(config('constants.user_types'));

        foreach ($userTypes as $userType) {
            // if (in_array($userType, ['normal_user'])) {
            //     $permissions = array_filter($permissions, function ($permission) {
            //         return str_contains($permission, 'task') || str_contains($permission, 'project');
            //     });
            // }

            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'user_type' => $userType,
                ]);
            }
        }

        // Create Roles
        $superAdmin = Role::create(['name' => 'Super Admin', 'user_type' => 'super_admin']);
        Role::create(['name' => 'Admin', 'user_type' => 'admin']);
        Role::create(['name' => 'Manager', 'user_type' => 'manager']);
        Role::create(['name' => 'Team Leader', 'user_type' => 'team_leader']);
        Role::create(['name' => 'Developer', 'user_type' => 'normal_user']);

        // Assign Permissions to Roles
        $superAdmin->givePermissionTo(Permission::where('user_type', 'super_admin')->get());
    }
}
