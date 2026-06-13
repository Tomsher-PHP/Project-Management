<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserLoginSession;
use App\Services\UserLoginActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserLoginActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_only_returns_sessions_for_accessible_users(): void
    {
        $viewer = User::factory()->create();

        Auth::login($viewer);
        $accessibleUser = User::factory()->create();
        Auth::logout();

        $inaccessibleUser = User::factory()->create();

        $selfSession = UserLoginSession::create([
            'user_id' => $viewer->id,
            'session_id' => 'self-session',
            'login_at' => now(),
        ]);
        $olderAccessibleSession = UserLoginSession::create([
            'user_id' => $accessibleUser->id,
            'session_id' => 'older-accessible-session',
            'login_at' => now()->subHour(),
        ]);
        $newerAccessibleSession = UserLoginSession::create([
            'user_id' => $accessibleUser->id,
            'session_id' => 'newer-accessible-session',
            'login_at' => now()->subMinute(),
        ]);
        UserLoginSession::create([
            'user_id' => $inaccessibleUser->id,
            'session_id' => 'inaccessible-session',
            'login_at' => now(),
        ]);

        $activities = app(UserLoginActivityService::class)->getActivities($viewer, [], 10);

        $this->assertSame(
            [$selfSession->id, $newerAccessibleSession->id, $olderAccessibleSession->id],
            $activities->pluck('id')->all()
        );

        $this->assertSame(
            collect([$viewer->id, $accessibleUser->id])->sort()->values()->all(),
            app(UserLoginActivityService::class)
                ->getAccessibleUsers($viewer)
                ->pluck('id')
                ->sort()
                ->values()
                ->all()
        );
    }

    public function test_user_filter_cannot_expose_an_inaccessible_users_sessions(): void
    {
        $viewer = User::factory()->create();
        $inaccessibleUser = User::factory()->create();

        UserLoginSession::create([
            'user_id' => $inaccessibleUser->id,
            'session_id' => 'inaccessible-session',
            'login_at' => now(),
        ]);

        $activities = app(UserLoginActivityService::class)->getActivities($viewer, [
            'user_id' => [$inaccessibleUser->id],
        ], 10);

        $this->assertSame(0, $activities->total());
    }
}
