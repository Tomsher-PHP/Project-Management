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

        /*
         * Permissions that should be removed from the system.
         */
        $permissionsToRemove = [
            'project_category.view',
            'project_category.create',
            'project_category.edit',
            'project_category.delete',
            'project_status.view',
            'project_status.create',
            'project_status.edit',
            'project_status.delete',
            'project_stage.view',
            'project_stage.create',
            'project_stage.edit',
            'project_stage.delete',
            'agile_milestone.view',
            'agile_milestone.create',
            'agile_milestone.edit',
            'agile_milestone.delete',
            'agile_sprint.view',
            'agile_sprint.create',
            'agile_sprint.edit',
            'agile_sprint.delete',
            'sprint_group.view',
            'sprint_group.create',
            'sprint_group.edit',
            'sprint_group.delete',
        ];
        foreach ($permissionsToRemove as $permissionName) {
            Permission::where('name', $permissionName)
                ->where('guard_name', 'web')
                ->delete();
        }

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
