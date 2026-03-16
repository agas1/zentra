<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\AutomationService;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardMemberController
{
    public function __construct(private BoardService $boardService) {}

    public function store(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        $card->members()->syncWithoutDetaching([$data['user_id']]);
        $this->boardService->logActivity($card, auth()->user(), 'member_added', [
            'user_id' => $data['user_id'],
        ]);

        app(AutomationService::class)->execute('member_assigned', $card, [
            'user_id' => $data['user_id'],
        ]);

        if ($data['user_id'] !== auth()->id()) {
            app(\App\Domain\Notification\Services\NotificationService::class)->notifyUser(
                $data['user_id'],
                'member_assigned',
                auth()->user()->name . ' te atribuiu ao card "' . $card->title . '"',
                null,
                ['card_id' => $card->id, 'board_id' => $card->board_id]
            );
        }

        return response()->json(['data' => $card->members], 201);
    }

    public function destroy(Card $card, string $userId): JsonResponse
    {
        $card->members()->detach($userId);
        $this->boardService->logActivity($card, auth()->user(), 'member_removed', [
            'user_id' => $userId,
        ]);

        return response()->json(null, 204);
    }
}
