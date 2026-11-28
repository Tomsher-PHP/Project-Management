<?php

namespace Tests\Feature\CustomerPortal;

use App\Mail\CustomerPortal\PasswordResetOtpMail;
use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use App\Services\CustomerPortal\Auth\CustomerOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerOtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('customer-portal-forgot-ip-min:127.0.0.1');
        RateLimiter::clear('customer-portal-forgot-ip-hr:127.0.0.1');
        RateLimiter::clear('customer-portal-otp-verify-ip:127.0.0.1');
    }

    public function test_direct_access_without_recovery_context_redirects_to_forgot_password(): void
    {
        $this->get(route('customer.password.otp'))
            ->assertRedirect(route('customer.password.request'));
    }

    public function test_forgot_password_redirects_identically_for_eligible_and_ineligible_usernames(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customer = $this->createEligibleCustomer(['username' => 'verify_page_cust']);

        $jarA = [];
        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jarA, [
            'username' => $customer->username,
        ])->assertRedirect(route('customer.password.otp'));

        $pageA = $this->sendWithJar('GET', route('customer.password.otp', [], false), $jarA);
        $pageA->assertOk()
            ->assertSeeText('Verify OTP')
            ->assertSeeText('Enter the six-digit verification code sent to your registered company email address.')
            ->assertDontSee($customer->email);

        $jarB = [];
        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jarB, [
            'username' => 'unknown_nonexistent_cust',
        ])->assertRedirect(route('customer.password.otp'));

        $pageB = $this->sendWithJar('GET', route('customer.password.otp', [], false), $jarB);
        $pageB->assertOk()
            ->assertSeeText('Verify OTP')
            ->assertSeeText('Enter the six-digit verification code sent to your registered company email address.');
    }

    public function test_otp_validation_rejects_invalid_formats_and_never_flashes_otp(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customer = $this->createEligibleCustomer(['username' => 'otp_format_cust']);
        [$jar] = $this->initiateRecovery($customer->username);

        foreach (['', '12345', '1234567', 'abcdef', '12-456'] as $invalidOtp) {
            $resp = $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
                'otp' => $invalidOtp,
            ]);
            $resp->assertRedirect(route('customer.password.otp'));

            $page = $this->sendWithJar('GET', route('customer.password.otp', [], false), $jar);
            $page->assertOk();
            if ($invalidOtp !== '') {
                $page->assertDontSee('value="' . $invalidOtp . '"', false);
            }
        }
    }

    public function test_valid_otp_including_leading_zeros_succeeds_and_issues_reset_authorization(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customer = $this->createEligibleCustomer(['username' => 'leading_zero_cust']);
        $originalPassword = $customer->password;

        [$jar, $reset] = $this->initiateRecovery($customer->username);

        // Set a known OTP with a leading zero bound to this challenge.
        $leadingZeroOtp = '012345';
        $reset->update([
            'otp' => $leadingZeroOtp,
        ]);

        $resp = $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
            'otp' => $leadingZeroOtp,
        ]);
        $resp->assertRedirect(route('customer.password.reset'));

        $page = $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar);
        $page->assertOk()
            ->assertSeeText('Reset Password');

        // Revisiting verify-otp with valid reset authorization redirects to reset-password.
        $this->sendWithJar('GET', route('customer.password.otp', [], false), $jar)
            ->assertRedirect(route('customer.password.reset'));

        $freshReset = $reset->fresh();
        $this->assertNotNull($freshReset->verified_at);
        $this->assertNotNull($freshReset->reset_token_hash);
        $this->assertNotNull($freshReset->reset_expires_at);
        $this->assertNull($freshReset->used_at);
        $this->assertSame(0, $freshReset->attempt_count);

        // Customer must NOT be logged in and password must NOT be changed.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertRedirect(route('customer.login'));
        $this->assertSame($originalPassword, $customer->fresh()->password);

        // Verified OTP cannot be replayed.
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
            'otp' => $leadingZeroOtp,
        ])->assertRedirect(route('customer.password.otp'));
    }

    public function test_incorrect_otp_increments_attempt_count_and_invalidates_after_max_attempts(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customer = $this->createEligibleCustomer(['username' => 'max_attempts_cust']);
        [$jar, $reset, $sentOtp] = $this->initiateRecovery($customer->username);

        $wrongOtp = $sentOtp === '999999' ? '888888' : '999999';

        for ($i = 1; $i <= 5; $i++) {
            $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
                'otp' => $wrongOtp,
            ])->assertRedirect(route('customer.password.otp'));

            $this->assertSame($i, $reset->fresh()->attempt_count);
        }

        $this->assertNotNull($reset->fresh()->invalidated_at);

        // Even the real OTP must now fail after max attempts reached.
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
            'otp' => $sentOtp,
        ])->assertRedirect(route('customer.password.otp'));

        $this->assertNull($reset->fresh()->verified_at);
        $this->assertNull($reset->fresh()->reset_token_hash);
    }

    public function test_expired_invalidated_and_cross_session_otps_are_rejected(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customerA = $this->createEligibleCustomer(['username' => 'cross_cust_a']);
        $customerB = $this->createEligibleCustomer(['username' => 'cross_cust_b']);

        [$jarA, $resetA, $otpA] = $this->initiateRecovery($customerA->username);
        [$jarB, $resetB, $otpB] = $this->initiateRecovery($customerB->username);

        // Cross-customer session cannot verify A's OTP.
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jarB, [
            'otp' => $otpA,
        ])->assertRedirect(route('customer.password.otp'));
        $this->assertNull($resetA->fresh()->verified_at);

        // Expired OTP fails.
        $resetA->update(['expires_at' => now()->subMinute()]);
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jarA, [
            'otp' => $otpA,
        ])->assertRedirect(route('customer.password.otp'));
        $this->assertNull($resetA->fresh()->verified_at);

        // Invalidated OTP fails.
        $resetB->update(['invalidated_at' => now()]);
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jarB, [
            'otp' => $otpB,
        ])->assertRedirect(route('customer.password.otp'));
        $this->assertNull($resetB->fresh()->verified_at);
    }

    public function test_account_and_credential_changes_invalidate_otp_and_reset_authorization(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();
        $this->registerResetTestRoute();

        // 1. Admin credential change (password_set_at > reset.created_at) invalidates OTP verification.
        $customer1 = $this->createEligibleCustomer(['username' => 'admin_changed_cust']);
        [$jar1, $reset1, $otp1] = $this->initiateRecovery($customer1->username);

        Customer::query()->whereKey($customer1->id)->update([
            'password_set_at' => now()->addSeconds(5),
        ]);

        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar1, [
            'otp' => $otp1,
        ])->assertRedirect(route('customer.password.otp'));
        $this->assertNull($reset1->fresh()->verified_at);

        // 2. Email change invalidates reset authorization middleware.
        $customer2 = $this->createEligibleCustomer(['username' => 'email_changed_cust']);
        [$jar2, $reset2, $otp2] = $this->initiateRecovery($customer2->username);

        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar2, [
            'otp' => $otp2,
        ])->assertRedirect(route('customer.password.reset'));
        $this->assertNotNull($reset2->fresh()->verified_at);

        // Valid authorization passes middleware.
        $this->sendWithJar('GET', '/c-portal/_test/reset-auth-check', $jar2)->assertOk();

        // Email changes -> middleware rejects & clears authorization.
        Customer::query()->whereKey($customer2->id)->update(['email' => 'new-email@example.com']);
        $this->sendWithJar('GET', '/c-portal/_test/reset-auth-check', $jar2)
            ->assertRedirect(route('customer.password.request'));

        // 3. Disabled customer or expired/used authorization rejected by middleware.
        $customer3 = $this->createEligibleCustomer(['username' => 'disabled_auth_cust']);
        [$jar3, $reset3, $otp3] = $this->initiateRecovery($customer3->username);
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar3, ['otp' => $otp3]);

        Customer::query()->whereKey($customer3->id)->update(['login_enabled' => false]);
        $this->sendWithJar('GET', '/c-portal/_test/reset-auth-check', $jar3)
            ->assertRedirect(route('customer.password.request'));

        // Unrelated session without authorization cannot access protected reset route.
        $emptyJar = [];
        $this->sendWithJar('GET', '/c-portal/_test/reset-auth-check', $emptyJar)
            ->assertRedirect(route('customer.password.request'));
    }

    private function initiateRecovery(string $username): array
    {
        $jar = [];
        $sentOtp = null;

        Mail::fake();
        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jar, [
            'username' => $username,
        ])->assertRedirect(route('customer.password.otp'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$sentOtp) {
            $sentOtp = $mail->otp;

            return true;
        });

        $customer = Customer::query()->where('username', strtolower(trim($username)))->firstOrFail();
        $reset = CustomerPasswordReset::query()
            ->where('customer_id', $customer->id)
            ->whereNull('invalidated_at')
            ->latest('id')
            ->firstOrFail();

        return [$jar, $reset, $sentOtp];
    }

    private function registerResetTestRoute(): void
    {
        Route::prefix('c-portal')
            ->name('customer.')
            ->middleware('customer.web')
            ->group(function () {
                Route::get('/_test/reset-auth-check', fn () => response()->json(['ok' => true]))
                    ->middleware(['customer.guest', 'customer.password-reset-verified']);
            });

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    private function createEligibleCustomer(array $overrides = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'cust_' . fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('SecretPass123!'),
            'password_set_at' => now()->subMinute(),
        ], $overrides));
    }

    private function sendWithJar(string $method, string $uri, array &$jar, array $data = [])
    {
        $cookiesToSend = [];
        foreach ($jar as $name => $cookie) {
            if (str_starts_with($uri, $cookie['path'])) {
                $cookiesToSend[$name] = $cookie['value'];
            }
        }

        $response = $this->call($method, $uri, $data, $cookiesToSend);

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() < time()) {
                unset($jar[$cookie->getName()]);
            } else {
                $jar[$cookie->getName()] = [
                    'value' => $cookie->getValue(),
                    'path' => $cookie->getPath() ?: '/',
                ];
            }
        }

        return $response;
    }
}
