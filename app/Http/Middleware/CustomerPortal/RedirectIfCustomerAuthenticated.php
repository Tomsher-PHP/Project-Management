<?php

namespace App\Http\Middleware\CustomerPortal;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfCustomerAuthenticated
{
    /**
     * Redirect authenticated customers away from guest-only Customer Portal routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = (string) config('customer_portal.authentication.guard', 'customer');

        if (Auth::guard($guard)->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Already authenticated.',
                ], Response::HTTP_FORBIDDEN);
            }

            $target = Route::has('customer.dashboard')
                ? route('customer.dashboard')
                : url('/c-portal/dashboard');

            return redirect()->to($target);
        }

        return $next($request);
    }
}
