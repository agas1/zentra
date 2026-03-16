<?php

use App\Domain\Plan\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function () {
    Route::get('plans', [PlanController::class, 'index']);
});

Route::middleware(['jwt', 'workspace'])->group(function () {
    Route::get('plans/usage', [PlanController::class, 'usage']);
});
