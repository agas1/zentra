<?php

namespace App\Domain\Api\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardList;
use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExternalApiController
{
    public function __construct(private BoardService $boardService) {}

    public function boards(Request $request): JsonResponse
    {
        $boards = $request->workspace->boards()
            ->where('is_archived', false)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'client_name', 'background_color', 'is_starred', 'created_at', 'updated_at']);

        return response()->json(['data' => $boards]);
    }

    public function showBoard(Request $request, Board $board): JsonResponse
    {
        if ($board->workspace_id !== $request->workspace->id) {
            return response()->json(['error' => ['message' => 'Board not found.']], 404);
        }

        $board->load([
            'lists' => fn($q) => $q->where('is_archived', false)->orderBy('position'),
            'lists.cards' => fn($q) => $q->where('is_archived', false)->orderBy('position'),
            'lists.cards.labels',
            'labels',
        ]);

        return response()->json(['data' => $board]);
    }

    public function createCard(Request $request, Board $board, BoardList $list): JsonResponse
    {
        if ($board->workspace_id !== $request->workspace->id) {
            return response()->json(['error' => ['message' => 'Board not found.']], 404);
        }

        if ($list->board_id !== $board->id) {
            return response()->json(['error' => ['message' => 'List does not belong to this board.']], 422);
        }

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        // Use workspace owner as the card creator for API-created cards
        $owner = $request->workspace->owner;
        $card = $this->boardService->createCard($list, $data, $owner);

        return response()->json(['data' => $card], 201);
    }

    public function showCard(Request $request, Card $card): JsonResponse
    {
        if ($card->board->workspace_id !== $request->workspace->id) {
            return response()->json(['error' => ['message' => 'Card not found.']], 404);
        }

        $card->load([
            'list:id,name',
            'labels',
            'members:id,name',
            'checklists.items',
        ]);

        return response()->json(['data' => $card]);
    }

    public function moveCard(Request $request, Card $card): JsonResponse
    {
        if ($card->board->workspace_id !== $request->workspace->id) {
            return response()->json(['error' => ['message' => 'Card not found.']], 404);
        }

        $data = $request->validate([
            'list_id' => [
                'required', 'uuid',
                Rule::exists('board_lists', 'id')->where('board_id', $card->board_id),
            ],
            'position' => 'required|numeric',
        ]);

        $owner = $request->workspace->owner;
        $card = $this->boardService->moveCard($card, $data['list_id'], $data['position'], $owner);

        return response()->json(['data' => $card]);
    }
}
