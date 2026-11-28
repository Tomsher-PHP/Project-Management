<?php

namespace Tests\Feature\CustomerPortal;

use App\Http\Middleware\CustomerPortal\AuthenticateCustomer;
use App\Http\Middleware\CustomerPortal\ConfigureCustomerSession;
use App\Http\Middleware\CustomerPortal\EnsureCustomerLoginEnabled;
use App\Http\Middleware\CustomerPortal\RedirectIfCustomerAuthenticated;
use App\Http\Middleware\EnsureActiveLoginSession;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class CustomerPortalFoundationTest extends TestCase
{
    use RefreshDatabase;

    private string $pmsCookieName;

    private string $customerCookieName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pmsCookieName = (string) config('session.cookie');
        $this->customerCookieName = (string) config('customer_portal.session.cookie', 'customer_portal_session');
    }

    public function test_customer_auth_guard_and_provider_are_configured_without_changing_pms_defaults(): void
    {
        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertSame('users', config('auth.defaults.passwords'));

        $this->assertSame([
            'driver' => 'session',
            'provider' => 'customers',
        ], config('auth.guards.customer'));

        $this->assertSame([
            'driver' => 'eloquent',
            'model' => Customer::class,
        ], config('auth.providers.customers'));

        $guard = Auth::guard('customer');
        $this->assertSame(Customer::class, $guard->getProvider()->getModel());
        $this->assertSame('web', Auth::getDefaultDriver());
    }

    public function test_customer_portal_configuration_and_middleware_aliases_are_registered(): void
    {
        $this->assertSame('customer_portal_session', config('customer_portal.session.cookie'));
        $this->assertSame('/c-portal', config('customer_portal.session.path'));
        $this->assertSame(120, config('customer_portal.session.lifetime'));
        $this->assertSame('customer', config('customer_portal.authentication.guard'));

        // Ensure HTTP kernel has booted and synced middleware aliases/groups to the router.
        $this->get(route('login'))->assertOk();

        $router = app('router');
        $aliases = $router->getMiddleware();

        $this->assertSame(AuthenticateCustomer::class, $aliases['customer.auth'] ?? null);
        $this->assertSame(RedirectIfCustomerAuthenticated::class, $aliases['customer.guest'] ?? null);
        $this->assertSame(EnsureCustomerLoginEnabled::class, $aliases['customer.access'] ?? null);

        $groups = $router->getMiddlewareGroups();
        $this->assertArrayHasKey('customer.web', $groups);
        $this->assertSame([
            ConfigureCustomerSession::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
        ], $groups['customer.web']);

        $this->assertNotContains(EnsureActiveLoginSession::class, $groups['customer.web']);
    }

    public function test_customer_routes_use_c_portal_prefix_and_customer_web_middleware_only_once(): void
    {
        $this->registerTestRoutes();

        $route = Route::getRoutes()->getByName('customer._test.protected');

        $this->assertNotNull($route);
        $this->assertSame('c-portal/_test/protected', $route->uri());
        $this->assertSame(['customer.web', 'customer.auth', 'customer.access'], $route->gatherMiddleware());

        $pmsRoute = Route::getRoutes()->getByName('login');
        $this->assertNotNull($pmsRoute);
        $this->assertSame('login', $pmsRoute->uri());
    }

    public function test_customer_session_cookie_uses_dedicated_name_and_path_without_leaking_to_pms(): void
    {
        config(['session.driver' => 'database']);
        $this->registerTestRoutes();

        $customerResponse = $this->get('/c-portal/_test/csrf');
        $customerResponse->assertOk();

        $customerCookie = $this->findResponseCookie($customerResponse, $this->customerCookieName);
        $this->assertNotNull($customerCookie);
        $this->assertSame('/c-portal', $customerCookie->getPath());
        $this->assertTrue($customerCookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $customerCookie->getSameSite()));
        $this->assertNull($this->findResponseCookie($customerResponse, $this->pmsCookieName));

        // Subsequent PMS request in the same process must still use the PMS cookie name and root path.
        $this->assertSame($this->pmsCookieName, config('session.cookie'));
        $this->assertSame('/', config('session.path'));

        $pmsResponse = $this->get('/_test/pms-csrf');
        $pmsResponse->assertOk();

        $pmsCookie = $this->findResponseCookie($pmsResponse, $this->pmsCookieName);
        $this->assertNotNull($pmsCookie);
        $this->assertSame('/', $pmsCookie->getPath());
        $this->assertNull($this->findResponseCookie($pmsResponse, $this->customerCookieName));
    }

    public function test_pms_and_customer_sessions_are_completely_isolated_in_same_browser_context(): void
    {
        config(['session.driver' => 'database']);
        $this->registerTestRoutes();

        $user = User::factory()->create([
            'is_active' => true,
            'delete_status' => false,
            'password' => Hash::make('secret-pass'),
        ]);

        $customer = $this->createPortalCustomer();

        // Simulated browser cookie jar: [cookieName => ['value' => string, 'path' => string]]
        $jar = [];

        // 1. Obtain PMS CSRF token & log in on PMS.
        $pmsCsrfResp = $this->sendWithJar('GET', '/_test/pms-csrf', $jar);
        $pmsCsrfResp->assertOk();
        $pmsToken = $pmsCsrfResp->json('token');

        $pmsLoginResp = $this->sendWithJar('POST', '/_test/pms-login', $jar, [
            '_token' => $pmsToken,
            'user_id' => $user->id,
        ]);
        $pmsLoginResp->assertOk();
        $pmsSessionId = $pmsLoginResp->json('session_id');
        $this->assertArrayHasKey($this->pmsCookieName, $jar);

        // 2. Obtain Customer Portal CSRF token & log in on Customer Portal in the SAME browser jar.
        $custCsrfResp = $this->sendWithJar('GET', '/c-portal/_test/csrf', $jar);
        $custCsrfResp->assertOk();
        $custToken = $custCsrfResp->json('token');
        $this->assertNotSame($pmsToken, $custToken);

        $custLoginResp = $this->sendWithJar('POST', '/c-portal/_test/customer-login', $jar, [
            '_token' => $custToken,
            'customer_id' => $customer->id,
        ]);
        $custLoginResp->assertOk();
        $custSessionId = $custLoginResp->json('session_id');

        $this->assertArrayHasKey($this->customerCookieName, $jar);
        $this->assertNotSame($pmsSessionId, $custSessionId);

        // 3. Verify both sessions remain simultaneously authenticated and independent.
        $pmsMe = $this->sendWithJar('GET', '/_test/pms-me', $jar);
        $pmsMe->assertOk()->assertJson([
            'user_id' => $user->id,
            'session_id' => $pmsSessionId,
            'pms_marker' => 'pms-only',
            'customer_marker' => null,
        ]);

        $custMe = $this->sendWithJar('GET', '/c-portal/_test/customer-me', $jar);
        $custMe->assertOk()->assertJson([
            'customer_id' => $customer->id,
            'session_id' => $custSessionId,
            'customer_marker' => 'customer-only',
            'pms_marker' => null,
        ]);

        // 4. Regenerating Customer Portal session must not change the PMS session ID.
        $custToken = $custMe->json('token');
        $regenResp = $this->sendWithJar('POST', '/c-portal/_test/customer-regenerate', $jar, [
            '_token' => $custToken,
        ]);
        $regenResp->assertOk();
        $newCustSessionId = $regenResp->json('session_id');
        $this->assertNotSame($custSessionId, $newCustSessionId);

        $this->sendWithJar('GET', '/_test/pms-me', $jar)
            ->assertOk()
            ->assertJson([
                'user_id' => $user->id,
                'session_id' => $pmsSessionId,
            ]);

        // 5. Customer logout must not invalidate the PMS session.
        $custToken = $regenResp->json('token');
        $this->sendWithJar('POST', '/c-portal/_test/customer-logout', $jar, [
            '_token' => $custToken,
        ])->assertOk();

        $this->sendWithJar('GET', '/c-portal/_test/protected', $jar)
            ->assertRedirect('/c-portal/login');

        $this->sendWithJar('GET', '/_test/pms-me', $jar)
            ->assertOk()
            ->assertJson([
                'user_id' => $user->id,
                'session_id' => $pmsSessionId,
            ]);

        // 6. Log customer back in, then PMS logout must not invalidate Customer Portal session.
        $custCsrf2 = $this->sendWithJar('GET', '/c-portal/_test/csrf', $jar)->json('token');
        $this->sendWithJar('POST', '/c-portal/_test/customer-login', $jar, [
            '_token' => $custCsrf2,
            'customer_id' => $customer->id,
        ])->assertOk();

        $pmsToken2 = $this->sendWithJar('GET', '/_test/pms-me', $jar)->json('token');
        $this->sendWithJar('POST', '/_test/pms-logout', $jar, [
            '_token' => $pmsToken2,
        ])->assertOk();

        $this->sendWithJar('GET', '/_test/pms-protected', $jar)
            ->assertRedirect(route('login'));

        $this->sendWithJar('GET', '/c-portal/_test/protected', $jar)
            ->assertOk()
            ->assertJson(['customer_id' => $customer->id]);
    }

    public function test_pms_authentication_does_not_grant_customer_portal_access_and_vice_versa(): void
    {
        config(['session.driver' => 'database']);
        $this->registerTestRoutes();

        $user = User::factory()->create(['is_active' => true, 'delete_status' => false]);
        $customer = $this->createPortalCustomer();

        // Authenticated PMS user cannot access Customer Portal protected routes.
        Auth::guard('web')->setUser($user);
        $this->get('/c-portal/_test/protected')
            ->assertRedirect('/c-portal/login');

        $this->getJson('/c-portal/_test/protected')
            ->assertUnauthorized();

        // PMS user is not treated as authenticated customer on guest-only customer routes.
        $this->get('/c-portal/_test/guest-only')
            ->assertOk();

        Auth::guard('web')->forgetUser();

        // Authenticated customer cannot access PMS protected routes, and is redirected away from customer guest-only routes.
        $jar = [];
        $custToken = $this->sendWithJar('GET', '/c-portal/_test/csrf', $jar)->json('token');
        $this->sendWithJar('POST', '/c-portal/_test/customer-login', $jar, [
            '_token' => $custToken,
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->sendWithJar('GET', '/_test/pms-protected', $jar)
            ->assertRedirect(route('login'));

        $this->sendWithJar('GET', '/c-portal/_test/guest-only', $jar)
            ->assertRedirect('/c-portal/dashboard');
    }

    public function test_disabled_inactive_and_soft_deleted_customers_are_blocked_and_session_revoked(): void
    {
        config(['session.driver' => 'database']);
        $this->registerTestRoutes();

        $user = User::factory()->create(['is_active' => true, 'delete_status' => false]);
        $disabledCustomer = $this->createPortalCustomer(['login_enabled' => false]);
        $inactiveCustomer = $this->createPortalCustomer(['is_active' => false]);
        $deletedCustomer = $this->createPortalCustomer();
        $deletedCustomer->delete();

        $jar = [];
        $pmsToken = $this->sendWithJar('GET', '/_test/pms-csrf', $jar)->json('token');
        $this->sendWithJar('POST', '/_test/pms-login', $jar, [
            '_token' => $pmsToken,
            'user_id' => $user->id,
        ])->assertOk();

        foreach ([$disabledCustomer, $inactiveCustomer, $deletedCustomer] as $ineligible) {
            $custToken = $this->sendWithJar('GET', '/c-portal/_test/csrf', $jar)->json('token');
            $this->sendWithJar('POST', '/c-portal/_test/customer-login', $jar, [
                '_token' => $custToken,
                'customer_id' => $ineligible->id,
            ])->assertOk();

            $this->sendWithJar('GET', '/c-portal/_test/protected', $jar)
                ->assertRedirect('/c-portal/login');

            // Subsequent access must now fail at customer.auth (session was revoked).
            $this->sendWithJar('GET', '/c-portal/_test/protected', $jar, [], true)
                ->assertUnauthorized();

            // PMS employee session in the same browser jar must still be intact.
            $this->sendWithJar('GET', '/_test/pms-me', $jar)
                ->assertOk()
                ->assertJson(['user_id' => $user->id]);
        }
    }

    public function test_customer_portal_retains_csrf_protection(): void
    {
        $this->get(route('login'))->assertOk();

        $groups = app('router')->getMiddlewareGroups();
        $this->assertContains(ValidateCsrfToken::class, $groups['customer.web']);

        $csrfMiddleware = new class(app(), app('encrypter')) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };

        $request = Request::create('/c-portal/_test/customer-login', 'POST');
        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $csrfMiddleware->handle($request, fn () => response('ok'));
    }

    public function test_existing_pms_login_route_remains_functional(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_configuration_and_route_caching_are_compatible(): void
    {
        try {
            $this->assertSame(0, Artisan::call('config:cache'));
            $this->assertSame(0, Artisan::call('route:cache'));
        } finally {
            Artisan::call('route:clear');
            Artisan::call('config:clear');
        }
    }

    private function createPortalCustomer(array $overrides = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'cust_' . fake()->unique()->userName(),
            'password' => Hash::make('customer-secret'),
            'password_set_at' => now(),
        ], $overrides));
    }

    private function registerTestRoutes(): void
    {
        Route::middleware('web')->group(function () {
            Route::get('/_test/pms-csrf', function (Request $request) {
                return response()->json([
                    'token' => $request->session()->token(),
                ]);
            });

            Route::post('/_test/pms-login', function (Request $request) {
                $user = User::findOrFail($request->integer('user_id'));
                Auth::guard('web')->login($user);
                $request->session()->put('pms_marker', 'pms-only');
                $request->session()->save();

                return response()->json([
                    'session_id' => $request->session()->getId(),
                ]);
            });

            Route::get('/_test/pms-me', function (Request $request) {
                return response()->json([
                    'user_id' => Auth::guard('web')->id(),
                    'session_id' => $request->session()->getId(),
                    'pms_marker' => $request->session()->get('pms_marker'),
                    'customer_marker' => $request->session()->get('customer_marker'),
                    'token' => $request->session()->token(),
                ]);
            });

            Route::post('/_test/pms-logout', function (Request $request) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json(['ok' => true]);
            });

            Route::get('/_test/pms-protected', function () {
                return response()->json(['user_id' => Auth::guard('web')->id()]);
            })->middleware('auth');
        });

        Route::prefix('c-portal')
            ->name('customer.')
            ->middleware('customer.web')
            ->group(function () {
                Route::get('/_test/csrf', function (Request $request) {
                    return response()->json([
                        'token' => $request->session()->token(),
                    ]);
                });

                Route::post('/_test/customer-login', function (Request $request) {
                    $customer = Customer::withTrashed()->findOrFail($request->integer('customer_id'));
                    Auth::guard('customer')->login($customer);
                    $authService = app(\App\Services\CustomerPortal\Auth\CustomerAuthService::class);
                    $request->session()->put(
                        \App\Services\CustomerPortal\Auth\CustomerAuthService::SESSION_FINGERPRINT_KEY,
                        $authService->credentialFingerprint($customer)
                    );
                    $request->session()->put(
                        \App\Services\CustomerPortal\Auth\CustomerAuthService::SESSION_AUTHENTICATED_AT_KEY,
                        microtime(true)
                    );
                    $request->session()->put('customer_marker', 'customer-only');
                    $request->session()->save();

                    return response()->json([
                        'session_id' => $request->session()->getId(),
                    ]);
                });

                Route::get('/_test/customer-me', function (Request $request) {
                    return response()->json([
                        'customer_id' => Auth::guard('customer')->id(),
                        'session_id' => $request->session()->getId(),
                        'customer_marker' => $request->session()->get('customer_marker'),
                        'pms_marker' => $request->session()->get('pms_marker'),
                        'token' => $request->session()->token(),
                    ]);
                });

                Route::post('/_test/customer-regenerate', function (Request $request) {
                    $request->session()->regenerate();

                    return response()->json([
                        'session_id' => $request->session()->getId(),
                        'token' => $request->session()->token(),
                    ]);
                });

                Route::post('/_test/customer-logout', function (Request $request) {
                    Auth::guard('customer')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return response()->json(['ok' => true]);
                });

                Route::get('/_test/protected', function () {
                    return response()->json([
                        'customer_id' => Auth::guard('customer')->id(),
                    ]);
                })->middleware(['customer.auth', 'customer.access'])->name('_test.protected');

                Route::get('/_test/guest-only', function () {
                    return response()->json(['guest' => true]);
                })->middleware('customer.guest')->name('_test.guest');
            });

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Send a request using a simulated browser cookie jar that respects cookie paths.
     */
    private function sendWithJar(
        string $method,
        string $uri,
        array &$jar,
        array $data = [],
        bool $asJson = false
    ) {
        $cookiesToSend = [];
        foreach ($jar as $name => $cookie) {
            if (str_starts_with($uri, $cookie['path'])) {
                $cookiesToSend[$name] = $cookie['value'];
            }
        }

        $headers = $asJson ? ['Accept' => 'application/json'] : [];
        $server = $this->transformHeadersToServerVars($headers);

        $response = $this->call($method, $uri, $data, $cookiesToSend, [], $server);

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

    private function findResponseCookie($response, string $name): ?Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }
}
