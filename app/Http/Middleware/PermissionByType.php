<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        if($user->user_type == "super_admin") {
            return $next($request);
        }
        
        if (!$user || !$user->canByUserType($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
