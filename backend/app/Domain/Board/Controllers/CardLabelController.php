<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\AutomationService;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardLabelController
{
    public function __construct(private BoardService $boardService) {}

    public function store(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'label_id' => 'required|uuid|exists:labels,id',
        ]);

        $card->labels()->syncWithoutDetaching([$data['label_id']]);

        app(AutomationService::class)->execute('label_added', $card, [
            'label_id' => $data['label_id'],
        ]);

        return response()->json(['data' => $card->labels], 201);
    }

    public function destroy(Card $card, string $labelId): JsonResponse
    {
        $card->labels()->detach($labelId);

        return response()->json(null, 204);
    }
}
