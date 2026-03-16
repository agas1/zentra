<?php

use App\Domain\Notification\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt', 'workspace'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
});
