<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\CustomerPortal\AuthenticateCustomer;
use App\Http\Middleware\CustomerPortal\ConfigureCustomerSession;
use App\Http\Middleware\CustomerPortal\EnsureCustomerLoginEnabled;
use App\Http\Middleware\CustomerPortal\EnsurePasswordResetVerified;
use App\Http\Middleware\CustomerPortal\RedirectIfCustomerAuthenticated;
use App\Http\Middleware\EnsureActiveLoginSession;
use App\Http\Middleware\PermissionByType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('c-portal')
                ->name('customer.')
                ->middleware('customer.web')
                ->group(base_path('routes/customer.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureActiveLoginSession::class,
        ]);

        $middleware->group('customer.web', [
            ConfigureCustomerSession::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'permission.type' => PermissionByType::class,
            'api.key' => AuthenticateApiKey::class,
            'customer.auth' => AuthenticateCustomer::class,
            'customer.guest' => RedirectIfCustomerAuthenticated::class,
            'customer.access' => EnsureCustomerLoginEnabled::class,
            'customer.password-reset-verified' => EnsurePasswordResetVerified::class,
        ]);

        $middleware->prependToPriorityList(
            ThrottleRequests::class,
            AuthenticateApiKey::class
        );

        $middleware->prependToPriorityList(
            StartSession::class,
            ConfigureCustomerSession::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['otp']);

        // Log unexpected application errors (skip expected auth and session failures)
        $exceptions->report(function (Throwable $e) {
            if (
                $e instanceof AuthenticationException ||
                $e instanceof TokenMismatchException ||
                $e instanceof HttpResponseException ||
                ($e instanceof HttpException && in_array($e->getStatusCode(), [401, 419, 429]))
            ) {
                return false;
            }

            $request = request();

            Log::error('Application Error', [
                'route'   => optional($request->route())->getName(),
                'message' => $e->getMessage(),
            ]);
        });

        // Handle rendering globally (like global try-catch)
        $exceptions->render(function (Throwable $e, Request $request) {

            // Let Laravel handle validation, authentication, and explicit HTTP response exceptions
            if (
                $e instanceof ValidationException ||
                $e instanceof AuthenticationException ||
                $e instanceof HttpResponseException
            ) {
                return null;
            }

            // Determine status code
            $status = 500;
            if ($e instanceof AuthorizationException) {
                $status = 403;
            } elseif ($e instanceof TokenMismatchException) {
                $status = 419;
            } elseif ($e instanceof HttpException) {
                $status = $e->getStatusCode();
            } elseif (method_exists($e, 'getStatusCode')) {
                $status = $e->getStatusCode();
            }

            $message = $e->getMessage();

            // For AJAX / API
            if ($request->expectsJson()) {
                Log::error('Application Error', [
                    'route'   => optional($request->route())->getName(),
                    'message' => $message ?: 'Something went wrong.',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $message ?: 'Something went wrong.',
                ], $status);
            }

            return response()->view('errors.error-page', [
                'code'      => $status,
                'status'    => $status,
                'message'   => $message,
                'exception' => $e,
            ], $status);
        });
    })->create();
