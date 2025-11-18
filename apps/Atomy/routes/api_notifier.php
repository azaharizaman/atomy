<?php

declare(strict_types=1);

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifier API Routes
|--------------------------------------------------------------------------
|
| API endpoints for notification management.
|
*/

Route::prefix('notifications')->group(function () {
    // Send notifications
    Route::post('send', [NotificationController::class, 'send']);
    Route::post('send-batch', [NotificationController::class, 'sendBatch']);
    Route::post('schedule', [NotificationController::class, 'schedule']);
    
    // Notification management
    Route::delete('{notificationId}', [NotificationController::class, 'cancel']);
    Route::get('{notificationId}/status', [NotificationController::class, 'status']);
});

// User notification history
Route::prefix('users/{userId}/notifications')->group(function () {
    Route::get('history', [HistoryController::class, 'index']);
    Route::get('history/{historyId}', [HistoryController::class, 'show']);
});

// Notification preferences
Route::prefix('users/{userId}/preferences')->group(function () {
    Route::get('/', [PreferenceController::class, 'index']);
    Route::post('/', [PreferenceController::class, 'store']);
    Route::delete('{preferenceId}', [PreferenceController::class, 'destroy']);
});

// Notification templates
Route::prefix('templates')->group(function () {
    Route::get('/', [TemplateController::class, 'index']);
    Route::post('/', [TemplateController::class, 'store']);
    Route::put('{templateId}', [TemplateController::class, 'update']);
    Route::delete('{templateId}', [TemplateController::class, 'destroy']);
    Route::post('preview', [TemplateController::class, 'preview']);
});

// Webhooks (no authentication)
Route::prefix('webhooks')->withoutMiddleware(['auth:sanctum'])->group(function () {
    Route::post('sendgrid', [WebhookController::class, 'sendgrid']);
    Route::post('twilio', [WebhookController::class, 'twilio']);
    Route::post('fcm', [WebhookController::class, 'fcm']);
});
