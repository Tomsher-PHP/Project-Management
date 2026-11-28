<?php

namespace App\Http\Controllers\CustomerPortal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\Auth\ForgotPasswordRequest;
use App\Services\CustomerPortal\Auth\CustomerOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
    ) {}

    public function create(): View
    {
        return view('customer-portal.auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $message = $this->otpService->requestPasswordReset(
            $request->normalizedUsername(),
            $request,
        );

        return redirect()
            ->route('customer.password.otp')
            ->with('success', $message);
    }
}
