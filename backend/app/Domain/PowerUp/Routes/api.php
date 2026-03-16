<?php

use App\Domain\PowerUp\Controllers\PowerUpController;
use App\Domain\PowerUp\Controllers\SlackController;
use App\Domain\PowerUp\Controllers\GoogleCalendarController;
use App\Domain\PowerUp\Controllers\GoogleDriveController;
use Illuminate\Support\Facades\Route;

// OAuth callbacks - no auth required (Google redirects browser here)
Route::get('power-ups/google-calendar/callback', [GoogleCalendarController::class, 'callback']);
Route::get('power-ups/google-drive/callback', [GoogleDriveController::class, 'callback']);

Route::middleware(['jwt', 'workspace'])->group(function () {
    // Power-Ups management
    Route::get('power-ups', [PowerUpController::class, 'index']);
    Route::get('power-ups/installed', [PowerUpController::class, 'installed']);
    Route::post('power-ups/{slug}/install', [PowerUpController::class, 'install'])
        ->middleware('workspace.role:owner,admin');
    Route::delete('power-ups/{slug}/uninstall', [PowerUpController::class, 'uninstall'])
        ->middleware('workspace.role:owner,admin');
    Route::put('power-ups/{slug}/config', [PowerUpController::class, 'updateConfig'])
        ->middleware('workspace.role:owner,admin');

    // Slack
    Route::post('power-ups/slack/test', [SlackController::class, 'test'])
        ->middleware('workspace.role:owner,admin');

    // Google Calendar
    Route::get('power-ups/google-calendar/auth', [GoogleCalendarController::class, 'auth'])
        ->middleware('workspace.role:owner,admin');
    Route::post('power-ups/google-calendar/sync', [GoogleCalendarController::class, 'sync'])
        ->middleware('workspace.role:owner,admin');

    // Google Drive
    Route::get('power-ups/google-drive/auth', [GoogleDriveController::class, 'auth'])
        ->middleware('workspace.role:owner,admin');
    Route::get('power-ups/google-drive/files', [GoogleDriveController::class, 'files']);
    Route::post('cards/{card}/attachments/drive', [GoogleDriveController::class, 'attach']);
});
