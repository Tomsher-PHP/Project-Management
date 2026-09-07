<?php

namespace Database\Seeders;

use App\Models\MeetingLocation;
use App\Models\MeetingTag;
use App\Models\MeetingType;
use Illuminate\Database\Seeder;

class MeetingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Project Meeting', 'sort_order' => 1, 'is_default' => 1, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Team Meeting', 'sort_order' => 2, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Client Meeting', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => '1-on-1', 'sort_order' => 4, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Daily Standup', 'sort_order' => 5, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Sprint Planning', 'sort_order' => 6, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Sprint Review', 'sort_order' => 7, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Retrospective', 'sort_order' => 8, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'General Meeting', 'sort_order' => 9, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
        ];

        foreach ($types as $type) {
            MeetingType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }

        $locations = [
            ['name' => 'Office', 'sort_order' => 1, 'is_default' => 1, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Conference Room', 'sort_order' => 2, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Board Room', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Client Office', 'sort_order' => 4, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Online', 'sort_order' => 5, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
        ];

        foreach ($locations as $location) {
            MeetingLocation::updateOrCreate(
                ['name' => $location['name']],
                $location
            );
        }

        $tags = [
            ['name' => 'Client', 'sort_order' => 1, 'is_default' => 1, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Internal', 'sort_order' => 2, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Planning', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Review', 'sort_order' => 4, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Management', 'sort_order' => 5, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Follow-up', 'sort_order' => 6, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
        ];

        foreach ($tags as $tag) {
            MeetingTag::updateOrCreate(
                ['name' => $tag['name']],
                $tag
            );
        }
    }
}
