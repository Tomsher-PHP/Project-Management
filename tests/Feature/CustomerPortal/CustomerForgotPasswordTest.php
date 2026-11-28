<?php

namespace Tests\Feature\CustomerPortal;

use App\Jobs\CustomerPortal\SendPasswordResetOtpJob;
use App\Mail\CustomerPortal\PasswordResetOtpMail;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerPasswordReset;
use App\Services\CustomerPortal\Auth\CustomerOtpService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CustomerForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('customer-portal-forgot-ip-min:127.0.0.1');
        RateLimiter::clear('customer-portal-forgot-ip-hr:127.0.0.1');
    }

    public function test_forgot_password_page_renders_correctly_and_login_has_link(): void
    {
        $this->get(route('customer.login'))
            ->assertOk()
            ->assertSeeText('Forgot Password?')
            ->assertSee(route('customer.password.request'), false);

        $response = $this->get(route('customer.password.request'));

        $response->assertOk();
        $response->assertSeeText('Forgot Password');
        $response->assertSeeText('Enter your username to receive a verification code at your registered company email address.');
        $response->assertSeeText('Send OTP');
        $response->assertSeeText('Back to Login');
        $response->assertSee('name="username"', false);
        $response->assertSee('name="_token"', false);
        $response->assertDontSee('name="email"', false);
    }

    public function test_authenticated_customer_cannot_access_forgot_password_page(): void
    {
        config(['session.driver' => 'database']);

        $customer = $this->createEligibleCustomer(['username' => 'auth_guest_check']);

        $jar = [];
        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $token = $this->extractCsrfToken($loginPage->getContent());

        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $token,
            'username' => 'auth_guest_check',
            'password' => 'SecretPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        $this->sendWithJar('GET', route('customer.password.request', [], false), $jar)
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_username_validation_works(): void
    {
        config(['session.driver' => 'database']);

        $jar = [];
        $page = $this->sendWithJar('GET', route('customer.password.request', [], false), $jar);
        $token = $this->extractCsrfToken($page->getContent());

        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jar, [
            '_token' => $token,
            'username' => '',
        ])->assertRedirect(route('customer.password.request'));

        $this->sendWithJar('GET', route('customer.password.request', [], false), $jar)
            ->assertOk()
            ->assertSeeText('The username field is required.');

        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jar, [
            '_token' => $token,
            'username' => str_repeat('a', 256),
        ])->assertRedirect(route('customer.password.request'));

        $this->sendWithJar('GET', route('customer.password.request', [], false), $jar)
            ->assertOk()
            ->assertSeeText('255 characters');
    }

    public function test_eligible_customer_receives_hashed_otp_challenge_and_queued_email_to_company_email_only(): void
    {
        config(['session.driver' => 'database']);
        Mail::fake();
        Log::spy();

        $customer = $this->createEligibleCustomer([
            'name' => 'Acme Recovery Co',
            'username' => 'acme_custom_user',
            'email' => 'company@acme.example',
        ]);
        $originalPasswordHash = $customer->password;

        // Add primary and extra contacts with different emails; they must NOT receive the OTP.
        CustomerContact::factory()->primary()->create([
            'customer_id' => $customer->id,
            'email' => 'primary-contact@acme.example',
        ]);
        CustomerContact::factory()->create([
            'customer_id' => $customer->id,
            'is_primary' => false,
            'email' => 'extra-contact@acme.example',
        ]);

        $jar = [];
        $page = $this->sendWithJar('GET', route('customer.password.request', [], false), $jar);
        $token = $this->extractCsrfToken($page->getContent());

        $resp = $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jar, [
            '_token' => $token,
            'username' => '  ACME_Custom_User  ',
        ]);
        $resp->assertRedirect(route('customer.password.otp'));

        $rendered = $this->sendWithJar('GET', route('customer.password.otp', [], false), $jar);
        $rendered->assertOk()
            ->assertSeeText(CustomerOtpService::GENERIC_CONFIRMATION_MESSAGE)
            ->assertDontSee('company@acme.example');

        $reset = CustomerPasswordReset::query()->where('customer_id', $customer->id)->sole();
        $this->assertSame('company@acme.example', $reset->email);
        $this->assertSame(0, $reset->attempt_count);
        $this->assertNull($reset->invalidated_at);
        $this->assertNull($reset->used_at);
        $this->assertTrue($reset->expires_at->greaterThan(now()->addMinutes(4)));
        $this->assertTrue($reset->expires_at->lessThanOrEqualTo(now()->addMinutes(5)->addSeconds(5)));

        // Hidden attributes must not serialize.
        $serialized = $reset->toArray();
        $this->assertArrayNotHasKey('otp_hash', $serialized);
        $this->assertArrayNotHasKey('challenge_token_hash', $serialized);
        $this->assertArrayNotHasKey('reset_token_hash', $serialized);

        // Password must remain unchanged.
        $this->assertSame($originalPasswordHash, $customer->fresh()->password);

        // Mail sent only to company email, never to contacts.
        $sentOtp = null;
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use ($reset, &$sentOtp) {
            $sentOtp = $mail->otp;
            $this->assertSame('Customer Portal – Password Reset Verification Code', $mail->envelope()->subject);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $mail->otp);
            $this->assertNotSame($mail->otp, $reset->otp_hash);

            $expectedHash = app(CustomerOtpService::class)->hashOtp($mail->otp, $reset->challenge_token_hash);
            $this->assertTrue(hash_equals($reset->otp_hash, $expectedHash));

            return $mail->hasTo('company@acme.example')
                && ! $mail->hasTo('primary-contact@acme.example')
                && ! $mail->hasTo('extra-contact@acme.example');
        });

        // Session must contain opaque challenge context, not plain-text OTP or customer ID.
        $decodedPayloads = DB::table('sessions')
            ->pluck('payload')
            ->map(fn ($p) => (string) base64_decode((string) $p))
            ->filter(fn ($p) => str_contains($p, 'password_reset_challenge'));

        $this->assertCount(1, $decodedPayloads);
        $challengePayload = $decodedPayloads->first();
        $this->assertStringNotContainsString((string) $sentOtp, $challengePayload);

        $sessionData = unserialize($challengePayload);
        $challenge = $sessionData['customer_portal']['password_reset_challenge'] ?? [];
        $this->assertSame(['token', 'issued_at', 'fingerprint'], array_keys($challenge));
        $this->assertSame(64, strlen((string) $challenge['token']));

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('error');
    }

    public function test_ineligible_and_unknown_customers_do_not_receive_otps_and_return_generic_response(): void
    {
        Mail::fake();
        Queue::fake();

        $disabled = $this->createEligibleCustomer([
            'username' => 'disabled_forgot',
            'login_enabled' => false,
            'email' => 'disabled@example.com',
        ]);

        $inactive = $this->createEligibleCustomer([
            'username' => 'inactive_forgot',
            'is_active' => false,
            'email' => 'inactive@example.com',
        ]);

        $deleted = $this->createEligibleCustomer([
            'username' => 'deleted_forgot',
            'email' => 'deleted@example.com',
        ]);
        $deleted->delete();

        $noEmail = $this->createEligibleCustomer([
            'username' => 'noemail_forgot',
            'email' => null,
        ]);

        $noPassword = $this->createEligibleCustomer([
            'username' => 'nopass_forgot',
            'email' => 'nopass@example.com',
            'password' => null,
            'password_set_at' => null,
        ]);

        $usernames = [
            'nonexistent_user_xyz',
            $disabled->username,
            $inactive->username,
            $deleted->username,
            $noEmail->username,
            $noPassword->username,
        ];

        foreach ($usernames as $username) {
            $this->post(route('customer.password.otp.send'), ['username' => $username])
                ->assertRedirect(route('customer.password.otp'));
        }

        $this->assertSame(0, CustomerPasswordReset::query()->count());
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_previous_challenges_are_invalidated_and_duplicate_emails_do_not_conflict(): void
    {
        Mail::fake();

        $customerA = $this->createEligibleCustomer([
            'username' => 'shared_email_a',
            'email' => 'shared@company.example',
        ]);
        $customerB = $this->createEligibleCustomer([
            'username' => 'shared_email_b',
            'email' => 'shared@company.example',
        ]);

        // Issue OTP for Customer A.
        $this->post(route('customer.password.otp.send'), ['username' => 'shared_email_a'])
            ->assertRedirect(route('customer.password.otp'));

        $firstChallengeA = CustomerPasswordReset::query()->where('customer_id', $customerA->id)->sole();

        // Issue OTP for Customer B (same company email) -> Customer A's challenge must remain valid!
        $this->post(route('customer.password.otp.send'), ['username' => 'shared_email_b'])
            ->assertRedirect(route('customer.password.otp'));

        $this->assertNull($firstChallengeA->fresh()->invalidated_at);
        $this->assertSame(1, CustomerPasswordReset::query()->where('customer_id', $customerB->id)->count());

        // Clear cooldown for Customer A and issue a new OTP for Customer A -> old challenge for A is invalidated.
        $otpService = app(CustomerOtpService::class);
        RateLimiter::clear($otpService->cooldownThrottleKey('shared_email_a'));

        $this->post(route('customer.password.otp.send'), ['username' => 'shared_email_a'])
            ->assertRedirect(route('customer.password.otp'));

        $this->assertNotNull($firstChallengeA->fresh()->invalidated_at);
        $this->assertSame(
            1,
            CustomerPasswordReset::query()
                ->where('customer_id', $customerA->id)
                ->whereNull('invalidated_at')
                ->count()
        );
    }

    public function test_queued_job_payload_is_encrypted_and_skips_invalidated_expired_or_email_changed_challenges(): void
    {
        $this->assertContains(ShouldBeEncrypted::class, class_implements(SendPasswordResetOtpJob::class));

        config(['queue.default' => 'database']);
        Mail::fake();

        $customer = $this->createEligibleCustomer([
            'username' => 'queue_enc_user',
            'email' => 'queue-enc@example.com',
        ]);

        $this->post(route('customer.password.otp.send'), ['username' => 'queue_enc_user'])
            ->assertRedirect(route('customer.password.otp'));

        $jobRow = DB::table('jobs')->first();
        $this->assertNotNull($jobRow);
        $this->assertStringNotContainsString('queue-enc@example.com', $jobRow->payload);

        $reset = CustomerPasswordReset::query()->where('customer_id', $customer->id)->sole();
        $otpService = app(CustomerOtpService::class);

        // 1. Invalidated challenge must skip mail delivery.
        $reset->update(['invalidated_at' => now()]);
        (new SendPasswordResetOtpJob($reset->id, '123456'))->handle($otpService);
        Mail::assertNothingSent();

        // 2. Expired challenge must skip mail delivery.
        $reset->update(['invalidated_at' => null, 'expires_at' => now()->subMinute()]);
        (new SendPasswordResetOtpJob($reset->id, '123456'))->handle($otpService);
        Mail::assertNothingSent();

        // 3. Changed company email must skip mail delivery.
        $reset->update(['expires_at' => now()->addMinutes(5)]);
        Customer::query()->whereKey($customer->id)->update(['email' => 'changed@example.com']);
        (new SendPasswordResetOtpJob($reset->id, '123456'))->handle($otpService);
        Mail::assertNothingSent();

        // 4. Valid challenge delivers mail.
        Customer::query()->whereKey($customer->id)->update(['email' => 'queue-enc@example.com']);
        (new SendPasswordResetOtpJob($reset->id, '123456'))->handle($otpService);
        Mail::assertSent(PasswordResetOtpMail::class, 1);
    }

    public function test_rate_limiting_enforces_cooldown_hourly_account_limit_and_ip_limit(): void
    {
        Mail::fake();

        $customer = $this->createEligibleCustomer([
            'username' => 'rate_limit_user',
            'email' => 'ratelimit@example.com',
        ]);
        $otpService = app(CustomerOtpService::class);

        // 1. Resend cooldown: 2nd request within 60s (even with uppercase/spaces & different IP) is suppressed.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post(route('customer.password.otp.send'), ['username' => 'rate_limit_user'])
            ->assertRedirect(route('customer.password.otp'));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->post(route('customer.password.otp.send'), ['username' => '  RATE_LIMIT_USER  '])
            ->assertRedirect(route('customer.password.otp'));

        $this->assertSame(1, CustomerPasswordReset::query()->where('customer_id', $customer->id)->count());
        Mail::assertSent(PasswordResetOtpMail::class, 1);

        // 2. Hourly account limit: 5 requests/hour max across IP changes.
        for ($i = 2; $i <= 5; $i++) {
            RateLimiter::clear($otpService->cooldownThrottleKey('rate_limit_user'));
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->post(route('customer.password.otp.send'), ['username' => 'rate_limit_user'])
                ->assertRedirect(route('customer.password.otp'));
        }

        $this->assertSame(5, CustomerPasswordReset::query()->where('customer_id', $customer->id)->count());

        // 6th request within the hour must be suppressed even if cooldown is cleared and IP changes.
        RateLimiter::clear($otpService->cooldownThrottleKey('rate_limit_user'));
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post(route('customer.password.otp.send'), ['username' => 'rate_limit_user'])
            ->assertRedirect(route('customer.password.otp'));

        $this->assertSame(5, CustomerPasswordReset::query()->where('customer_id', $customer->id)->count());

        // 3. IP-level limit across multiple usernames from the same IP.
        for ($j = 1; $j <= 10; $j++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
                ->post(route('customer.password.otp.send'), ['username' => "unknown_spray_{$j}"])
                ->assertRedirect(route('customer.password.otp'));
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
            ->post(route('customer.password.otp.send'), ['username' => 'unknown_spray_11'])
            ->assertStatus(429);
    }

    private function createEligibleCustomer(array $overrides = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'cust_' . fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('SecretPass123!'),
            'password_set_at' => now(),
        ], $overrides));
    }

    private function extractCsrfToken(string $html): string
    {
        if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return '';
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
