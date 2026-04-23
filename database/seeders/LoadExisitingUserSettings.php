<?php

namespace Database\Seeders;

use App\Models\UserNotificationSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserGeneralSetting;

class LoadExisitingUserSettings extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        UserNotificationSetting::truncate();
        UserGeneralSetting::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $users = User::all();

        foreach ($users as $user) {

            // General Settings
            DB::table('user_general_settings')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'kanban_view' => 'agile',
                    'theme' => 'light',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Notification Settings
            $actions = config('notification_settings'); // your config array

            foreach ($actions as $setting) {
                DB::table('user_notification_settings')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'action' => $setting['action']
                    ],
                    [
                        'in_app' => 1,
                        'mail' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
