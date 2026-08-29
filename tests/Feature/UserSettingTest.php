<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_constant_setting_key_exists()
    {
        $this->assertEquals('daily_work_hours_warning_mail', config('constants.daily_work_hours_warning_mail'));
    }

    public function test_user_settings_relationship()
    {
        $user = User::factory()->create();

        $setting = UserSetting::create([
            'user_id' => $user->id,
            'key' => config('constants.daily_work_hours_warning_mail'),
            'value' => false,
        ]);

        $this->assertCount(1, $user->settings);
        $this->assertFalse($user->settings->first()->value);
    }

    public function test_update_general_settings_disables_warning_mail()
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('users.general.settings'), [
            'user_id' => $user->id,
            'field' => config('constants.daily_work_hours_warning_mail'),
            'value' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'key' => config('constants.daily_work_hours_warning_mail'),
            'value' => 0,
        ]);
    }

    public function test_update_general_settings_enables_warning_mail()
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('users.general.settings'), [
            'user_id' => $user->id,
            'field' => config('constants.daily_work_hours_warning_mail'),
            'value' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'key' => config('constants.daily_work_hours_warning_mail'),
            'value' => 1,
        ]);
    }
}
