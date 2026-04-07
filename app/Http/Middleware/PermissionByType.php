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

        if (!$user || !$user->can($permission)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'You are not allowed to access this page.'], 403);
            }

            throw new UnauthorizedActionException();
        }

        return $next($request);
    }
}
