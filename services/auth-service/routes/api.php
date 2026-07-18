<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InternalUserController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/metrics', [MetricsController::class, 'metrics']);

Route::prefix('api/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/verify', [AuthController::class, 'verify']);
    });

    // service-to-service (shared-secret protected)
    Route::get('/internal/users/{id}', [InternalUserController::class, 'show']);
    Route::get('/internal/users', [InternalUserController::class, 'batch']);
});
