<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\InternalUserController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/metrics', [MetricsController::class, 'metrics']);

Route::prefix('api/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Recuperación de contraseña (público)
    Route::post('/password/forgot', [PasswordController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/password/reset', [PasswordController::class, 'reset'])->middleware('throttle:10,1');

    // Verificación de correo (público)
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1');

    // Completar login con 2FA (público)
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/verify', [AuthController::class, 'verify']);

        Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1');

        Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->middleware('throttle:5,1');
        Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:10,1');
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
    });

    // service-to-service (shared-secret protected)
    Route::get('/internal/users/{id}', [InternalUserController::class, 'show']);
    Route::get('/internal/users', [InternalUserController::class, 'batch']);
});
