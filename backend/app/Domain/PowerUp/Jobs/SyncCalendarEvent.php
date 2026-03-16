<?php

namespace App\Domain\PowerUp\Jobs;

use App\Domain\Board\Models\Card;
use App\Domain\PowerUp\Services\GoogleCalendarService;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCalendarEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 15;

    public function __construct(
        public Card $card,
        public Workspace $workspace,
    ) {}

    public function handle(GoogleCalendarService $calendarService): void
    {
        $calendarService->syncCardToCalendar($this->card, $this->workspace);
    }
}
