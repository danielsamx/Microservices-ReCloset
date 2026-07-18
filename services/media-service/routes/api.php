<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\MediaController;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/metrics', [MetricsController::class, 'metrics']);

// Public retrieval
Route::get('/api/media/{id}/raw', [MediaController::class, 'raw']);

// Internal (Item Service only, shared-secret protected)
Route::post('/api/media', [MediaController::class, 'store']);
Route::get('/api/media/{id}/meta', [MediaController::class, 'meta']);
Route::delete('/api/media/by-item/{itemId}', [MediaController::class, 'destroyByItem']);
Route::delete('/api/media/{id}', [MediaController::class, 'destroy']);
