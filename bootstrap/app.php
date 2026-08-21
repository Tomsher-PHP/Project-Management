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

            // Let Laravel handle expected exceptions normally
            if (
                $e instanceof ValidationException ||
                $e instanceof AuthenticationException ||
                $e instanceof TokenMismatchException ||
                $e instanceof AuthorizationException ||
                ($e instanceof HttpException && in_array(
                    $e->getStatusCode(),
                    [401, 403, 404, 419]
                ))
            ) {
                return null;
            }

            // For AJAX / API
            if ($request->expectsJson()) {
                Log::error('Application Error', [
                    'route'   => optional($request->route())->getName(),
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong.',
                ], 500);
            }
            return response()->view('errors.403', [], 403);
        });
    })->create();
