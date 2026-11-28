<?php

namespace App\Http\Controllers\CustomerPortal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\Auth\LoginRequest;
use App\Services\CustomerPortal\Auth\CustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
    ) {}

    public function entry(): RedirectResponse
    {
        $guard = $this->authService->guardName();
        $customer = Auth::guard($guard)->user();

        if ($this->authService->isEligibleCustomer($customer)) {
            return redirect()->route('customer.dashboard');
        }

        return redirect()->route('customer.login');
    }

    public function create(): View
    {
        return view('customer-portal.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->authService->attemptLogin(
            $request->normalizedUsername(),
            $request->submittedPassword(),
            $request,
        );

        return redirect()->intended(route('customer.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout($request);

        return redirect()->route('customer.login')
            ->with('success', 'You have been logged out successfully.');
    }
}
