<?php

namespace App\Http\Controllers\CustomerPortal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\Auth\ResetPasswordRequest;
use App\Services\CustomerPortal\Auth\CustomerPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly CustomerPasswordResetService $passwordResetService,
    ) {}

    public function create(): View
    {
        return view('customer-portal.auth.reset-password');
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $resetSucceeded = $this->passwordResetService->resetPassword(
            $request->submittedPassword(),
            $request,
        );

        if (! $resetSucceeded) {
            return redirect()
                ->route('customer.password.request')
                ->with('error', 'Your password reset session is invalid or has expired. Please request a new verification code.');
        }

        return redirect()
            ->route('customer.login')
            ->with('success', 'Your password has been reset successfully. Please sign in using your new password.');
    }
}
