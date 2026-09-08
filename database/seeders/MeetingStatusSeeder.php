<?php

namespace Database\Seeders;

use App\Models\MeetingStatus;
use Illuminate\Database\Seeder;

class MeetingStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Scheduled',
                'code' => MeetingStatus::STATUS_SCHEDULED,
                'color' => '#3B82F6',
                'type' => MeetingStatus::TYPE_OPEN,
                'sort_order' => 1,
                'is_default' => 1,
                'is_completed' => 0,
                'is_system' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'Rescheduled',
                'code' => MeetingStatus::STATUS_RESCHEDULED,
                'color' => '#F59E0B',
                'type' => MeetingStatus::TYPE_OPEN,
                'sort_order' => 2,
                'is_default' => 0,
                'is_completed' => 0,
                'is_system' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'In Progress',
                'code' => MeetingStatus::STATUS_IN_PROGRESS,
                'color' => '#06B6D4',
                'type' => MeetingStatus::TYPE_IN_PROGRESS,
                'sort_order' => 3,
                'is_default' => 0,
                'is_completed' => 0,
                'is_system' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'Completed',
                'code' => MeetingStatus::STATUS_COMPLETED,
                'color' => '#10B981',
                'type' => MeetingStatus::TYPE_COMPLETED,
                'sort_order' => 4,
                'is_default' => 0,
                'is_completed' => 1,
                'is_system' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'Cancelled',
                'code' => MeetingStatus::STATUS_CANCELLED,
                'color' => '#EF4444',
                'type' => MeetingStatus::TYPE_CANCELLED,
                'sort_order' => 5,
                'is_default' => 0,
                'is_completed' => 0,
                'is_system' => 1,
                'is_active' => 1,
            ],
        ];

        foreach ($statuses as $status) {
            MeetingStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
