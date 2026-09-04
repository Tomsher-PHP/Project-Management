<?php

use App\Http\Middleware\EnsureActiveLoginSession;
use App\Http\Middleware\PermissionByType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureActiveLoginSession::class,
        ]);

        $middleware->alias([
            'permission.type' => PermissionByType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log unexpected application errors (skip expected auth and session failures)
        $exceptions->report(function (Throwable $e) {
            if (
                $e instanceof AuthenticationException ||
                $e instanceof TokenMismatchException ||
                ($e instanceof HttpException && in_array($e->getStatusCode(), [401, 419]))
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

            // Let Laravel handle validation and authentication redirects
            if (
                $e instanceof ValidationException ||
                $e instanceof AuthenticationException
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
