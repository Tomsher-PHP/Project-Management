<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = config('system_permissions');

        foreach ($permissions as $index => $permission) {
            Permission::updateOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ], [
                'sort_order' => $index + 1,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
