<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_root_and_dashboard_redirect_unauthenticated_users_to_customer_login(): void
    {
        $this->get(route('customer.index'))
            ->assertRedirect(route('customer.login'));

        $this->get(route('customer.dashboard'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_authenticated_customer_can_access_dashboard_and_is_redirected_from_root_and_login(): void
    {
        config(['session.driver' => 'database']);

        $customer = Customer::factory()->create([
            'name' => 'Acme Portal Corp',
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'acme_access_user',
            'password' => Hash::make('AccessPass123!'),
            'password_set_at' => now(),
        ]);

        $jar = [];
        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        preg_match('/name="_token"\s+value="([^"]+)"/', $loginPage->getContent(), $matches);

        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $matches[1] ?? '',
            'username' => 'acme_access_user',
            'password' => 'AccessPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        // /c-portal redirects to dashboard
        $this->sendWithJar('GET', route('customer.index', [], false), $jar)
            ->assertRedirect(route('customer.dashboard'));

        // /c-portal/login redirects to dashboard
        $this->sendWithJar('GET', route('customer.login', [], false), $jar)
            ->assertRedirect(route('customer.dashboard'));

        // Dashboard renders customer company name without PMS sidebar/employee navigation
        $dash = $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar);
        $dash->assertOk();
        $dash->assertSeeText('Dashboard');
        $dash->assertSeeText('Acme Portal Corp');
        $dash->assertDontSee('user-workspace', false);
    }

    public function test_disabled_customer_loses_access_on_next_protected_request_without_redirect_loop(): void
    {
        config(['session.driver' => 'database']);

        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'revoke_access_user',
            'password' => Hash::make('RevokePass123!'),
            'password_set_at' => now(),
        ]);

        $jar = [];
        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        preg_match('/name="_token"\s+value="([^"]+)"/', $loginPage->getContent(), $matches);

        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $matches[1] ?? '',
            'username' => 'revoke_access_user',
            'password' => 'RevokePass123!',
        ])->assertRedirect(route('customer.dashboard'));

        // Disable customer login access mid-session.
        Customer::query()->whereKey($customer->id)->update(['login_enabled' => false]);

        // Next protected request revokes session and redirects to login.
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertRedirect(route('customer.login'));

        // Following redirect to login renders 200 OK (no redirect loop).
        $this->sendWithJar('GET', route('customer.login', [], false), $jar)
            ->assertOk();
    }

    public function test_employee_and_customer_authentication_do_not_cross_grant_access(): void
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
            'username' => 'isolated_cust',
            'password' => Hash::make('CustPass123!'),
            'password_set_at' => now(),
        ]);

        // Employee-only session cannot access Customer Portal dashboard.
        $empJar = [];
        $pmsLogin = $this->sendWithJar('GET', route('login', [], false), $empJar);
        preg_match('/name="_token"\s+value="([^"]+)"/', $pmsLogin->getContent(), $pmsMatches);
        $this->sendWithJar('POST', route('login.post', [], false), $empJar, [
            '_token' => $pmsMatches[1] ?? '',
            'email' => $employee->email,
            'password' => 'EmpPass123!',
        ])->assertRedirect();

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $empJar)
            ->assertRedirect(route('customer.login'));

        // Customer-only session cannot access PMS employee workspace.
        $custJar = [];
        $custLogin = $this->sendWithJar('GET', route('customer.login', [], false), $custJar);
        preg_match('/name="_token"\s+value="([^"]+)"/', $custLogin->getContent(), $custMatches);
        $this->sendWithJar('POST', route('customer.login.store', [], false), $custJar, [
            '_token' => $custMatches[1] ?? '',
            'username' => 'isolated_cust',
            'password' => 'CustPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        $this->sendWithJar('GET', route('user.workspace', [], false), $custJar)
            ->assertRedirect(route('login'));
    }

    public function test_route_and_config_caching_remain_compatible_with_customer_portal_routes(): void
    {
        try {
            $this->assertSame(0, Artisan::call('config:cache'));
            $this->assertSame(0, Artisan::call('route:cache'));
        } finally {
            Artisan::call('route:clear');
            Artisan::call('config:clear');
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
