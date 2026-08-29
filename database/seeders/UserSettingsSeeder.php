<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;

class UserSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = config('constants.user_settings', []);
        $users = User::where('delete_status', 0)->get();

        foreach ($users as $user) {
            foreach ($settings as $key => $label) {
                UserSetting::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'key' => $key,
                    ],
                    [
                        'value' => false,
                    ]
                );
            }
        }
    }
}
