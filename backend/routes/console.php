<?php

use App\Domain\Client\Services\CreditResetService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    app(CreditResetService::class)->resetDueClients();
})->dailyAt('00:05')->name('credit-reset');

Schedule::command('cards:auto-unarchive')
    ->everyFiveMinutes()
    ->name('cards-auto-unarchive')
    ->withoutOverlapping();
