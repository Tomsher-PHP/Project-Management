<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // truncate existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        Permission::truncate();
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $permissions = config('system_permissions');

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'guard_name' => 'web',
                'sort_order' => $permission['sort_order'],
            ]);
        }

        // Create Roles
        $owner = Role::create(['name' => 'Owner']);
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Manager']);
        Role::create(['name' => 'Team Leader']);
        Role::create(['name' => 'Team Member']);

        // Assign Permissions to Roles
        $owner->givePermissionTo(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
