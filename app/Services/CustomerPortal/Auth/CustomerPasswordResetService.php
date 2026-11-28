<?php

namespace App\Services\CustomerPortal\Auth;

use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerPasswordResetService
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
        private readonly CustomerAuthService $authService,
    ) {}

    /**
     * Reset the customer's password using the server-side verified reset authorization.
     */
    public function resetPassword(string $newPassword, Request $request): bool
    {
        $authContext = $request->hasSession()
            ? $request->session()->get(CustomerOtpService::SESSION_AUTHORIZATION_KEY)
            : null;

        if (! is_array($authContext) || empty($authContext['token'])) {
            $this->otpService->clearRecoverySession($request);

            return false;
        }

        $resetTokenHash = $this->otpService->hashResetToken((string) $authContext['token']);
        $sessionFingerprint = isset($authContext['fingerprint']) && is_string($authContext['fingerprint'])
            ? $authContext['fingerprint']
            : null;

        $lookup = CustomerPasswordReset::query()
            ->where('reset_token_hash', $resetTokenHash)
            ->first(['id', 'customer_id']);

        if (! $lookup) {
            $this->otpService->clearRecoverySession($request);

            return false;
        }

        $result = DB::transaction(function () use ($lookup, $resetTokenHash, $sessionFingerprint, $newPassword) {
            // Consistent locking order: Customer first, then CustomerPasswordReset.
            $customer = Customer::query()
                ->whereKey($lookup->customer_id)
                ->lockForUpdate()
                ->first();

            $reset = CustomerPasswordReset::query()
                ->where('reset_token_hash', $resetTokenHash)
                ->lockForUpdate()
                ->first();

            if (! $reset || ! $this->otpService->isAuthorizationUsable($reset, $customer, $sessionFingerprint)) {
                if ($reset && $reset->used_at === null && $reset->invalidated_at === null) {
                    $reset->update(['invalidated_at' => now()]);
                }

                return ['success' => false, 'customer_id' => null];
            }

            $now = now();

            // Targeted query-builder update avoids overwriting added_by/updated_by, last_login_at, or triggering Data Sync.
            Customer::query()
                ->whereKey($customer->getKey())
                ->update([
                    'password' => Hash::make($newPassword),
                    'password_set_at' => $now,
                ]);

            $reset->update([
                'used_at' => $now,
            ]);

            CustomerPasswordReset::query()
                ->where('customer_id', $customer->getKey())
                ->where('id', '!=', $reset->id)
                ->whereNull('invalidated_at')
                ->whereNull('used_at')
                ->update(['invalidated_at' => $now]);

            return ['success' => true, 'customer_id' => $customer->getKey()];
        });

        if (! ($result['success'] ?? false)) {
            $this->otpService->clearRecoverySession($request);

            return false;
        }

        $this->authService->revokeCustomerSessions($result['customer_id']);
        $this->otpService->clearRecoverySession($request);

        $guard = $this->authService->guardName();
        if (Auth::guard($guard)->check()) {
            Auth::guard($guard)->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return true;
    }
}
