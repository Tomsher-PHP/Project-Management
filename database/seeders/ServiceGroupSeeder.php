<?php

namespace Database\Seeders;

use App\Models\ServiceGroup;
use Illuminate\Database\Seeder;

class ServiceGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = ServiceGroup::getPredefinedGroups();

        $sortOrder = 1;
        foreach ($groups as $code => $name) {
            ServiceGroup::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                ]
            );
        }
    }
}
