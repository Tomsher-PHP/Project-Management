<?php

namespace Tests\Feature\CustomerPortal;

use App\Http\Middleware\PermissionByType;
use App\Mail\CustomerPortal\PasswordResetOtpMail;
use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CustomerAuthenticationIntegrationTest extends TestCase
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

    public function test_complete_end_to_end_customer_authentication_and_password_recovery_workflow(): void
    {
        config(['session.driver' => 'database']);

        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);

        // 1. Admin creates customer with login access disabled.
        $this->actingAs($admin, 'web')
            ->withoutMiddleware([PermissionByType::class])
            ->post(route('customers.store'), [
                'name' => 'E2E Portal Corp',
                'email' => 'e2e-portal@example.com',
                'sales_person_id' => $admin->id,
                'login_enabled' => '0',
                'primary_name' => 'Primary Contact',
                'primary_email' => 'primary@example.com',
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('name', 'E2E Portal Corp')->sole();
        $this->assertFalse($customer->login_enabled);

        // 2. Admin enables customer login access.
        $this->actingAs($admin, 'web')
            ->withoutMiddleware([PermissionByType::class])
            ->put(route('customers.update', $customer), [
                'name' => 'E2E Portal Corp',
                'email' => 'e2e-portal@example.com',
                'sales_person_id' => $admin->id,
                'login_enabled' => '1',
                'username' => 'e2e_portal_user@example.com',
                'password' => 'InitialPass123',
                'password_confirmation' => 'InitialPass123',
                'primary_name' => 'Primary Contact',
                'primary_email' => 'primary@example.com',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertTrue($customer->login_enabled);
        $this->assertSame('e2e_portal_user@example.com', $customer->username);

        // 3. Customer accesses /c-portal -> redirected to /c-portal/login.
        $jar = [];
        $this->sendWithJar('GET', route('customer.index', [], false), $jar)
            ->assertRedirect(route('customer.login'));

        // 4. Customer logs in with username and password.
        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $loginPage->assertOk();

        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            'username' => 'e2e_portal_user@example.com',
            'password' => 'InitialPass123',
        ])->assertRedirect(route('customer.dashboard'));

        // 5. Customer accesses protected dashboard.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertOk()
            ->assertSeeText('E2E Portal Corp');

        // Also keep a second active session in another browser to verify revocation on reset.
        $otherBrowserJar = [];
        $this->sendWithJar('POST', route('customer.login.store', [], false), $otherBrowserJar, [
            'username' => 'e2e_portal_user@example.com',
            'password' => 'InitialPass123',
        ])->assertRedirect(route('customer.dashboard'));

        // 6. Customer logs out from first browser.
        $this->sendWithJar('POST', route('customer.logout', [], false), $jar)
            ->assertRedirect(route('customer.login'));

        // 7-10. Customer visits Forgot Password, submits username, OTP queued & delivered to company email.
        $this->sendWithJar('GET', route('customer.password.request', [], false), $jar)->assertOk();

        Mail::fake();
        $sentOtp = null;
        $this->sendWithJar('POST', route('customer.password.otp.send', [], false), $jar, [
            'username' => 'e2e_portal_user@example.com',
        ])->assertRedirect(route('customer.password.otp'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$sentOtp) {
            $sentOtp = $mail->otp;

            return $mail->hasTo('e2e-portal@example.com') && ! $mail->hasTo('primary@example.com');
        });

        // 11-12. Customer verifies OTP and accesses Reset Password page.
        $this->sendWithJar('POST', route('customer.password.otp.verify', [], false), $jar, [
            'otp' => $sentOtp,
        ])->assertRedirect(route('customer.password.reset'));

        $this->sendWithJar('GET', route('customer.password.reset', [], false), $jar)->assertOk();

        // 13-17. Customer submits new password -> consumed, other sessions revoked, redirected to Login.
        $this->sendWithJar('POST', route('customer.password.update', [], false), $jar, [
            'password' => 'RecoveredPass999',
            'password_confirmation' => 'RecoveredPass999',
        ])->assertRedirect(route('customer.login'));

        $resetRecord = CustomerPasswordReset::query()->where('customer_id', $customer->id)->sole();
        $this->assertNotNull($resetRecord->used_at);
        $this->assertTrue(Hash::check('RecoveredPass999', $customer->fresh()->password));

        // Second browser session was revoked.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $otherBrowserJar)
            ->assertRedirect(route('customer.login'));

        // 18. Customer logs in with new password.
        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            'username' => 'e2e_portal_user@example.com',
            'password' => 'RecoveredPass999',
        ])->assertRedirect(route('customer.dashboard'));

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertOk()
            ->assertSeeText('E2E Portal Corp');
    }

    public function test_actual_csrf_enforcement_on_customer_portal_post_routes(): void
    {
        $csrfMiddleware = new class(app(), app('encrypter')) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };

        $session = app('session')->driver();
        $session->start();

        foreach ([
            '/c-portal/login',
            '/c-portal/logout',
            '/c-portal/forgot-password',
            '/c-portal/verify-otp',
            '/c-portal/resend-otp',
            '/c-portal/reset-password',
        ] as $uri) {
            $request = Request::create($uri, 'POST');
            $request->setLaravelSession($session);

            $threw = false;
            try {
                $csrfMiddleware->handle($request, fn () => response('ok'));
            } catch (\Illuminate\Session\TokenMismatchException) {
                $threw = true;
            }

            $this->assertTrue($threw, "Expected TokenMismatchException for POST {$uri}");
        }
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
