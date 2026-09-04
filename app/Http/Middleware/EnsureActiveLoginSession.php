<?php

namespace App\Http\Middleware;

use App\Models\UserLoginSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveLoginSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->trackValidGetUrl($request);

        if (! Auth::check()) {
            return $next($request);
        }

        $userId = Auth::id();
        $sessionId = $request->session()->getId();
        $hasActiveSession = UserLoginSession::query()
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->whereNull('logout_at')
            ->exists();

        if ($hasActiveSession) {
            UserLoginSession::query()
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->whereNull('logout_at')
                ->update(['last_activity_at' => now()]);

            return $next($request);
        }

        if (! UserLoginSession::query()->where('user_id', $userId)->exists()) {
            return $next($request);
        }

        Auth::logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function trackValidGetUrl(Request $request): void
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson() || $request->pjax()) {
            return;
        }

        $ignoredPatterns = [
            'api/*',
            '*/refresh*',
            '*/chart/*',
            '*/summary*',
            '*/worked-time*',
            '*/running-tasks*',
            '*/tile-details*',
            'livewire/*',
            '_debugbar/*',
        ];

        foreach ($ignoredPatterns as $pattern) {
            if ($request->is($pattern)) {
                return;
            }
        }

        $url = $request->fullUrl();

        if (! $request->hasSession()) {
            return;
        }

        $history = $request->session()->get('valid_get_history', []);
        if (! is_array($history)) {
            $history = [];
        }

        if (empty($history) || end($history) !== $url) {
            $history[] = $url;
            if (count($history) > 10) {
                array_shift($history);
            }
            $request->session()->put('valid_get_history', array_values($history));
        }

        $request->session()->put('last_valid_get_url', $url);
    }
}
