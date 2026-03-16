<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Models\CardComment;
use App\Domain\Board\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardCommentController
{
    public function __construct(private BoardService $boardService) {}

    public function index(Card $card): JsonResponse
    {
        $comments = $card->comments()->with('user:id,name')->paginate(20);

        return response()->json($comments);
    }

    public function store(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = $card->comments()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        $this->boardService->logActivity($card, auth()->user(), 'comment_added');

        app(\App\Domain\Notification\Services\NotificationService::class)->notifyCardMembers(
            $card,
            auth()->user(),
            'comment_added',
            auth()->user()->name . ' comentou em "' . $card->title . '"',
            mb_substr($data['body'], 0, 100),
        );

        // Slack notification
        $this->boardService->dispatchSlackNotification($card->board->workspace, 'comment_added', [
            'user_name' => auth()->user()->name,
            'card_title' => $card->title,
            'board_name' => $card->board->name,
            'comment' => $data['body'],
        ]);

        return response()->json(['data' => $comment->load('user:id,name')], 201);
    }

    public function update(Request $request, CardComment $comment): JsonResponse
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => ['message' => 'Não autorizado.']], 403);
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment->update($data);

        return response()->json(['data' => $comment]);
    }

    public function destroy(CardComment $comment): JsonResponse
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => ['message' => 'Não autorizado.']], 403);
        }

        $comment->delete();

        return response()->json(null, 204);
    }
}
