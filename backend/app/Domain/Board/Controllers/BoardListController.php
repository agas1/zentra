<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardList;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardListController
{
    public function __construct(private BoardService $boardService) {}

    public function store(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $list = $this->boardService->createList($board, $data);

        return response()->json(['data' => $list], 201);
    }

    public function update(Request $request, Board $board, BoardList $list): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:150',
            'is_archived' => 'sometimes|boolean',
        ]);

        $list->update($data);

        return response()->json(['data' => $list]);
    }

    public function reorder(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|uuid',
            'positions.*.position' => 'required|numeric',
        ]);

        $this->boardService->reorderLists($board, $data['positions']);

        return response()->json(['message' => 'Listas reordenadas.']);
    }

    public function destroy(Board $board, BoardList $list): JsonResponse
    {
        $list->update(['is_archived' => true]);

        return response()->json(null, 204);
    }
}
