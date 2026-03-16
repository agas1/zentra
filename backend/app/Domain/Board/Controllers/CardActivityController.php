<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Card;
use Illuminate\Http\JsonResponse;

class CardActivityController
{
    public function index(Card $card): JsonResponse
    {
        $activities = $card->activities()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($activities);
    }
}
