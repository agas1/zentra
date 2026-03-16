<?php

use App\Domain\Sso\Controllers\SamlController;
use App\Domain\Sso\Controllers\SsoConfigController;
use Illuminate\Support\Facades\Route;

// Public SAML endpoints (no auth required)
Route::get('sso/saml/{workspace}/metadata', [SamlController::class, 'metadata']);
Route::get('sso/saml/{workspace}/login', [SamlController::class, 'login']);
Route::post('sso/saml/{workspace}/acs', [SamlController::class, 'acs']);

// SSO discovery (no auth required)
Route::post('sso/discover', [SsoConfigController::class, 'discover']);

// Protected SSO config routes (owner only)
Route::middleware(['jwt', 'workspace', 'workspace.role:owner'])->group(function () {
    Route::get('sso/config', [SsoConfigController::class, 'show']);
    Route::post('sso/config', [SsoConfigController::class, 'store']);
    Route::post('sso/config/test', [SsoConfigController::class, 'testConnection']);
    Route::delete('sso/config', [SsoConfigController::class, 'destroy']);
});
