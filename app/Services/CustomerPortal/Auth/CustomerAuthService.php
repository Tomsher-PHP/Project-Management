<?php

namespace App\Services\CustomerPortal\Auth;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerAuthService
{
    public const SESSION_FINGERPRINT_KEY = 'customer_portal.credential_fingerprint';

    public const SESSION_AUTHENTICATED_AT_KEY = 'customer_portal.authenticated_at';

    /**
     * Dummy bcrypt hash used to equalize timing when a username does not exist.
     */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function attemptLogin(string $username, string $password, Request $request): Customer
    {
        $guard = $this->guardName();
        $throttleKey = $this->throttleKey($username, $request);
        $maxAttempts = (int) config('customer_portal.authentication.max_login_attempts', 5);
        $decaySeconds = (int) config('customer_portal.authentication.login_decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->redirectTo(route('customer.login'));
        }

        $customer = Customer::query()
            ->where('username', $username)
            ->first();

        $hashedPassword = $customer?->password ?: self::DUMMY_HASH;
        $passwordMatches = Hash::check($password, $hashedPassword);

        if (! $this->isEligibleCustomer($customer) || ! $passwordMatches) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'username' => 'The provided credentials are incorrect.',
            ])->redirectTo(route('customer.login'));
        }

        RateLimiter::clear($throttleKey);

        // Perform targeted password rehash without firing Customer audit/DataSync observers.
        if (config('hashing.rehash_on_login', true) && Hash::needsRehash($customer->password)) {
            $newHash = Hash::make($password);
            Customer::query()
                ->whereKey($customer->getKey())
                ->update(['password' => $newHash]);
            $customer->forceFill(['password' => $newHash])->syncOriginalAttribute('password');
        }

        Auth::guard($guard)->login($customer);
        $request->session()->regenerate();

        // Store credential fingerprint after any rehash and session regeneration.
        $request->session()->put(self::SESSION_FINGERPRINT_KEY, $this->credentialFingerprint($customer));
        $request->session()->put(self::SESSION_AUTHENTICATED_AT_KEY, microtime(true));

        // Update last_login_at directly so added_by, updated_by, activity logs, and Data Sync are untouched.
        $now = now();
        Customer::query()
            ->whereKey($customer->getKey())
            ->update(['last_login_at' => $now]);
        $customer->forceFill(['last_login_at' => $now])->syncOriginalAttribute('last_login_at');

        return $customer;
    }

    public function logout(Request $request): void
    {
        $guard = $this->guardName();

        if (Auth::guard($guard)->check()) {
            Auth::guard($guard)->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    public function isEligibleCustomer(?Customer $customer): bool
    {
        if (! $customer instanceof Customer) {
            return false;
        }

        if (method_exists($customer, 'trashed') && $customer->trashed()) {
            return false;
        }

        if (! $customer->is_active || ! $customer->login_enabled) {
            return false;
        }

        return ! empty($customer->password);
    }

    public function credentialFingerprint(Customer $customer): string
    {
        $payload = implode('|', [
            (string) $customer->getKey(),
            (string) $customer->username,
            (string) $customer->getRawOriginal('password') ?: (string) $customer->password,
            $customer->login_enabled ? '1' : '0',
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function hasValidSessionFingerprint(Customer $customer, Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $storedFingerprint = $request->session()->get(self::SESSION_FINGERPRINT_KEY);
        if (! is_string($storedFingerprint) || $storedFingerprint === '') {
            return false;
        }

        $expectedFingerprint = $this->credentialFingerprint($customer);
        if (! hash_equals($expectedFingerprint, $storedFingerprint)) {
            return false;
        }

        $authenticatedAt = $request->session()->get(self::SESSION_AUTHENTICATED_AT_KEY);
        $revokedBefore = Cache::get($this->revokedBeforeCacheKey($customer->getKey()));

        if ($revokedBefore !== null) {
            if (! is_numeric($authenticatedAt) || (float) $authenticatedAt <= (float) $revokedBefore) {
                return false;
            }
        }

        return true;
    }

    public function revokeCustomerSessions(int|string $customerId): void
    {
        Cache::forever($this->revokedBeforeCacheKey($customerId), microtime(true));
    }

    public function revokedBeforeCacheKey(int|string $customerId): string
    {
        return "customer_portal:revoked_before:{$customerId}";
    }

    public function guardName(): string
    {
        return (string) config('customer_portal.authentication.guard', 'customer');
    }

    public function throttleKey(string $username, Request $request): string
    {
        return 'customer-portal-login:' . Str::transliterate(sha1($username . '|' . $request->ip()));
    }
}
