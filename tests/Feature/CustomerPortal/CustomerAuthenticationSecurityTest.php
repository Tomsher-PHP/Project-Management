<?php

namespace Tests\Feature\CustomerPortal;

use App\Http\Middleware\PermissionByType;
use App\Mail\CustomerPortal\PasswordResetOtpMail;
use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use App\Models\User;
use App\Services\CustomerRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CustomerAuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('customer-portal-ip:127.0.0.1');
        RateLimiter::clear('customer-portal-forgot-ip-min:127.0.0.1');
        RateLimiter::clear('customer-portal-forgot-ip-hr:127.0.0.1');
        RateLimiter::clear('customer-portal-otp-verify-ip:127.0.0.1');
        RateLimiter::clear('customer-portal-password-reset-ip:127.0.0.1');
    }

    public function test_auth_and_recovery_pages_send_no_store_cache_headers(): void
    {
        $loginResp = $this->get(route('customer.login'));
        $loginResp->assertOk();
        $this->assertStringContainsString('no-store', strtolower((string) $loginResp->headers->get('Cache-Control')));

        $forgotResp = $this->get(route('customer.password.request'));
        $forgotResp->assertOk();
        $this->assertStringContainsString('no-store', strtolower((string) $forgotResp->headers->get('Cache-Control')));
    }

    public function test_customer_deactivate_and_reactivate_revokes_sessions_and_invalidates_recovery(): void
    {
        config(['session.driver' => 'database']);

        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);

        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'toggle_status_sec',
            'email' => 'toggle-sec@example.com',
            'password' => Hash::make('SecretPass123!'),
            'password_set_at' => now()->subMinute(),
            'sales_person_id' => $admin->id,
        ]);

        // Active customer session + verified recovery authorization
        $sessionJar = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $sessionJar, [
            'username' => $customer->username,
            'password' => 'SecretPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        $recoveryJar = $this->initiateVerifiedRecovery($customer->username);

        // Deactivate and then reactivate via CustomerController::toggleStatus
        $this->actingAs($admin, 'web')
            ->withoutMiddleware([PermissionByType::class])
            ->patch(route('customers.toggleStatus'), ['id' => $customer->id])
            ->assertOk();

        $this->assertFalse($customer->fresh()->is_active);

        $this->actingAs($admin, 'web')
            ->withoutMiddleware([PermissionByType::class])
            ->patch(route('customers.toggleStatus'), ['id' => $customer->id])
            ->assertOk();

        $this->assertTrue($customer->fresh()->is_active);

        // Previously issued session and recovery authorization remain invalid even after reactivation!
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $sessionJar)
            ->assertRedirect(route('customer.login'));

        $this->sendWithJar('GET', route('customer.password.reset', [], false), $recoveryJar)
            ->assertRedirect(route('customer.password.request'));
    }

    public function test_customer_soft_delete_and_restore_revokes_sessions_and_invalidates_recovery(): void
    {
        config(['session.driver' => 'database']);

        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);

        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'delete_restore_sec',
            'email' => 'delete-sec@example.com',
            'password' => Hash::make('SecretPass123!'),
            'password_set_at' => now()->subMinute(),
            'sales_person_id' => $admin->id,
        ]);

        $sessionJar = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $sessionJar, [
            'username' => $customer->username,
            'password' => 'SecretPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        $recoveryJar = $this->initiateVerifiedRecovery($customer->username);

        // Soft delete and restore
        $customer->delete();
        app(CustomerRestoreService::class)->restoreDeletedCustomer($customer->fresh());

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $sessionJar)
            ->assertRedirect(route('customer.login'));

        $this->sendWithJar('GET', route('customer.password.reset', [], false), $recoveryJar)
            ->assertRedirect(route('customer.password.request'));
    }

    public function test_cache_flush_after_password_reset_still_revokes_session_via_credential_fingerprint(): void
    {
        config(['session.driver' => 'database']);

        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'cache_flush_sec',
            'email' => 'cache-flush@example.com',
            'password' => Hash::make('OldPass123!'),
            'password_set_at' => now()->subMinute(),
        ]);

        $sessionJar = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $sessionJar, [
            'username' => $customer->username,
            'password' => 'OldPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        $recoveryJar = $this->initiateVerifiedRecovery($customer->username);
        $this->sendWithJar('POST', route('customer.password.update', [], false), $recoveryJar, [
            'password' => 'NewPass999!',
            'password_confirmation' => 'NewPass999!',
        ])->assertRedirect(route('customer.login'));

        // Simulate cache flush/eviction
        Cache::flush();

        // Password-changed session is STILL revoked by credential_fingerprint even after Cache::flush().
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $sessionJar)
            ->assertRedirect(route('customer.login'));
    }

    private function initiateVerifiedRecovery(string $username): array
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

        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
            'otp' => $sentOtp,
        ])->assertRedirect(route('customer.password.reset'));

        return $jar;
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
