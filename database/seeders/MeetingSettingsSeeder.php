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
            ['name' => 'Project Meeting', 'color' => '#3B82F6', 'sort_order' => 1, 'is_default' => 1, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Team Meeting', 'color' => '#10B981', 'sort_order' => 2, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Client Meeting', 'color' => '#F59E0B', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => '1-on-1', 'color' => '#8B5CF6', 'sort_order' => 4, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Daily Standup', 'color' => '#06B6D4', 'sort_order' => 5, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Sprint Planning', 'color' => '#6366F1', 'sort_order' => 6, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Sprint Review', 'color' => '#EC4899', 'sort_order' => 7, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Retrospective', 'color' => '#14B8A6', 'sort_order' => 8, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'General Meeting', 'color' => '#64748B', 'sort_order' => 9, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
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
            ['name' => 'Client', 'color' => '#EF4444', 'sort_order' => 1, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Internal', 'color' => '#3B82F6', 'sort_order' => 2, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Planning', 'color' => '#F59E0B', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Review', 'color' => '#10B981', 'sort_order' => 4, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Management', 'color' => '#8B5CF6', 'sort_order' => 5, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Follow-up', 'color' => '#6366F1', 'sort_order' => 6, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
        ];

        foreach ($tags as $tag) {
            MeetingTag::updateOrCreate(
                ['name' => $tag['name']],
                $tag
            );
        }
    }
}
