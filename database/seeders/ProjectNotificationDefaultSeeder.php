<?php

namespace Database\Seeders;

use App\Models\ProjectNotificationDefault;
use App\Models\ProjectNotificationSetting;
use Illuminate\Database\Seeder;

class ProjectNotificationDefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timelineKey = ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON;
        $hostingKey = ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON;

        $defaults = [
            // 1. Timeline Ending Soon - Agile: disabled by default
            [
                'setting_key' => $timelineKey,
                'conditions' => ['project_flow' => 'agile'],
                'setting_value' => ['is_enabled' => false],
            ],
            // 2. Timeline Ending Soon - Linear: enabled by default
            [
                'setting_key' => $timelineKey,
                'conditions' => ['project_flow' => 'linear'],
                'setting_value' => ['is_enabled' => true],
            ],
            // 3. Server & Hosting Expiry - Fallback/General: enabled by default
            [
                'setting_key' => $hostingKey,
                'conditions' => [],
                'setting_value' => ['is_enabled' => true],
            ],
        ];

        foreach ($defaults as $default) {
            $normalizedConditions = self::normalizeConditions($default['conditions']);
            $existing = ProjectNotificationDefault::where('setting_key', $default['setting_key'])->get();

            $match = $existing->first(function ($item) use ($normalizedConditions) {
                return self::normalizeConditions($item->conditions ?? []) === $normalizedConditions;
            });

            if ($match) {
                $match->update([
                    'setting_value' => $default['setting_value'],
                ]);
            } else {
                ProjectNotificationDefault::create([
                    'setting_key' => $default['setting_key'],
                    'conditions' => $default['conditions'],
                    'setting_value' => $default['setting_value'],
                ]);
            }
        }
    }

    /**
     * Normalize conditions array by sorting keys recursively for deterministic comparison.
     */
    public static function normalizeConditions(array $conditions): array
    {
        ksort($conditions);
        foreach ($conditions as $k => $v) {
            if (is_array($v)) {
                $conditions[$k] = self::normalizeConditions($v);
            }
        }
        return $conditions;
    }
}
