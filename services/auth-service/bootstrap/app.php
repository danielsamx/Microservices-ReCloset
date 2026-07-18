<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

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
        // Todos los mensajes visibles para el usuario, en español y sin detalles técnicos.
        $exceptions->render(function (ValidationException $e, Request $r) {
            return response()->json([
                'message' => 'Revisa los datos del formulario.',
                'errors' => $e->errors(),
            ], 422);
        });
        $exceptions->render(function (AuthenticationException $e, Request $r) {
            return response()->json(['message' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
        });
        $exceptions->render(function (AccessDeniedHttpException $e, Request $r) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $r) {
            return response()->json(['message' => 'No encontramos lo que buscas.'], 404);
        });
        $exceptions->render(function (PostTooLargeException $e, Request $r) {
            return response()->json(['message' => 'Los archivos son demasiado grandes. El máximo permitido es 50 MB por archivo.'], 413);
        });
        $exceptions->render(function (ThrottleRequestsException $e, Request $r) {
            return response()->json(['message' => 'Demasiados intentos. Espera un momento e inténtalo de nuevo.'], 429);
        });
    })->create();
