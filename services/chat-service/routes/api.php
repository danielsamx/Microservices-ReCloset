<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BroadcastAuthController;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/metrics', [MetricsController::class, 'metrics']);

Route::middleware('service.auth')->group(function () {
    // Echo private-channel authorization
    Route::post('/api/broadcasting/auth', [BroadcastAuthController::class, 'authenticate']);

    // Conversations (RF-10, RF-12, RF-13)
    Route::get('/api/conversations', [ConversationController::class, 'index']);
    Route::post('/api/conversations', [ConversationController::class, 'store']);
    Route::get('/api/conversations/{id}', [ConversationController::class, 'show']);
    Route::delete('/api/conversations/{id}', [ConversationController::class, 'destroy']);

    // Messages (RF-11)
    Route::post('/api/conversations/{id}/messages', [MessageController::class, 'store']);

    // Notifications
    Route::get('/api/notifications', [NotificationController::class, 'index']);
    Route::get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/api/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('/api/notifications/read-all', [NotificationController::class, 'markAllRead']);
});
