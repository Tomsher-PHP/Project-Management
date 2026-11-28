<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\DataSyncOutboundHistory;
use App\Models\User;
use App\Services\DataSync\OutboundDataSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('customer-portal-ip:127.0.0.1');
    }

    public function test_customer_login_page_renders_correctly(): void
    {
        $response = $this->get(route('customer.login'));

        $response->assertOk();
        $response->assertSeeText('Customer Portal');
        $response->assertSeeText('Username');
        $response->assertSeeText('Sign In');
        $response->assertSee('name="username"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="_token"', false);
        $response->assertDontSee('name="remember"', false);
        $response->assertSeeText('Forgot Password?');
        $response->assertSee(route('customer.password.request'), false);
    }

    public function test_customer_can_login_with_custom_username_and_normalized_input(): void
    {
        config(['session.driver' => 'database']);

        $customer = $this->createPortalCustomer([
            'username' => 'acme_portal_user',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $jar = [];
        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $token = $this->extractCsrfToken($loginPage->getContent());

        $response = $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $token,
            'username' => '  ACME_Portal_User  ',
            'password' => 'SecretPass123!',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertOk()
            ->assertSeeText($customer->name);
        $this->assertNotNull($customer->fresh()->last_login_at);
    }

    public function test_login_rejects_invalid_and_ineligible_accounts_with_generic_message(): void
    {
        config(['session.driver' => 'database']);

        $active = $this->createPortalCustomer([
            'username' => 'active_cust',
            'password' => Hash::make('ValidPass123!'),
        ]);

        $disabled = $this->createPortalCustomer([
            'username' => 'disabled_cust',
            'login_enabled' => false,
            'password' => Hash::make('ValidPass123!'),
        ]);

        $inactive = $this->createPortalCustomer([
            'username' => 'inactive_cust',
            'is_active' => false,
            'password' => Hash::make('ValidPass123!'),
        ]);

        $deleted = $this->createPortalCustomer([
            'username' => 'deleted_cust',
            'password' => Hash::make('ValidPass123!'),
        ]);
        $deleted->delete();

        $noPassword = $this->createPortalCustomer([
            'username' => 'nopass_cust',
            'password' => null,
            'password_set_at' => null,
        ]);

        $cases = [
            ['username' => 'unknown_user_999', 'password' => 'ValidPass123!'],
            ['username' => $active->username, 'password' => 'WrongPassword!'],
            ['username' => $disabled->username, 'password' => 'ValidPass123!'],
            ['username' => $inactive->username, 'password' => 'ValidPass123!'],
            ['username' => $deleted->username, 'password' => 'ValidPass123!'],
            ['username' => $noPassword->username, 'password' => ''],
        ];

        $jar = [];
        foreach ($cases as $payload) {
            $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
            $token = $this->extractCsrfToken($loginPage->getContent());

            $resp = $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, array_merge($payload, [
                '_token' => $token,
            ]));

            $resp->assertRedirect(route('customer.login'));
            $redirected = $this->sendWithJar('GET', route('customer.login', [], false), $jar);

            if ($payload['password'] === '') {
                $redirected->assertSeeText('The password field is required.');
            } else {
                $redirected->assertSeeText('The provided credentials are incorrect.');
            }

            $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
                ->assertRedirect(route('customer.login'));
        }

        $this->assertNull($active->fresh()->last_login_at);
        $this->assertNull($disabled->fresh()->last_login_at);
        $this->assertNull($inactive->fresh()->last_login_at);
    }

    public function test_login_rate_limiting_throttles_after_max_attempts_and_clears_on_success(): void
    {
        config(['session.driver' => 'database']);

        $customer = $this->createPortalCustomer([
            'username' => 'throttled_cust',
            'password' => Hash::make('CorrectPass123!'),
        ]);

        $jar = [];
        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $token = $this->extractCsrfToken($loginPage->getContent());

        for ($i = 0; $i < 5; $i++) {
            $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
                '_token' => $token,
                'username' => 'throttled_cust',
                'password' => 'WrongPass!' . $i,
            ])->assertRedirect(route('customer.login'));
        }

        $throttledResponse = $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $token,
            'username' => 'throttled_cust',
            'password' => 'CorrectPass123!',
        ]);

        $throttledResponse->assertRedirect(route('customer.login'));
        $this->sendWithJar('GET', route('customer.login', [], false), $jar)
            ->assertSeeText('Too many login attempts');

        // Clear rate limiter and verify successful login succeeds.
        RateLimiter::clear('customer-portal-login:' . sha1('throttled_cust|127.0.0.1'));

        $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $token,
            'username' => 'throttled_cust',
            'password' => 'CorrectPass123!',
        ])->assertRedirect(route('customer.dashboard'));

        $this->sendWithJar('GET', route('customer.dashboard', [], false), $jar)
            ->assertOk()
            ->assertSeeText($customer->name);
    }

    public function test_successful_login_updates_last_login_at_without_modifying_audit_columns_or_triggering_data_sync(): void
    {
        $creator = User::factory()->create(['is_active' => true, 'delete_status' => false]);
        $updater = User::factory()->create(['is_active' => true, 'delete_status' => false]);

        $customer = $this->createPortalCustomer([
            'username' => 'audit_safe_cust',
            'added_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        // Set higher bcrypt cost and historical audit columns directly to exercise rehash-on-login without cast error.
        Customer::query()->whereKey($customer->id)->update([
            'password' => password_hash('AuditSafe123!', PASSWORD_BCRYPT, ['cost' => 10]),
            'added_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        $syncServiceSpy = $this->spy(OutboundDataSyncService::class);
        $activityCountBefore = Activity::query()->count();
        $syncCountBefore = DataSyncOutboundHistory::query()->count();

        $this->post(route('customer.login.store'), [
            'username' => 'audit_safe_cust',
            'password' => 'AuditSafe123!',
        ])->assertRedirect(route('customer.dashboard'));

        $fresh = $customer->fresh();
        $this->assertNotNull($fresh->last_login_at);
        $this->assertSame($creator->id, $fresh->added_by);
        $this->assertSame($updater->id, $fresh->updated_by);
        $this->assertSame($activityCountBefore, Activity::query()->count());
        $this->assertSame($syncCountBefore, DataSyncOutboundHistory::query()->count());
        $syncServiceSpy->shouldNotHaveReceived('handle');
    }

    public function test_login_regenerates_session_id_and_never_flashes_or_logs_password(): void
    {
        config(['session.driver' => 'database']);

        $customer = $this->createPortalCustomer([
            'username' => 'session_regen_cust',
            'password' => Hash::make('SuperSecretPassword99!'),
        ]);

        Log::spy();

        $jar = [];
        $loginPage = $this->sendWithJar('GET', route('customer.login', [], false), $jar);
        $loginPage->assertOk();

        // Extract CSRF token from form HTML
        preg_match('/name="_token"\s+value="([^"]+)"/', $loginPage->getContent(), $matches);
        $csrfToken = $matches[1] ?? '';
        $this->assertNotEmpty($csrfToken);

        $beforeCookie = $jar[(string) config('customer_portal.session.cookie')]['value'] ?? null;

        $loginResp = $this->sendWithJar('POST', route('customer.login.store', [], false), $jar, [
            '_token' => $csrfToken,
            'username' => 'session_regen_cust',
            'password' => 'SuperSecretPassword99!',
        ]);
        $loginResp->assertRedirect(route('customer.dashboard'));

        $afterCookie = $jar[(string) config('customer_portal.session.cookie')]['value'] ?? null;
        $this->assertNotNull($afterCookie);
        $this->assertNotSame($beforeCookie, $afterCookie);

        // Failed login must preserve username but never flash password in old input.
        $failedJar = [];
        $failedLoginPage = $this->sendWithJar('GET', route('customer.login', [], false), $failedJar);
        $failedToken = $this->extractCsrfToken($failedLoginPage->getContent());

        $failedResp = $this->sendWithJar('POST', route('customer.login.store', [], false), $failedJar, [
            '_token' => $failedToken,
            'username' => 'session_regen_cust',
            'password' => 'WrongSecret99!',
        ]);
        $failedResp->assertRedirect(route('customer.login'));

        $renderedLogin = $this->sendWithJar('GET', route('customer.login', [], false), $failedJar);
        $renderedLogin->assertOk();
        $renderedLogin->assertSee('value="session_regen_cust"', false);
        $renderedLogin->assertDontSee('WrongSecret99!', false);

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('error');
    }

    private function extractCsrfToken(string $html): string
    {
        if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function createPortalCustomer(array $overrides = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'cust_' . fake()->unique()->userName(),
            'password' => Hash::make('SecretPass123!'),
            'password_set_at' => now(),
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
