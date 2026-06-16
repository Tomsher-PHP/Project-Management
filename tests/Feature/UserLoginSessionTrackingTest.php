<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserLoginSessionTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_records_the_current_session(): void
    {
        Carbon::setTestNow('2026-06-13 12:00:00');

        $user = User::factory()->create();
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
            .'AppleWebKit/537.36 (KHTML, like Gecko) '
            .'Chrome/137.0.0.0 Safari/537.36';

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('User-Agent', $userAgent)
            ->post(route('login.post'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(route('user.workspace'));
        $this->assertAuthenticatedAs($user);

        $loginSession = UserLoginSession::query()->sole();

        $this->assertSame($user->id, $loginSession->user_id);
        $this->assertSame(session()->getId(), $loginSession->session_id);
        $this->assertTrue($loginSession->login_at->equalTo(now()));
        $this->assertNull($loginSession->logout_at);
        $this->assertSame('203.0.113.10', $loginSession->ip_address);
        $this->assertSame('Chrome', $loginSession->browser);
        $this->assertSame('Windows', $loginSession->platform);
        $this->assertSame('Desktop', $loginSession->device);
        $this->assertSame($userAgent, $loginSession->user_agent);
        $this->assertNull($loginSession->country);
        $this->assertNull($loginSession->city);
    }

    public function test_normal_logout_updates_the_matching_session(): void
    {
        Carbon::setTestNow('2026-06-13 12:00:00');

        $user = User::factory()->create();

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginSession = UserLoginSession::query()->sole();

        Carbon::setTestNow('2026-06-13 13:00:00');

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue($loginSession->fresh()->logout_at->equalTo(now()));
        $this->assertSame(1, UserLoginSession::query()->count());
    }

    public function test_new_login_closes_and_invalidates_the_previous_session(): void
    {
        Carbon::setTestNow('2026-06-13 12:00:00');

        $user = User::factory()->create();
        $previousSessionId = 'previous-session-id';
        $sessionHandler = app('session')->driver()->getHandler();
        $sessionHandler->write($previousSessionId, 'previous-session-data');

        $previousLogin = UserLoginSession::create([
            'user_id' => $user->id,
            'session_id' => $previousSessionId,
            'login_at' => now()->subHour(),
        ]);

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('user.workspace'));

        $this->assertNotNull($previousLogin->fresh()->logout_at);
        $this->assertSame('', $sessionHandler->read($previousSessionId));
        $this->assertTrue(
            UserLoginSession::query()
                ->where('user_id', $user->id)
                ->where('session_id', session()->getId())
                ->whereNull('logout_at')
                ->exists()
        );
        $this->assertSame(1, UserLoginSession::query()->whereNull('logout_at')->count());
    }

    public function test_stale_authenticated_session_is_logged_out_on_its_next_request(): void
    {
        $user = User::factory()->create();

        UserLoginSession::create([
            'user_id' => $user->id,
            'session_id' => 'newer-session-id',
            'login_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user.workspace'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_request_updates_only_the_current_session_activity(): void
    {
        Carbon::setTestNow('2026-06-13 14:00:00');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user);
        $currentSession = UserLoginSession::create([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'login_at' => now()->subHour(),
        ]);
        $otherSession = UserLoginSession::create([
            'user_id' => $otherUser->id,
            'session_id' => 'other-session-id',
            'login_at' => now()->subHour(),
            'last_activity_at' => now()->subMinutes(30),
        ]);

        $this->get(route('user.workspace'))->assertSuccessful();

        $this->assertTrue($currentSession->fresh()->last_activity_at->equalTo(now()));
        $this->assertTrue($otherSession->fresh()->last_activity_at->equalTo(now()->subMinutes(30)));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
