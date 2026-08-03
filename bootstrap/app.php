<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Set memory limit middleware
        $middleware->web(append: [
            \App\Http\Middleware\SetMemoryLimit::class,
        ]);

        // Register middleware alias for role-based redirect
        $middleware->alias([
            'redirect.role' => \App\Http\Middleware\RedirectBasedOnRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (app()->environment('local') || config('app.debug')) {
                return null;
            }

            if ($request->expectsJson()) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return response()->view('errors.generic', [
                'status' => $status,
                'message' => $status === 500
                    ? 'Terjadi gangguan pada sistem. Silakan coba lagi beberapa saat.'
                    : 'Permintaan tidak dapat diproses.',
            ], $status);
        });
    })->create();
