<?php

namespace App\Services\CustomerPortal\Auth;

use App\Jobs\CustomerPortal\SendPasswordResetOtpJob;
use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOtpService
{
    public const GENERIC_CONFIRMATION_MESSAGE = 'If your account is eligible for password recovery, a verification code will be sent to your registered email address.';

    public const GENERIC_VERIFICATION_ERROR = 'Invalid or expired verification code. Please try again or request a new code.';

    public const SESSION_CHALLENGE_KEY = 'customer_portal.password_reset_challenge';

    public const SESSION_AUTHORIZATION_KEY = 'customer_portal.password_reset_authorization';

    public function requestPasswordReset(string $username, Request $request): string
    {
        $normalizedUsername = $this->normalizeUsername($username);
        if ($normalizedUsername === '') {
            return self::GENERIC_CONFIRMATION_MESSAGE;
        }

        $maxPerHour = (int) config('customer_portal.password_reset.max_requests_per_hour', 5);
        $resendInterval = (int) config('customer_portal.password_reset.resend_interval_seconds', 60);

        $cooldownKey = $this->cooldownThrottleKey($normalizedUsername);
        $hourlyKey = $this->hourlyThrottleKey($normalizedUsername);

        if (RateLimiter::tooManyAttempts($cooldownKey, 1) || RateLimiter::tooManyAttempts($hourlyKey, $maxPerHour)) {
            $this->ensureOpaqueChallengeContext($request);

            return self::GENERIC_CONFIRMATION_MESSAGE;
        }

        // Record the attempt regardless of customer existence so timing and limits cannot enumerate accounts.
        RateLimiter::hit($cooldownKey, $resendInterval);
        RateLimiter::hit($hourlyKey, 3600);

        $otpLength = (int) config('customer_portal.password_reset.otp_length', 6);
        $expiryMinutes = (int) config('customer_portal.password_reset.otp_expiry_minutes', 5);

        $challengeToken = null;
        $issuedAt = null;
        $fingerprint = null;

        DB::transaction(function () use (
            $normalizedUsername,
            $otpLength,
            $expiryMinutes,
            &$challengeToken,
            &$issuedAt,
            &$fingerprint
        ) {
            $customer = Customer::query()
                ->where('username', $normalizedUsername)
                ->lockForUpdate()
                ->first();

            if (! $this->isEligibleForPasswordReset($customer)) {
                return;
            }

            [$challengeToken, $issuedAt, $fingerprint] = $this->issueChallengeForCustomer($customer, $otpLength, $expiryMinutes);
        });

        if ($request->hasSession()) {
            $request->session()->forget(self::SESSION_AUTHORIZATION_KEY);
            $request->session()->put(self::SESSION_CHALLENGE_KEY, [
                'token' => $challengeToken ?? bin2hex(random_bytes(32)),
                'issued_at' => $issuedAt ?? now()->timestamp,
                'fingerprint' => $fingerprint,
            ]);
        }

        return self::GENERIC_CONFIRMATION_MESSAGE;
    }

    public function hasChallengeContext(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $challenge = $request->session()->get(self::SESSION_CHALLENGE_KEY);

        return is_array($challenge) && ! empty($challenge['token']);
    }

    public function hasResetAuthorization(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $auth = $request->session()->get(self::SESSION_AUTHORIZATION_KEY);

        return is_array($auth) && ! empty($auth['token']);
    }

    public function getRemainingResendSeconds(Request $request): int
    {
        $resendInterval = (int) config('customer_portal.password_reset.resend_interval_seconds', 60);
        $challenge = $request->session()->get(self::SESSION_CHALLENGE_KEY);

        if (! is_array($challenge) || empty($challenge['issued_at'])) {
            return 0;
        }

        $elapsed = max(0, now()->timestamp - (int) $challenge['issued_at']);

        return max(0, $resendInterval - $elapsed);
    }

    public function verifyOtp(string $otp, Request $request): void
    {
        $challenge = $request->hasSession() ? $request->session()->get(self::SESSION_CHALLENGE_KEY) : null;

        if (! is_array($challenge) || empty($challenge['token'])) {
            throw ValidationException::withMessages([
                'otp' => self::GENERIC_VERIFICATION_ERROR,
            ])->redirectTo(route('customer.password.otp'));
        }

        $challengeTokenHash = $this->hashChallengeToken((string) $challenge['token']);
        $sessionFingerprint = isset($challenge['fingerprint']) && is_string($challenge['fingerprint'])
            ? $challenge['fingerprint']
            : null;

        $lookup = CustomerPasswordReset::query()
            ->where('challenge_token_hash', $challengeTokenHash)
            ->first(['id', 'customer_id']);

        if (! $lookup) {
            // Perform a dummy HMAC comparison to equalize timing for dummy/unknown contexts.
            hash_equals($this->hashOtp('000000', $challengeTokenHash), $this->hashOtp($otp, $challengeTokenHash));

            throw ValidationException::withMessages([
                'otp' => self::GENERIC_VERIFICATION_ERROR,
            ])->redirectTo(route('customer.password.otp'));
        }

        $maxAttempts = (int) config('customer_portal.password_reset.max_verification_attempts', 5);
        $resetTokenExpiryMinutes = (int) config('customer_portal.password_reset.reset_token_expiry_minutes', 10);

        $outcome = DB::transaction(function () use (
            $lookup,
            $challengeTokenHash,
            $sessionFingerprint,
            $otp,
            $maxAttempts,
            $resetTokenExpiryMinutes
        ) {
            // Consistent lock order: Customer first, then CustomerPasswordReset.
            $customer = Customer::query()
                ->whereKey($lookup->customer_id)
                ->lockForUpdate()
                ->first();

            $reset = CustomerPasswordReset::query()
                ->where('challenge_token_hash', $challengeTokenHash)
                ->lockForUpdate()
                ->first();

            if (! $reset || ! $this->isChallengeUsable($reset, $customer, $maxAttempts, $sessionFingerprint)) {
                if ($reset && $reset->invalidated_at === null && $reset->used_at === null && $reset->verified_at === null) {
                    $reset->update(['invalidated_at' => now()]);
                }

                return ['success' => false];
            }

            $expectedHash = $this->hashOtp($otp, $reset->challenge_token_hash);

            if (! hash_equals($reset->otp_hash, $expectedHash)) {
                $newAttempts = $reset->attempt_count + 1;
                $updates = ['attempt_count' => $newAttempts];

                if ($newAttempts >= $maxAttempts) {
                    $updates['invalidated_at'] = now();
                }

                $reset->update($updates);

                return ['success' => false];
            }

            $now = now();
            $resetToken = bin2hex(random_bytes(32));
            $resetExpiresAt = $now->copy()->addMinutes($resetTokenExpiryMinutes);

            $reset->update([
                'verified_at' => $now,
                'reset_token_hash' => $this->hashResetToken($resetToken),
                'reset_expires_at' => $resetExpiresAt,
            ]);

            return [
                'success' => true,
                'reset_token' => $resetToken,
                'expires_at' => $resetExpiresAt->timestamp,
                'fingerprint' => $this->recoveryFingerprint($customer),
            ];
        });

        if (! ($outcome['success'] ?? false)) {
            throw ValidationException::withMessages([
                'otp' => self::GENERIC_VERIFICATION_ERROR,
            ])->redirectTo(route('customer.password.otp'));
        }

        if ($request->hasSession()) {
            $request->session()->forget(self::SESSION_CHALLENGE_KEY);
            $request->session()->put(self::SESSION_AUTHORIZATION_KEY, [
                'token' => $outcome['reset_token'],
                'expires_at' => $outcome['expires_at'],
                'fingerprint' => $outcome['fingerprint'],
            ]);
        }
    }

    public function resendOtp(Request $request): ?string
    {
        $challenge = $request->hasSession() ? $request->session()->get(self::SESSION_CHALLENGE_KEY) : null;

        if (! is_array($challenge) || empty($challenge['token'])) {
            return null;
        }

        $challengeTokenHash = $this->hashChallengeToken((string) $challenge['token']);
        $lookup = CustomerPasswordReset::query()
            ->where('challenge_token_hash', $challengeTokenHash)
            ->first(['id', 'customer_id']);

        $maxPerHour = (int) config('customer_portal.password_reset.max_requests_per_hour', 5);
        $resendInterval = (int) config('customer_portal.password_reset.resend_interval_seconds', 60);

        if (! $lookup) {
            // Dummy recovery context: apply dummy cooldown and return equivalent generic response.
            $dummyCooldownKey = 'customer-portal-otp-dummy-cooldown:' . sha1((string) $challenge['token']);
            if (! RateLimiter::tooManyAttempts($dummyCooldownKey, 1)) {
                RateLimiter::hit($dummyCooldownKey, $resendInterval);
                $request->session()->put(self::SESSION_CHALLENGE_KEY, [
                    'token' => bin2hex(random_bytes(32)),
                    'issued_at' => now()->timestamp,
                    'fingerprint' => null,
                ]);
            }

            return self::GENERIC_CONFIRMATION_MESSAGE;
        }

        $otpLength = (int) config('customer_portal.password_reset.otp_length', 6);
        $expiryMinutes = (int) config('customer_portal.password_reset.otp_expiry_minutes', 5);

        $newChallengeToken = null;
        $newIssuedAt = null;
        $newFingerprint = null;

        DB::transaction(function () use (
            $lookup,
            $maxPerHour,
            $resendInterval,
            $otpLength,
            $expiryMinutes,
            &$newChallengeToken,
            &$newIssuedAt,
            &$newFingerprint
        ) {
            $customer = Customer::query()
                ->whereKey($lookup->customer_id)
                ->lockForUpdate()
                ->first();

            if (! $this->isEligibleForPasswordReset($customer)) {
                return;
            }

            $normalizedUsername = $this->normalizeUsername((string) $customer->username);
            $cooldownKey = $this->cooldownThrottleKey($normalizedUsername);
            $hourlyKey = $this->hourlyThrottleKey($normalizedUsername);

            if (RateLimiter::tooManyAttempts($cooldownKey, 1) || RateLimiter::tooManyAttempts($hourlyKey, $maxPerHour)) {
                return;
            }

            // DB-level concurrency guard to prevent parallel resend requests from bypassing cooldown/hourly caps.
            $now = now();
            $recentCount = CustomerPasswordReset::query()
                ->where('customer_id', $customer->id)
                ->where('created_at', '>=', $now->copy()->subHour())
                ->count();

            $latestCreatedAt = CustomerPasswordReset::query()
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->value('created_at');

            if ($recentCount >= $maxPerHour) {
                return;
            }

            if ($latestCreatedAt !== null && \Illuminate\Support\Carbon::parse($latestCreatedAt)->diffInSeconds($now) < $resendInterval) {
                return;
            }

            RateLimiter::hit($cooldownKey, $resendInterval);
            RateLimiter::hit($hourlyKey, 3600);

            [$newChallengeToken, $newIssuedAt, $newFingerprint] = $this->issueChallengeForCustomer($customer, $otpLength, $expiryMinutes);
        });

        if ($newChallengeToken !== null && $newIssuedAt !== null && $request->hasSession()) {
            $request->session()->put(self::SESSION_CHALLENGE_KEY, [
                'token' => $newChallengeToken,
                'issued_at' => $newIssuedAt,
                'fingerprint' => $newFingerprint,
            ]);
        }

        return self::GENERIC_CONFIRMATION_MESSAGE;
    }

    public function validateResetAuthorization(Request $request): ?CustomerPasswordReset
    {
        if (! $request->hasSession()) {
            return null;
        }

        $authContext = $request->session()->get(self::SESSION_AUTHORIZATION_KEY);

        if (! is_array($authContext) || empty($authContext['token'])) {
            return null;
        }

        $resetTokenHash = $this->hashResetToken((string) $authContext['token']);
        $sessionFingerprint = isset($authContext['fingerprint']) && is_string($authContext['fingerprint'])
            ? $authContext['fingerprint']
            : null;

        $reset = CustomerPasswordReset::query()
            ->with('customer')
            ->where('reset_token_hash', $resetTokenHash)
            ->first();

        if (! $reset || ! $this->isAuthorizationUsable($reset, $reset->customer, $sessionFingerprint)) {
            $this->clearRecoverySession($request);

            return null;
        }

        return $reset;
    }

    public function clearRecoverySession(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget([
                self::SESSION_CHALLENGE_KEY,
                self::SESSION_AUTHORIZATION_KEY,
            ]);
        }
    }

    public function invalidateOutstandingForCustomer(int|string $customerId): void
    {
        CustomerPasswordReset::query()
            ->where('customer_id', $customerId)
            ->whereNull('invalidated_at')
            ->whereNull('used_at')
            ->update(['invalidated_at' => now()]);
    }

    public function isEligibleForPasswordReset(?Customer $customer): bool
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

        if (empty($customer->username) || empty($customer->password)) {
            return false;
        }

        $email = trim((string) $customer->email);

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function credentialsChangedSinceChallenge(Customer $customer, CustomerPasswordReset $reset): bool
    {
        return $customer->password_set_at !== null
            && $reset->created_at !== null
            && $customer->password_set_at->greaterThan($reset->created_at);
    }

    public function recoveryFingerprint(Customer $customer): string
    {
        $payload = implode('|', [
            (string) $customer->getKey(),
            (string) $customer->username,
            (string) $customer->getRawOriginal('password') ?: (string) $customer->password,
            strtolower(trim((string) $customer->email)),
            $customer->login_enabled ? '1' : '0',
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function isAuthorizationUsable(
        CustomerPasswordReset $reset,
        ?Customer $customer,
        ?string $sessionFingerprint = null
    ): bool {
        if (! $this->isEligibleForPasswordReset($customer)) {
            return false;
        }

        if (strtolower(trim((string) $customer->email)) !== strtolower(trim((string) $reset->email))) {
            return false;
        }

        if ($this->credentialsChangedSinceChallenge($customer, $reset)) {
            return false;
        }

        if ($sessionFingerprint !== null && ! hash_equals($this->recoveryFingerprint($customer), $sessionFingerprint)) {
            return false;
        }

        if ($reset->verified_at === null || $reset->used_at !== null || $reset->invalidated_at !== null) {
            return false;
        }

        return $reset->reset_expires_at !== null && ! $reset->reset_expires_at->isPast();
    }

    public function normalizeUsername(string $username): string
    {
        return strtolower(trim($username));
    }

    public function generateOtp(int $length = 6): string
    {
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    public function hashChallengeToken(string $challengeToken): string
    {
        return hash('sha256', $challengeToken);
    }

    public function hashResetToken(string $resetToken): string
    {
        return hash('sha256', $resetToken);
    }

    public function hashOtp(string $otp, string $challengeTokenHash): string
    {
        return hash_hmac('sha256', $challengeTokenHash . '|' . $otp, (string) config('app.key'));
    }

    public function cooldownThrottleKey(string $normalizedUsername): string
    {
        return 'customer-portal-otp-cooldown:' . Str::transliterate(sha1($normalizedUsername));
    }

    public function hourlyThrottleKey(string $normalizedUsername): string
    {
        return 'customer-portal-otp-hour:' . Str::transliterate(sha1($normalizedUsername));
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     */
    private function issueChallengeForCustomer(Customer $customer, int $otpLength, int $expiryMinutes): array
    {
        $now = now();

        CustomerPasswordReset::query()
            ->where('customer_id', $customer->id)
            ->whereNull('invalidated_at')
            ->whereNull('used_at')
            ->update(['invalidated_at' => $now]);

        $otp = $this->generateOtp($otpLength);
        $challengeToken = bin2hex(random_bytes(32));
        $challengeTokenHash = $this->hashChallengeToken($challengeToken);
        $otpHash = $this->hashOtp($otp, $challengeTokenHash);

        $reset = CustomerPasswordReset::query()->create([
            'customer_id' => $customer->id,
            'email' => trim((string) $customer->email),
            'challenge_token_hash' => $challengeTokenHash,
            'otp_hash' => $otpHash,
            'attempt_count' => 0,
            'expires_at' => $now->copy()->addMinutes($expiryMinutes),
        ]);

        $resetId = $reset->id;

        DB::afterCommit(function () use ($resetId, $otp) {
            SendPasswordResetOtpJob::dispatch($resetId, $otp);
        });

        return [$challengeToken, $now->timestamp, $this->recoveryFingerprint($customer)];
    }

    private function ensureOpaqueChallengeContext(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        if (! $this->hasChallengeContext($request)) {
            $request->session()->put(self::SESSION_CHALLENGE_KEY, [
                'token' => bin2hex(random_bytes(32)),
                'issued_at' => now()->timestamp,
                'fingerprint' => null,
            ]);
        }
    }

    private function isChallengeUsable(
        CustomerPasswordReset $reset,
        ?Customer $customer,
        int $maxAttempts,
        ?string $sessionFingerprint = null
    ): bool {
        if (! $this->isEligibleForPasswordReset($customer)) {
            return false;
        }

        if (strtolower(trim((string) $customer->email)) !== strtolower(trim((string) $reset->email))) {
            return false;
        }

        if ($this->credentialsChangedSinceChallenge($customer, $reset)) {
            return false;
        }

        if ($sessionFingerprint !== null && ! hash_equals($this->recoveryFingerprint($customer), $sessionFingerprint)) {
            return false;
        }

        if ($reset->invalidated_at !== null || $reset->used_at !== null || $reset->verified_at !== null) {
            return false;
        }

        if ($reset->expires_at->isPast()) {
            return false;
        }

        return $reset->attempt_count < $maxAttempts;
    }
}
