<?php

namespace App\Http\Middleware\CustomerPortal;

use App\Models\Customer;
use App\Services\CustomerPortal\Auth\CustomerAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerLoginEnabled
{
    public function __construct(
        private readonly CustomerAuthService $authService,
    ) {}

    /**
     * Ensure the authenticated customer remains active, permitted, and has a valid credential fingerprint.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = $this->authService->guardName();
        $customer = Auth::guard($guard)->user();

        if (! $this->isEligibleAndValidSession($customer, $request)) {
            $this->revokeCustomerSession($request, $guard);

            $message = 'Your customer portal session is no longer valid. Please sign in again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], Response::HTTP_FORBIDDEN);
            }

            $loginUrl = Route::has('customer.login')
                ? route('customer.login')
                : url('/c-portal/login');

            return redirect()->to($loginUrl)->with('error', $message);
        }

        return $next($request);
    }

    protected function isEligibleAndValidSession(mixed $customer, Request $request): bool
    {
        if (! $customer instanceof Customer) {
            return false;
        }

        if (! $this->authService->isEligibleCustomer($customer)) {
            return false;
        }

        return $this->authService->hasValidSessionFingerprint($customer, $request);
    }

    protected function revokeCustomerSession(Request $request, string $guard): void
    {
        Auth::guard($guard)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
