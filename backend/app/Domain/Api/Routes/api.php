<?php

use App\Domain\Api\Controllers\ApiKeyController;
use App\Domain\Api\Controllers\ExternalApiController;
use Illuminate\Support\Facades\Route;

// API Key management (authenticated via JWT, workspace admin+)
Route::middleware(['jwt', 'workspace', 'workspace.role:owner,admin'])->group(function () {
    Route::get('api-keys', [ApiKeyController::class, 'index']);
    Route::post('api-keys', [ApiKeyController::class, 'store']);
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);
});

// External API (authenticated via API Key, Business plan only)
Route::prefix('external')->middleware(['api_key', 'throttle:api-external'])->group(function () {
    Route::get('boards', [ExternalApiController::class, 'boards']);
    Route::get('boards/{board}', [ExternalApiController::class, 'showBoard']);
    Route::post('boards/{board}/lists/{list}/cards', [ExternalApiController::class, 'createCard']);
    Route::get('cards/{card}', [ExternalApiController::class, 'showCard']);
    Route::patch('cards/{card}/move', [ExternalApiController::class, 'moveCard']);
});
