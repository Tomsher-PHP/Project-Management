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
use Tests\TestCase;

class CustomerOtpResendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('customer-portal-forgot-ip-min:127.0.0.1');
        RateLimiter::clear('customer-portal-forgot-ip-hr:127.0.0.1');
        RateLimiter::clear('customer-portal-otp-verify-ip:127.0.0.1');
    }

    public function test_resend_without_session_context_redirects_to_forgot_password(): void
    {
        $this->post(route('customer.password.otp.resend'))
            ->assertRedirect(route('customer.password.request'));
    }

    public function test_resend_generates_new_otp_invalidates_old_challenge_and_resets_attempts(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customer = $this->createEligibleCustomer(['username' => 'resend_valid_cust']);
        $otpService = app(CustomerOtpService::class);

        $jar = [];
        $firstOtp = null;
        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jar, [
            'username' => $customer->username,
        ])->assertRedirect(route('customer.password.otp'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$firstOtp) {
            $firstOtp = $mail->otp;

            return true;
        });

        $firstReset = CustomerPasswordReset::query()->where('customer_id', $customer->id)->sole();

        // Exhaust or increment attempts on first challenge and travel past 60s cooldown.
        $firstReset->update(['attempt_count' => 5, 'invalidated_at' => now()]);
        $this->travel(65)->seconds();
        RateLimiter::clear($otpService->cooldownThrottleKey($customer->username));

        Mail::fake();
        $secondOtp = null;
        $this->sendWithJar('POST', route('customer.password.otp.resend', [], false), $jar)
            ->assertRedirect(route('customer.password.otp'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$secondOtp) {
            $secondOtp = $mail->otp;

            return true;
        });

        $secondReset = CustomerPasswordReset::query()
            ->where('customer_id', $customer->id)
            ->whereNull('invalidated_at')
            ->sole();

        $this->assertNotSame($firstReset->id, $secondReset->id);
        $this->assertSame(0, $secondReset->attempt_count);
        $this->assertNotNull($firstReset->fresh()->invalidated_at);

        // Old OTP cannot verify on new session challenge.
        if ($firstOtp !== $secondOtp) {
            $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
                'otp' => $firstOtp,
            ])->assertRedirect(route('customer.password.otp'));
            $this->assertNull($secondReset->fresh()->verified_at);
        }

        // New OTP succeeds.
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
            'otp' => $secondOtp,
        ])->assertRedirect(route('customer.password.reset'));
        $this->assertNotNull($secondReset->fresh()->verified_at);
    }

    public function test_resend_enforces_cooldown_and_shares_hourly_limit_with_initial_requests(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customer = $this->createEligibleCustomer(['username' => 'resend_limit_cust']);
        $otpService = app(CustomerOtpService::class);

        $jar = [];
        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jar, [
            'username' => $customer->username,
        ])->assertRedirect(route('customer.password.otp'));

        // Immediate resend within 60s must be suppressed.
        $this->sendWithJar('POST', route('customer.password.otp.resend', [], false), $jar)
            ->assertRedirect(route('customer.password.otp'));

        $this->assertSame(1, CustomerPasswordReset::query()->where('customer_id', $customer->id)->count());
        Mail::assertSent(PasswordResetOtpMail::class, 1);

        // Perform 4 more resends spaced >60s apart (total 5 requests/hour).
        for ($i = 2; $i <= 5; $i++) {
            $this->travel(65)->seconds();
            RateLimiter::clear($otpService->cooldownThrottleKey($customer->username));

            $this->sendWithJar('POST', route('customer.password.otp.resend', [], false), $jar)
                ->assertRedirect(route('customer.password.otp'));
        }

        $this->assertSame(5, CustomerPasswordReset::query()->where('customer_id', $customer->id)->count());

        // 6th request within the hour must be suppressed.
        $this->travel(65)->seconds();
        RateLimiter::clear($otpService->cooldownThrottleKey($customer->username));

        $this->sendWithJar('POST', route('customer.password.otp.resend', [], false), $jar)
            ->assertRedirect(route('customer.password.otp'));

        $this->assertSame(5, CustomerPasswordReset::query()->where('customer_id', $customer->id)->count());
    }

    public function test_resend_does_not_invalidate_other_customer_with_duplicate_email(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();

        $customerA = $this->createEligibleCustomer([
            'username' => 'dup_resend_a',
            'email' => 'shared-resend@example.com',
        ]);
        $customerB = $this->createEligibleCustomer([
            'username' => 'dup_resend_b',
            'email' => 'shared-resend@example.com',
        ]);

        $jarA = [];
        $jarB = [];

        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jarA, [
            'username' => $customerA->username,
        ]);
        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jarB, [
            'username' => $customerB->username,
        ]);

        $resetB = CustomerPasswordReset::query()->where('customer_id', $customerB->id)->sole();

        $this->travel(65)->seconds();
        RateLimiter::clear(app(CustomerOtpService::class)->cooldownThrottleKey($customerA->username));

        $this->sendWithJar('POST', route('customer.password.otp.resend', [], false), $jarA)
            ->assertRedirect(route('customer.password.otp'));

        $this->assertNull($resetB->fresh()->invalidated_at);
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
