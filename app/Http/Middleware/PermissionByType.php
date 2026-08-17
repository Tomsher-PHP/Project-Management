<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\UnauthorizedActionException;

class PermissionByType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission = null): Response
    {
        $user = auth()->user();

        if ($user->is_super_admin) {
            return $next($request);
        }

        if (!$user) {
            return $this->deny($request);
        }

        $permissions = collect(explode('|', (string) $permission))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        if (empty($permissions) || !$user->canAny($permissions)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function deny(Request $request): Response
    {
        $message = 'You do not have permission to perform this action.';

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => $message,
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
