<?php

namespace App\Domain\PowerUp\Controllers;

use App\Domain\PowerUp\Services\SlackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlackController
{
    public function __construct(private SlackService $slackService) {}

    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'webhook_url' => 'required|url',
        ]);

        $success = $this->slackService->sendTestMessage($data['webhook_url']);

        if ($success) {
            return response()->json(['message' => 'Mensagem de teste enviada com sucesso!']);
        }

        return response()->json(['error' => ['message' => 'Falha ao enviar mensagem. Verifique a Webhook URL.']], 422);
    }
}
