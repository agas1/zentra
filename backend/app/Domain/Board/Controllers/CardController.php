<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardList;
use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController
{
    public function __construct(private BoardService $boardService) {}

    public function store(Request $request, Board $board, BoardList $list): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $card = $this->boardService->createCard($list, $data, auth()->user());

        return response()->json(['data' => $card], 201);
    }

    public function show(Card $card): JsonResponse
    {
        $card->load([
            'list:id,name',
            'members:id,name',
            'labels',
            'checklists.items',
            'attachments',
            'comments.user:id,name',
            'activities.user:id,name',
            'createdBy:id,name',
            'customFieldValues.customField',
            'subCards' => fn($q) => $q->where('is_archived', false),
            'subCards.members:id,name',
            'subCards.labels',
        ]);

        return response()->json(['data' => $card]);
    }

    public function update(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_completed' => 'sometimes|boolean',
            'cover_url' => 'nullable|string',
        ]);

        $dueDateChanged = isset($data['due_date']) && $data['due_date'] !== $card->due_date?->toDateString();

        $card->update($data);

        if (isset($data['description'])) {
            $this->boardService->logActivity($card, auth()->user(), 'description_updated');
        }

        if ($dueDateChanged) {
            $workspace = $card->board->workspace;
            $this->boardService->dispatchCalendarSync($card->fresh(), $workspace);
        }

        return response()->json(['data' => $card]);
    }

    public function move(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'list_id' => 'required|uuid|exists:board_lists,id',
            'position' => 'required|numeric',
        ]);

        $card = $this->boardService->moveCard($card, $data['list_id'], $data['position'], auth()->user());

        return response()->json(['data' => $card]);
    }

    public function reorder(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'list_id' => 'required|uuid',
            'positions' => 'required|array',
            'positions.*.id' => 'required|uuid',
            'positions.*.position' => 'required|numeric',
        ]);

        $this->boardService->reorderCards($data['list_id'], $data['positions']);

        return response()->json(['message' => 'Cards reordenados.']);
    }

    public function archive(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'archive_reason' => 'nullable|string|max:1000',
            'unarchive_at' => 'nullable|date|after:now',
            'unarchive_list_id' => 'nullable|uuid|exists:board_lists,id',
        ]);

        $card->update([
            'is_archived' => true,
            'archive_reason' => $data['archive_reason'] ?? null,
            'unarchive_at' => $data['unarchive_at'] ?? null,
            'unarchive_list_id' => $data['unarchive_list_id'] ?? null,
        ]);

        $card->subCards()->update(['is_archived' => true]);

        $this->boardService->logActivity($card, auth()->user(), 'card_archived', [
            'archive_reason' => $data['archive_reason'] ?? null,
            'unarchive_at' => $data['unarchive_at'] ?? null,
            'unarchive_list_name' => isset($data['unarchive_list_id'])
                ? BoardList::find($data['unarchive_list_id'])?->name
                : null,
        ]);

        return response()->json(['data' => $card]);
    }

    public function restore(Card $card): JsonResponse
    {
        $targetListId = $card->unarchive_list_id ?? $card->list_id;

        $card->update([
            'is_archived' => false,
            'list_id' => $targetListId,
            'archive_reason' => null,
            'unarchive_at' => null,
            'unarchive_list_id' => null,
        ]);

        $this->boardService->logActivity($card, auth()->user(), 'card_restored', [
            'list_name' => $card->list?->name,
        ]);

        return response()->json(['data' => $card->load('list:id,name')]);
    }

    public function storeSubCard(Request $request, Card $parentCard): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $maxPosition = $parentCard->subCards()->max('position') ?? 0;

        $subCard = Card::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'board_id' => $parentCard->board_id,
            'list_id' => $parentCard->list_id,
            'parent_card_id' => $parentCard->id,
            'created_by_id' => auth()->id(),
            'position' => $maxPosition + 1,
        ]);

        $this->boardService->logActivity($parentCard, auth()->user(), 'subcard_added', [
            'subcard_title' => $subCard->title,
        ]);

        return response()->json(['data' => $subCard->load('members:id,name', 'labels')], 201);
    }

    public function duplicate(Card $card): JsonResponse
    {
        $newCard = $this->boardService->duplicateCard($card, auth()->user());

        return response()->json(['data' => $newCard], 201);
    }

    public function destroy(Card $card): JsonResponse
    {
        $card->delete();

        return response()->json(null, 204);
    }
}
