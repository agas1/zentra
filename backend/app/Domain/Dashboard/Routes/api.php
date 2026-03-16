<?php

use App\Domain\Dashboard\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt', 'workspace'])->group(function () {
    Route::get('dashboard/metrics', [DashboardController::class, 'metrics']);
});
