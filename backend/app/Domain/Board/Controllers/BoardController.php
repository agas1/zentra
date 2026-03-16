<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController
{
    public function __construct(private BoardService $boardService) {}

    public function index(Request $request): JsonResponse
    {
        $boards = $this->boardService->listForWorkspace($request->workspace);

        return response()->json(['data' => $boards]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'client_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'background_color' => 'nullable|string|max:20',
            'background_image' => 'nullable|image|max:5120',
            'template' => 'nullable|string|in:default,design_pipeline,agency_pipeline,freelancer_simple,client_portal',
            'theme' => 'nullable|string|in:light,dark,system',
        ]);

        if ($request->hasFile('background_image')) {
            $path = $request->file('background_image')->store('board-backgrounds', 'public');
            $data['background_image'] = '/storage/' . $path;
        }

        $workspace = $request->workspace;

        if ($workspace->plan->max_boards > 0 && $workspace->boards()->count() >= $workspace->plan->max_boards) {
            return response()->json(['error' => ['message' => 'Limite de quadros atingido no plano atual.']], 403);
        }

        $board = $this->boardService->createBoard($data, auth()->user(), $workspace);

        return response()->json(['data' => $board->load('lists', 'labels')], 201);
    }

    public function show(Board $board): JsonResponse
    {
        $board->load([
            'lists.cards' => fn($q) => $q->where('is_archived', false)->whereNull('parent_card_id')->orderBy('position'),
            'lists.cards.members:id,name',
            'lists.cards.labels',
            'lists.cards.checklists.items',
            'lists.cards.attachments',
            'lists.cards.customFieldValues.customField',
            'lists.cards.subCards' => fn($q) => $q->where('is_archived', false)->select('id', 'parent_card_id', 'title', 'due_completed'),
            'labels',
            'customFields',
        ]);

        return response()->json(['data' => $board]);
    }

    public function update(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:150',
            'client_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'background_color' => 'sometimes|string|max:20',
            'background_image' => 'nullable|image|max:5120',
            'is_starred' => 'sometimes|boolean',
            'is_archived' => 'sometimes|boolean',
            'theme' => 'sometimes|string|in:light,dark,system',
        ]);

        // Check custom_backgrounds feature for image uploads
        if ($request->hasFile('background_image')) {
            $plan = $board->workspace->plan;
            if (!$plan->hasFeature('custom_backgrounds')) {
                return response()->json(['error' => ['message' => 'Backgrounds customizados nao estao disponiveis no plano atual. Faca upgrade para usar esta funcionalidade.']], 403);
            }
            $path = $request->file('background_image')->store('board-backgrounds', 'public');
            $data['background_image'] = '/storage/' . $path;
        } elseif (isset($data['background_color'])) {
            $data['background_image'] = null;
        }

        $board->update($data);

        return response()->json(['data' => $board->fresh()]);
    }

    public function archived(Board $board): JsonResponse
    {
        $archivedCards = $board->lists()
            ->with(['cards' => fn($q) => $q->where('is_archived', true)->with('list:id,name', 'labels', 'members:id,name', 'unarchiveList:id,name')])
            ->get()
            ->pluck('cards')
            ->flatten()
            ->sortByDesc('updated_at')
            ->values();

        $archivedLists = $board->lists()
            ->where('is_archived', true)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => [
                'cards' => $archivedCards,
                'lists' => $archivedLists,
            ],
        ]);
    }

    public function destroy(Board $board): JsonResponse
    {
        $board->delete();

        return response()->json(null, 204);
    }
}
