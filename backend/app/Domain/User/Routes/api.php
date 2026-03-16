<?php

use App\Domain\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt'])->group(function () {
    Route::post('user/profile', [UserController::class, 'updateProfile']);
    Route::put('user/password', [UserController::class, 'updatePassword']);
});
