<?php

namespace App\Http\Middleware\CustomerPortal;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomer
{
    /**
     * Ensure the incoming request is authenticated on the customer guard.
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = (string) config('customer_portal.authentication.guard', 'customer');

        if (Auth::guard($guard)->check()) {
            return $next($request);
        }

        throw new AuthenticationException(
            'Unauthenticated.',
            [$guard],
            $this->redirectTo($request),
        );
    }

    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        return Route::has('customer.login')
            ? route('customer.login')
            : url('/c-portal/login');
    }
}
