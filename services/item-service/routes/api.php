<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ItemController;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/metrics', [MetricsController::class, 'metrics']);

// Public metadata + catalog (RF-06, RF-07)
Route::get('/api/meta', [MetaController::class, 'index']);
Route::get('/api/catalog', [CatalogController::class, 'index']);
Route::get('/api/catalog/{id}', [CatalogController::class, 'show']);

// Owner-scoped item management (RF-03/04/08/09)
Route::middleware('service.auth')->group(function () {
    Route::get('/api/items/mine', [ItemController::class, 'mine']);
    Route::get('/api/wardrobe/summary', [ItemController::class, 'wardrobe']);
    Route::post('/api/items', [ItemController::class, 'store']);
    Route::patch('/api/items/{id}', [ItemController::class, 'update']);
    Route::patch('/api/items/{id}/status', [ItemController::class, 'changeStatus']);
    Route::delete('/api/items/{id}', [ItemController::class, 'destroy']);
});

// Service-to-service
Route::get('/api/items/internal/{id}', [ItemController::class, 'internalShow']);
