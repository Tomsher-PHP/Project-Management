<?php

namespace Tests\Feature\CustomerPortal;

use App\Mail\CustomerPortal\PasswordResetOtpMail;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerPortal\Auth\CustomerAuthService;
use App\Services\CustomerServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CustomerSessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('customer-portal-forgot-ip-min:127.0.0.1');
        RateLimiter::clear('customer-portal-forgot-ip-hr:127.0.0.1');
        RateLimiter::clear('customer-portal-otp-verify-ip:127.0.0.1');
        RateLimiter::clear('customer-portal-password-reset-ip:127.0.0.1');
        RateLimiter::clear('customer-portal-ip:127.0.0.1');
    }

    public function test_password_reset_revokes_all_existing_customer_sessions_without_affecting_other_customers_or_pms_employees(): void
    {
        config(['session.driver' => 'database']);

        $employee = User::factory()->create([
            'is_active' => true,
            'delete_status' => false,
            'password' => Hash::make('EmpSecret123!'),
        ]);

        $customerA = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'revoke_target_a',
            'email' => 'revoke-a@example.com',
            'password' => Hash::make('OldPassA123'),
            'password_set_at' => now()->subMinute(),
            'sales_person_id' => $employee->id,
        ]);

        $customerB = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'unaffected_cust_b',
            'email' => 'keep-b@example.com',
            'password' => Hash::make('KeepPassB123'),
            'password_set_at' => now()->subMinute(),
            'sales_person_id' => $employee->id,
        ]);

        // Log in Customer A on two separate browser sessions, Customer B, and PMS employee (even sharing Browser 1!).
        $browserA1 = [];
        $browserA2 = [];
        $browserB = [];

        // PMS employee + Customer A in Browser 1
        $this->sendWithJar('POST', route('login.post', [], false), $browserA1, [
            'email' => $employee->email,
            'password' => 'EmpSecret123!',
        ])->assertRedirect();

        $this->sendWithJar('POST', route('customer.login.store', [], false), $browserA1, [
            'username' => $customerA->username,
            'password' => 'OldPassA123',
        ])->assertRedirect(route('customer.dashboard'));

        // Customer A in Browser 2
        $this->sendWithJar('POST', route('customer.login.store', [], false), $browserA2, [
            'username' => $customerA->username,
            'password' => 'OldPassA123',
        ])->assertRedirect(route('customer.dashboard'));

        // Customer B
        $this->sendWithJar('POST', route('customer.login.store', [], false), $browserB, [
            'username' => $customerB->username,
            'password' => 'KeepPassB123',
        ])->assertRedirect(route('customer.dashboard'));

        // Reset Customer A's password in a third recovery browser.
        $recoveryJar = [];
        $sentOtp = null;
        Mail::fake();

        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $recoveryJar, [
            'username' => $customerA->username,
        ])->assertRedirect(route('customer.password.otp'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$sentOtp) {
            $sentOtp = $mail->otp;

            return true;
        });

        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $recoveryJar, [
            'otp' => $sentOtp,
        ])->assertRedirect(route('customer.password.reset'));

        $this->sendWithJar('POST', route('customer.password.update', [], false), $recoveryJar, [
            'password' => 'NewPassA999',
            'password_confirmation' => 'NewPassA999',
        ])->assertRedirect(route('customer.login'));

        // Both Browser A1 and Browser A2 must be rejected on their next protected request.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $browserA1)
            ->assertRedirect(route('customer.login'));

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $browserA2)
            ->assertRedirect(route('customer.login'));

        // PMS employee in Browser A1 remains authenticated!
        $this->sendWithJar('GET', route('user.workspace', [], false), $browserA1)
            ->assertOk();

        // Customer B remains authenticated!
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $browserB)
            ->assertOk();

        // Customer A can log in again with new password.
        $newBrowserA = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $newBrowserA, [
            'username' => $customerA->username,
            'password' => 'NewPassA999',
        ])->assertRedirect(route('customer.dashboard'));

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $newBrowserA)
            ->assertOk();
    }

    public function test_admin_actions_revoke_or_preserve_customer_sessions_appropriately(): void
    {
        config(['session.driver' => 'database']);

        $admin = User::factory()->create(['is_active' => true, 'delete_status' => false]);
        $customer = Customer::factory()->create([
            'name' => 'Initial Co',
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'admin_action_cust',
            'password' => Hash::make('InitPass123'),
            'password_set_at' => now()->subMinute(),
            'sales_person_id' => $admin->id,
        ]);

        $jar = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            'username' => $customer->username,
            'password' => 'InitPass123',
        ])->assertRedirect(route('customer.dashboard'));

        // 1. Changing customer name keeps existing session valid.
        app(CustomerServices::class)->update($customer, $this->adminPayload($customer, [
            'name' => 'Updated Co Name',
        ]));

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertOk()
            ->assertSeeText('Updated Co Name');

        // 2. Admin changing username revokes existing session.
        app(CustomerServices::class)->update($customer->fresh(), $this->adminPayload($customer->fresh(), [
            'username' => 'renamed_cust_user',
        ]));

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertRedirect(route('customer.login'));

        // Log back in with new username.
        $jar2 = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar2, [
            'username' => 'renamed_cust_user',
            'password' => 'InitPass123',
        ])->assertRedirect(route('customer.dashboard'));

        // 3. Disabling and re-enabling login access does not reactivate old sessions.
        app(CustomerServices::class)->update($customer->fresh(), $this->adminPayload($customer->fresh(), [
            'login_enabled' => false,
        ]));
        app(CustomerServices::class)->update($customer->fresh(), $this->adminPayload($customer->fresh(), [
            'login_enabled' => true,
            'username' => 'renamed_cust_user',
        ]));

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar2)
            ->assertRedirect(route('customer.login'));
    }

    public function test_missing_credential_fingerprint_is_rejected_and_rehash_does_not_invalidate_login(): void
    {
        config(['session.driver' => 'database']);

        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'rehash_fp_cust',
            'password' => Hash::make('RehashPass123'),
            'password_set_at' => now()->subMinute(),
        ]);

        // Set higher cost bcrypt hash so login triggers rehash.
        Customer::query()->whereKey($customer->id)->update([
            'password' => password_hash('RehashPass123', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);

        $jar = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            'username' => 'rehash_fp_cust',
            'password' => 'RehashPass123',
        ])->assertRedirect(route('customer.dashboard'));

        // Rehashed session must remain valid.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertOk();

        // Missing fingerprint in session must be rejected (never silently initialized).
        $staleJar = [];
        $request = \Illuminate\Http\Request::create('/c-portal/login', 'POST');
        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);
        \Illuminate\Support\Facades\Auth::guard('customer')->login($customer->fresh());
        $session->forget(CustomerAuthService::SESSION_FINGERPRINT_KEY);
        $session->save();

        $this->withCookie((string) config('customer_portal.session.cookie'), $session->getId())
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('customer.login'));
    }

    private function adminPayload(Customer $customer, array $overrides = []): array
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
