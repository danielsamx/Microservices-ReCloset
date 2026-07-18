<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Cargamos routes/api.php con el grupo de middleware 'api' pero SIN el
        // prefijo /api automático: las rutas ya declaran su path completo
        // (/health, /metrics, /api/auth/..., etc.).
        then: function () {
            Route::middleware('api')->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\RequestMetrics::class,
        ]);
        $middleware->alias([
            'service.auth' => \App\Http\Middleware\AuthenticateService::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, Request $r) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        });
        $exceptions->render(function (AuthenticationException $e, Request $r) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        });
    })->create();
