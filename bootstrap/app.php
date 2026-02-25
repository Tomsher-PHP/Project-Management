<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\PermissionByType;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission.type' => PermissionByType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions
        $exceptions->report(function (Throwable $e) {
            $request = request();

            Log::error('Application Error', [
                'route'   => optional($request->route())->getName(),
                'message' => $e->getMessage(),
                // 'url'     => $request->fullUrl(),
                // 'method'  => $request->method(),
                // 'ip'      => $request->ip(),
            ]);
        });

        // Handle rendering globally (like global try-catch)
        $exceptions->render(function (Throwable $e, Request $request) {

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

            // For normal web requests
            return redirect()
                ->back()
                ->with('error', 'Something went wrong. Please try again.')
                ->withInput();
        });
    })->create();
