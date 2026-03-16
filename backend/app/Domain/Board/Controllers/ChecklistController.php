<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Models\Checklist;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController
{
    public function __construct(private BoardService $boardService) {}

    public function store(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
        ]);

        $maxPosition = $card->checklists()->max('position') ?? 0;

        $checklist = $card->checklists()->create([
            'title' => $data['title'],
            'position' => $maxPosition + 1000,
        ]);

        $this->boardService->logActivity($card, auth()->user(), 'checklist_added', [
            'title' => $data['title'],
        ]);

        return response()->json(['data' => $checklist->load('items')], 201);
    }

    public function update(Request $request, Checklist $checklist): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:150',
        ]);

        $checklist->update($data);

        return response()->json(['data' => $checklist]);
    }

    public function destroy(Checklist $checklist): JsonResponse
    {
        $checklist->delete();

        return response()->json(null, 204);
    }
}
