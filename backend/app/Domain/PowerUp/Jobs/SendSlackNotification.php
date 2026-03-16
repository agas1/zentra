<?php

namespace App\Domain\PowerUp\Jobs;

use App\Domain\PowerUp\Services\SlackService;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSlackNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 10;

    public function __construct(
        public Workspace $workspace,
        public string $event,
        public array $data,
    ) {}

    public function handle(SlackService $slackService): void
    {
        $slackService->sendNotification($this->workspace, $this->event, $this->data);
    }
}
