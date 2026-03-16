<?php

namespace App\Domain\PowerUp\Services;

use App\Domain\PowerUp\Models\WorkspacePowerUp;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    public function sendNotification(Workspace $workspace, string $event, array $data): void
    {
        $powerUp = (new PowerUpService())->getInstalled($workspace, 'slack');
        if (!$powerUp) {
            return;
        }

        $config = $powerUp->config ?? [];
        $webhookUrl = $config['webhook_url'] ?? null;
        $notifyOn = $config['notify_on'] ?? ['card_created', 'card_moved', 'comment_added', 'due_date_approaching'];

        if (!$webhookUrl || !in_array($event, $notifyOn)) {
            return;
        }

        $message = $this->formatMessage($event, $data);
        if (!$message) {
            return;
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'text' => $message,
                'unfurl_links' => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Slack notification failed', [
                'workspace_id' => $workspace->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendTestMessage(string $webhookUrl): bool
    {
        try {
            $response = Http::timeout(5)->post($webhookUrl, [
                'text' => '🎉 Orbita conectado com sucesso! Voce recebera notificacoes aqui.',
            ]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function formatMessage(string $event, array $data): ?string
    {
        $user = $data['user_name'] ?? 'Alguem';
        $card = $data['card_title'] ?? 'um card';
        $board = $data['board_name'] ?? '';

        return match ($event) {
            'card_created' => "📋 *{$user}* criou o card *{$card}* na lista *{$data['list_name']}*" . ($board ? " ({$board})" : ''),
            'card_moved' => "➡️ *{$user}* moveu *{$card}* de *{$data['from_list']}* para *{$data['to_list']}*" . ($board ? " ({$board})" : ''),
            'comment_added' => "💬 *{$user}* comentou no card *{$card}*" . ($board ? " ({$board})" : '') . (isset($data['comment']) ? "\n> " . mb_substr($data['comment'], 0, 200) : ''),
            'due_date_approaching' => "⏰ O card *{$card}* vence " . ($data['due_label'] ?? 'em breve') . ($board ? " ({$board})" : ''),
            default => null,
        };
    }
}
