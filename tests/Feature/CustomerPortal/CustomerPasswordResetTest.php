<?php

namespace Tests\Feature\CustomerPortal;

use App\Mail\CustomerPortal\PasswordResetOtpMail;
use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use App\Models\DataSyncOutboundHistory;
use App\Models\User;
use App\Services\CustomerPortal\Auth\CustomerOtpService;
use App\Services\CustomerServices;
use App\Services\DataSync\OutboundDataSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('customer-portal-forgot-ip-min:127.0.0.1');
        RateLimiter::clear('customer-portal-forgot-ip-hr:127.0.0.1');
        RateLimiter::clear('customer-portal-otp-verify-ip:127.0.0.1');
        RateLimiter::clear('customer-portal-password-reset-ip:127.0.0.1');
    }

    public function test_direct_access_to_reset_password_without_authorization_is_rejected(): void
    {
        $this->get(route('customer.password.reset'))
            ->assertRedirect(route('customer.password.request'));

        $this->post(route('customer.password.update'), [
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('customer.password.request'));
    }

    public function test_reset_password_page_renders_with_valid_authorization_and_enforces_complexity(): void
    {
        config(['session.driver' => 'database']);

        $customer = $this->createEligibleCustomer(['username' => 'reset_view_cust']);
        [$jar, $reset] = $this->completeOtpVerification($customer->username);

        $page = $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar);
        $page->assertOk()
            ->assertSeeText('Reset Password')
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertDontSee($customer->username)
            ->assertDontSee($customer->email);

        // Weak passwords & mismatched confirmation must fail without consuming authorization.
        $invalidPayloads = [
            ['password' => 'short1', 'password_confirmation' => 'short1'],
            ['password' => 'onlyletters', 'password_confirmation' => 'onlyletters'],
            ['password' => '12345678', 'password_confirmation' => '12345678'],
            ['password' => 'ValidPass123', 'password_confirmation' => 'DifferentPass123'],
        ];

        foreach ($invalidPayloads as $payload) {
            $this->sendWithJar('POST', route('customer.password.update', [], false), $jar, $payload)
                ->assertRedirect(route('customer.password.reset'));

            $reloaded = $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar);
            $reloaded->assertOk();
            $reloaded->assertDontSee('value="' . $payload['password'] . '"', false);
        }

        $this->assertNull($reset->fresh()->used_at);
    }

    public function test_successful_password_reset_updates_password_consumes_authorization_and_protects_audit_and_data_sync(): void
    {
        config(['session.driver' => 'database']);
        Log::spy();

        $creator = User::factory()->create(['is_active' => true, 'delete_status' => false]);
        $updater = User::factory()->create(['is_active' => true, 'delete_status' => false]);

        $customer = $this->createEligibleCustomer([
            'username' => 'full_reset_cust',
            'password' => Hash::make('OldSecret123'),
            'last_login_at' => now()->subDays(2),
        ]);

        Customer::query()->whereKey($customer->id)->update([
            'added_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);
        $originalLastLogin = $customer->fresh()->last_login_at->toISOString();

        [$jar, $reset] = $this->completeOtpVerification($customer->username);

        $syncSpy = $this->spy(OutboundDataSyncService::class);
        $activityBefore = Activity::query()->count();
        $syncBefore = DataSyncOutboundHistory::query()->count();

        $resp = $this->sendWithJar('POST', route('customer.password.update', [], false), $jar, [
            'password' => 'BrandNewPass99',
            'password_confirmation' => 'BrandNewPass99',
        ]);
        $resp->assertRedirect(route('customer.login'));

        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $loginPage->assertOk()
            ->assertSeeText('Your password has been reset successfully. Please sign in using your new password.');

        // Must NOT auto-authenticate customer.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertRedirect(route('customer.login'));

        // Password hashed, password_set_at updated, audit & last_login_at & Data Sync untouched.
        $freshCustomer = $customer->fresh();
        $this->assertTrue(Hash::check('BrandNewPass99', $freshCustomer->password));
        $this->assertFalse(Hash::check('OldSecret123', $freshCustomer->password));
        $this->assertNotNull($freshCustomer->password_set_at);
        $this->assertSame($creator->id, $freshCustomer->added_by);
        $this->assertSame($updater->id, $freshCustomer->updated_by);
        $this->assertSame($originalLastLogin, $freshCustomer->last_login_at->toISOString());
        $this->assertSame($activityBefore, Activity::query()->count());
        $this->assertSame($syncBefore, DataSyncOutboundHistory::query()->count());
        $syncSpy->shouldNotHaveReceived('handle');

        // Reset authorization consumed exactly once; browser Back / replay rejected.
        $this->assertNotNull($reset->fresh()->used_at);

        $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar)
            ->assertRedirect(route('customer.password.request'));

        $this->sendWithJar('POST', route('customer.password.update', [], false), $jar, [
            'password' => 'AnotherPass123',
            'password_confirmation' => 'AnotherPass123',
        ])->assertRedirect(route('customer.password.request'));

        // Old password fails login, new password succeeds.
        $this->post(route('customer.login.store'), [
            'username' => $customer->username,
            'password' => 'OldSecret123',
        ])->assertRedirect(route('customer.login'));

        $this->post(route('customer.login.store'), [
            'username' => $customer->username,
            'password' => 'BrandNewPass99',
        ])->assertRedirect(route('customer.dashboard'));

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('error');
    }

    public function test_expired_invalidated_or_same_second_admin_modified_authorizations_are_rejected(): void
    {
        config(['session.driver' => 'database']);

        // 1. Expired authorization
        $c1 = $this->createEligibleCustomer(['username' => 'expired_reset_cust']);
        [$jar1, $reset1] = $this->completeOtpVerification($c1->username);
        $reset1->update(['reset_expires_at' => now()->subMinute()]);

        $this->sendWithJar('POST', route('customer.password.update', [], false), $jar1, [
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('customer.password.request'));

        // 2. Same-second admin password change invalidates authorization permanently
        $c2 = $this->createEligibleCustomer([
            'username' => 'same_sec_admin_cust',
            'sales_person_id' => User::factory()->create(['is_active' => true, 'delete_status' => false])->id,
        ]);
        [$jar2, $reset2] = $this->completeOtpVerification($c2->username);

        app(CustomerServices::class)->update($c2, $this->adminUpdatePayload($c2, [
            'password' => 'AdminSet999',
            'password_confirmation' => 'AdminSet999',
        ]));

        $this->assertNotNull($reset2->fresh()->invalidated_at);
        $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar2)
            ->assertRedirect(route('customer.password.request'));

        // 3. Email change + restore does not reactivate invalidated authorization
        $c3 = $this->createEligibleCustomer([
            'username' => 'email_toggle_cust',
            'email' => 'original@toggle.example',
            'sales_person_id' => $c2->sales_person_id,
        ]);
        [$jar3, $reset3] = $this->completeOtpVerification($c3->username);

        app(CustomerServices::class)->update($c3, $this->adminUpdatePayload($c3, [
            'email' => 'temp@toggle.example',
        ]));
        app(CustomerServices::class)->update($c3->fresh(), $this->adminUpdatePayload($c3->fresh(), [
            'email' => 'original@toggle.example',
        ]));

        $this->assertNotNull($reset3->fresh()->invalidated_at);
        $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar3)
            ->assertRedirect(route('customer.password.request'));

        // 4. Disabling and re-enabling login does not reactivate invalidated authorization
        $c4 = $this->createEligibleCustomer([
            'username' => 'login_toggle_reset_cust',
            'sales_person_id' => $c2->sales_person_id,
        ]);
        [$jar4, $reset4] = $this->completeOtpVerification($c4->username);

        app(CustomerServices::class)->update($c4, $this->adminUpdatePayload($c4, [
            'login_enabled' => false,
        ]));
        app(CustomerServices::class)->update($c4->fresh(), $this->adminUpdatePayload($c4->fresh(), [
            'login_enabled' => true,
            'username' => $c4->username,
        ]));

        $this->assertNotNull($reset4->fresh()->invalidated_at);
        $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar4)
            ->assertRedirect(route('customer.password.request'));
    }

    private function completeOtpVerification(string $username): array
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

        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
            'otp' => $sentOtp,
        ])->assertRedirect(route('customer.password.reset'));

        return [$jar, $reset->fresh()];
    }

    private function adminUpdatePayload(Customer $customer, array $overrides = []): array
    {
        return array_merge([
            'name' => $customer->name,
            'email' => $customer->email,
            'sales_person_id' => $customer->sales_person_id,
            'login_enabled' => $customer->login_enabled,
            'username' => $customer->username,
            'primary_name' => $customer->primaryContact?->name ?? 'Primary Contact',
            'primary_email' => $customer->primaryContact?->email,
        ], $overrides);
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
