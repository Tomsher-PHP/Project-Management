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

        if ($hasActiveSession || ! UserLoginSession::query()->where('user_id', $userId)->exists()) {
            return $next($request);
        }

        Auth::logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
