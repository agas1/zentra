<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController
{
    public function index(Board $board): JsonResponse
    {
        return response()->json(['data' => $board->labels]);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
            'color' => 'required|string|max:20',
        ]);

        $plan = $board->workspace->plan;
        if (!$plan->isUnlimited('max_labels') && $board->labels()->count() >= $plan->max_labels) {
            return response()->json(['error' => ['message' => 'Limite de etiquetas atingido no plano atual. Faca upgrade para criar mais.']], 403);
        }

        $label = $board->labels()->create($data);

        return response()->json(['data' => $label], 201);
    }

    public function update(Request $request, Board $board, Label $label): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
            'color' => 'sometimes|string|max:20',
        ]);

        $label->update($data);

        return response()->json(['data' => $label]);
    }

    public function destroy(Board $board, Label $label): JsonResponse
    {
        $label->delete();

        return response()->json(null, 204);
    }
}
