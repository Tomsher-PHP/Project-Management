<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_request_cannot_perform_customer_logout(): void
    {
        $this->get('/c-portal/logout')
            ->assertStatus(405);
    }

    public function test_unauthenticated_post_to_logout_redirects_to_customer_login(): void
    {
        $this->post(route('customer.logout'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_customer_logout_invalidates_only_customer_session_and_preserves_pms_session(): void
    {
        config(['session.driver' => 'database']);

        $employee = User::factory()->create([
            'is_active' => true,
            'delete_status' => false,
            'password' => Hash::make('EmpPass123!'),
        ]);

        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'logout_test_cust',
            'password' => Hash::make('CustPass123!'),
            'password_set_at' => now(),
        ]);

        $jar = [];

        // 1. Log in as PMS employee on web guard.
        $pmsLoginPage = $this->sendWithJar('GET', route('login', [], false), $jar);
        $pmsToken = $this->extractCsrfToken($pmsLoginPage->getContent());

        // Use test-style PMS session login via AuthController::login.post
        $this->sendWithJar('POST', route('login.post', [], false), $jar, [
            '_token' => $pmsToken,
            'email' => $employee->email,
            'password' => 'EmpPass123!',
        ])->assertRedirect();

        // 2. Log in as Customer in the same browser cookie jar.
        $custLoginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $custToken = $this->extractCsrfToken($custLoginPage->getContent());

        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $custToken,
            'username' => 'logout_test_cust',
            'password' => 'CustPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        // Verify dashboard is accessible.
        $dashResp = $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar);
        $dashResp->assertOk()->assertSeeText($customer->name);

        // 3. Log out Customer Portal.
        $dashToken = $this->extractCsrfToken($dashResp->getContent());
        $this->sendWithJar('POST', route('customer.logout', [], false), $jar, [
            '_token' => $dashToken,
        ])->assertRedirect(route('customer.login'));

        // Customer dashboard is no longer accessible.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertRedirect(route('customer.login'));

        // PMS employee session in the same browser jar is STILL authenticated.
        $this->sendWithJar('GET', route('user.workspace', [], false), $jar)
            ->assertOk();
    }

    public function test_pms_logout_does_not_invalidate_customer_session(): void
    {
        config(['session.driver' => 'database']);

        $employee = User::factory()->create([
            'is_active' => true,
            'delete_status' => false,
            'password' => Hash::make('EmpPass123!'),
        ]);

        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'pms_logout_cust',
            'password' => Hash::make('CustPass123!'),
            'password_set_at' => now(),
        ]);

        $jar = [];

        // 1. Log in on PMS.
        $pmsLoginPage = $this->sendWithJar('GET', route('login', [], false), $jar);
        $pmsToken = $this->extractCsrfToken($pmsLoginPage->getContent());
        $this->sendWithJar('POST', route('login.post', [], false), $jar, [
            '_token' => $pmsToken,
            'email' => $employee->email,
            'password' => 'EmpPass123!',
        ])->assertRedirect();

        // 2. Log in on Customer Portal.
        $custLoginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $custToken = $this->extractCsrfToken($custLoginPage->getContent());
        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $custToken,
            'username' => 'pms_logout_cust',
            'password' => 'CustPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        // 3. Log out of PMS.
        $workspaceResp = $this->sendWithJar('GET', route('user.workspace', [], false), $jar);
        $workspaceResp->assertOk();
        $logoutToken = $this->extractCsrfToken($workspaceResp->getContent());

        $this->sendWithJar('POST', route('logout', [], false), $jar, [
            '_token' => $logoutToken,
        ])->assertRedirect(route('login'));

        // PMS workspace now redirects to PMS login.
        $this->sendWithJar('GET', route('user.workspace', [], false), $jar)
            ->assertRedirect(route('login'));

        // Customer Portal dashboard is STILL accessible!
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertOk()
            ->assertSeeText($customer->name);
    }

    private function extractCsrfToken(string $html): string
    {
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

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
