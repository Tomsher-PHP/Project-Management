<?php

namespace App\Http\Controllers\CustomerPortal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\Auth\VerifyOtpRequest;
use App\Services\CustomerPortal\Auth\CustomerOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->otpService->hasResetAuthorization($request)) {
            if ($this->otpService->validateResetAuthorization($request) !== null) {
                return redirect()->route('customer.password.reset');
            }

            return redirect()
                ->route('customer.password.request')
                ->with('error', 'Your password reset session is invalid or has expired. Please request a new verification code.');
        }

        if (! $this->otpService->hasChallengeContext($request)) {
            return redirect()
                ->route('customer.password.request')
                ->with('error', 'Please enter your username to request a verification code.');
        }

        return view('customer-portal.auth.verify-otp', [
            'otpLength' => (int) config('customer_portal.password_reset.otp_length', 6),
            'resendCooldownSeconds' => $this->otpService->getRemainingResendSeconds($request),
        ]);
    }

    public function store(VerifyOtpRequest $request): RedirectResponse
    {
        $this->otpService->verifyOtp($request->submittedOtp(), $request);

        return redirect()
            ->route('customer.password.reset')
            ->with('success', 'Verification code confirmed. Please choose a new password.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $message = $this->otpService->resendOtp($request);

        if ($message === null) {
            return redirect()
                ->route('customer.password.request')
                ->with('error', 'Your recovery session has expired. Please request a new verification code.');
        }

        return redirect()
            ->route('customer.password.otp')
            ->with('success', $message);
    }
}
