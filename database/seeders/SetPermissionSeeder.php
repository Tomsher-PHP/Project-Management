<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = config('system_permissions');

        foreach ($permissions as $permissionData) {

            $permission = Permission::updateOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'label' => $permissionData['label'] ?? null,
                    'sort_order' => $permissionData['sort_order'],
                ]
            );

            /*
             * Only apply default_checked when the permission
             * is newly created.
             *
             * Existing permissions and their role assignments
             * are never changed by the seeder.
             */
            if (
                $permission->wasRecentlyCreated &&
                ($permissionData['default_checked'] ?? false) === true
            ) {
                $roles = Role::where('guard_name', 'web')->get();

                foreach ($roles as $role) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
