<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Models\CardAttachment;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardAttachmentController
{
    public function __construct(private BoardService $boardService) {}

    public function store(Request $request, Card $card): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $workspace = $card->board->workspace;
        $plan = $workspace->plan;

        // Check attachment size limit
        $fileSizeMb = $file->getSize() / (1024 * 1024);
        if ($fileSizeMb > $plan->max_attachment_size_mb) {
            return response()->json(['error' => ['message' => "Arquivo excede o limite de {$plan->max_attachment_size_mb} MB do plano atual."]], 403);
        }

        // Check total workspace storage limit
        $storageUsedBytes = $workspace->boards()
            ->join('cards', 'boards.id', '=', 'cards.board_id')
            ->join('card_attachments', 'cards.id', '=', 'card_attachments.card_id')
            ->sum('card_attachments.size');
        $storageUsedMb = $storageUsedBytes / (1024 * 1024);

        if (($storageUsedMb + $fileSizeMb) > $plan->max_storage_mb) {
            return response()->json(['error' => ['message' => 'Limite de armazenamento atingido no plano atual. Faca upgrade para mais espaco.']], 403);
        }

        $attachment = $this->boardService->uploadAttachment($card, $file, auth()->user());

        return response()->json(['data' => $attachment], 201);
    }

    public function setCover(CardAttachment $attachment): JsonResponse
    {
        $this->boardService->setAttachmentAsCover($attachment);

        return response()->json(['data' => $attachment->fresh()]);
    }

    public function destroy(CardAttachment $attachment): JsonResponse
    {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->path);

        if ($attachment->is_cover) {
            $attachment->card->update(['cover_url' => null]);
        }

        $attachment->delete();

        return response()->json(null, 204);
    }
}
