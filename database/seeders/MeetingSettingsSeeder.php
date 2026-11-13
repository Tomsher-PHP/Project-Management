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
            ['name' => 'Client Meeting', 'color' => '#F59E0B', 'sort_order' => 1, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'General Meeting', 'color' => '#64748B', 'sort_order' => 2, 'is_default' => 1, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Project Meeting', 'color' => '#3B82F6', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Team Meeting', 'color' => '#10B981', 'sort_order' => 4, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => '1-on-1', 'color' => '#8B5CF6', 'sort_order' => 5, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
        ];

        foreach ($types as $type) {
            $record = MeetingType::withTrashed()->where('name', $type['name'])->first();
            if ($record) {
                $record->fill($type);
                if (empty($record->code)) {
                    $record->code = MeetingType::generateUniqueCode();
                }
                $record->save();

                continue;
            }

            MeetingType::create($type + ['code' => MeetingType::generateUniqueCode()]);
        }

        $locations = [
            ['name' => 'Office', 'sort_order' => 1, 'is_default' => 1, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Online', 'sort_order' => 2, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
            ['name' => 'Client Office', 'sort_order' => 3, 'is_default' => 0, 'is_active' => 1, 'is_system' => 1],
        ];

        foreach ($locations as $location) {
            $record = MeetingLocation::withTrashed()->where('name', $location['name'])->first();
            if ($record) {
                $record->fill($location);
                if (empty($record->code)) {
                    $record->code = MeetingLocation::generateUniqueCode();
                }
                $record->save();

                continue;
            }

            MeetingLocation::create($location + ['code' => MeetingLocation::generateUniqueCode()]);
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
            $record = MeetingTag::withTrashed()->where('name', $tag['name'])->first();
            if ($record) {
                $record->fill($tag);
                if (empty($record->code)) {
                    $record->code = MeetingTag::generateUniqueCode();
                }
                $record->save();

                continue;
            }

            MeetingTag::create($tag + ['code' => MeetingTag::generateUniqueCode()]);
        }
    }
}
