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

        foreach ($permissions as $permission) {
            Permission::updateOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ], [
                'label' => $permission['label'] ?? null,
                'sort_order' => $permission['sort_order'],
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
