<?php

use App\Domain\Chat\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt', 'workspace'])->group(function () {
    Route::get('chat/conversations', [ChatController::class, 'index']);
    Route::post('chat/conversations', [ChatController::class, 'store']);
    Route::get('chat/conversations/{conversation}/messages', [ChatController::class, 'messages']);
    Route::post('chat/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::patch('chat/conversations/{conversation}/read', [ChatController::class, 'markAsRead']);
});
