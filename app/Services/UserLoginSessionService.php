<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserLoginSessionService
{
    public function recordLogin(User $user, Request $request): void
    {
        try {
            $userAgent = $request->userAgent();

            UserLoginSession::create([
                'user_id' => $user->id,
                'session_id' => $request->session()->getId(),
                'login_at' => now(),
                'logout_at' => null,
                'ip_address' => $request->ip(),
                'country' => null,
                'city' => null,
                'browser' => $this->detectBrowser($userAgent),
                'platform' => $this->detectPlatform($userAgent),
                'device' => $this->detectDevice($userAgent),
                'user_agent' => $userAgent,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to record user login session.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function recordLogout(Request $request): void
    {
        try {
            $loginSession = UserLoginSession::query()
                ->where('session_id', $request->session()->getId())
                ->whereNull('logout_at')
                ->latest('login_at')
                ->first();

            $loginSession?->update(['logout_at' => now()]);
        } catch (Throwable $exception) {
            Log::warning('Unable to record user logout session.', [
                'session_id' => $request->session()->getId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function detectBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            preg_match('/Edg(?:e|A|iOS)?\//i', $userAgent) === 1 => 'Edge',
            preg_match('/(?:OPR|Opera)\//i', $userAgent) === 1 => 'Opera',
            preg_match('/(?:Chrome|CriOS)\//i', $userAgent) === 1 => 'Chrome',
            preg_match('/(?:Firefox|FxiOS)\//i', $userAgent) === 1 => 'Firefox',
            preg_match('/Version\/.*Safari\//i', $userAgent) === 1 => 'Safari',
            preg_match('/(?:MSIE |Trident\/)/i', $userAgent) === 1 => 'Internet Explorer',
            default => 'Other',
        };
    }

    private function detectPlatform(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            preg_match('/Windows NT/i', $userAgent) === 1 => 'Windows',
            preg_match('/Android/i', $userAgent) === 1 => 'Android',
            preg_match('/iPhone|iPad|iPod/i', $userAgent) === 1 => 'iOS',
            preg_match('/Macintosh|Mac OS X/i', $userAgent) === 1 => 'macOS',
            preg_match('/Linux/i', $userAgent) === 1 => 'Linux',
            default => 'Other',
        };
    }

    private function detectDevice(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            preg_match('/bot|crawler|spider|slurp/i', $userAgent) === 1 => 'Bot',
            preg_match('/iPad|Tablet/i', $userAgent) === 1 => 'Tablet',
            preg_match('/Android/i', $userAgent) === 1
                && preg_match('/Mobile/i', $userAgent) !== 1 => 'Tablet',
            preg_match('/Mobile|Android|iPhone|iPod/i', $userAgent) === 1 => 'Mobile',
            default => 'Desktop',
        };
    }
}
