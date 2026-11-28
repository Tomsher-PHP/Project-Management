<?php

namespace App\Http\Middleware\CustomerPortal;

use App\Services\CustomerPortal\Auth\CustomerOtpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordResetVerified
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $reset = $this->otpService->validateResetAuthorization($request);

        if (! $reset) {
            $message = 'Your password reset session is invalid or has expired. Please request a new verification code.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()
                ->route('customer.password.request')
                ->with('error', $message);
        }

        return $next($request);
    }
}
