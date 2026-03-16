<?php

namespace App\Domain\Chat\Controllers;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController
{
    // GET /chat/conversations - list user's conversations in workspace
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $workspace = $request->workspace;

        $conversations = Conversation::where('workspace_id', $workspace->id)
            ->whereHas('participantRecords', fn($q) => $q->where('user_id', $user->id))
            ->with([
                'participants:id,name,avatar_url',
                'lastMessage.user:id,name',
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('messages.created_at', '>', function ($sub) use ($user) {
                        $sub->select('last_read_at')
                            ->from('conversation_participants')
                            ->whereColumn('conversation_participants.conversation_id', 'messages.conversation_id')
                            ->where('conversation_participants.user_id', $user->id)
                            ->limit(1);
                    })->orWhereRaw('(SELECT last_read_at FROM conversation_participants WHERE conversation_participants.conversation_id = messages.conversation_id AND conversation_participants.user_id = ? LIMIT 1) IS NULL', [$user->id]);
                });
            }])
            ->addSelect(['last_message_at' => Message::select('created_at')
                ->whereColumn('messages.conversation_id', 'conversations.id')
                ->latest()
                ->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json(['data' => $conversations]);
    }

    // POST /chat/conversations - create conversation
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:direct,channel',
            'name' => 'required_if:type,channel|nullable|string|max:150',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'uuid|exists:users,id',
        ]);

        $user = auth()->user();
        $workspace = $request->workspace;

        // For DM, check if conversation already exists between these 2 users
        if ($data['type'] === 'direct' && count($data['participant_ids']) === 1) {
            $otherUserId = $data['participant_ids'][0];
            $existing = Conversation::where('workspace_id', $workspace->id)
                ->where('type', 'direct')
                ->whereHas('participantRecords', fn($q) => $q->where('user_id', $user->id))
                ->whereHas('participantRecords', fn($q) => $q->where('user_id', $otherUserId))
                ->first();

            if ($existing) {
                $existing->load('participants:id,name,avatar_url', 'lastMessage.user:id,name');
                return response()->json(['data' => $existing]);
            }
        }

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'] ?? null,
            'type' => $data['type'],
            'created_by_id' => $user->id,
        ]);

        // Add creator as participant
        $conversation->participantRecords()->create([
            'user_id' => $user->id,
            'last_read_at' => now(),
        ]);

        // Add other participants
        foreach ($data['participant_ids'] as $participantId) {
            if ($participantId !== $user->id) {
                $conversation->participantRecords()->create([
                    'user_id' => $participantId,
                ]);
            }
        }

        // Create system message
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $data['type'] === 'channel'
                ? "criou o canal #{$conversation->name}"
                : 'iniciou a conversa',
            'type' => 'system',
        ]);

        $conversation->load('participants:id,name,avatar_url', 'lastMessage.user:id,name');

        return response()->json(['data' => $conversation], 201);
    }

    // GET /chat/conversations/{conversation}/messages
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        // Verify user is participant
        $isParticipant = $conversation->participantRecords()
            ->where('user_id', auth()->id())->exists();
        if (!$isParticipant) {
            return response()->json(['error' => ['message' => 'Nao autorizado']], 403);
        }

        $messages = $conversation->messages()
            ->with('user:id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    // POST /chat/conversations/{conversation}/messages
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        // Verify user is participant
        $isParticipant = $conversation->participantRecords()
            ->where('user_id', $user->id)->exists();
        if (!$isParticipant) {
            return response()->json(['error' => ['message' => 'Nao autorizado']], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $data['body'],
            'type' => 'text',
        ]);

        // Update sender's last_read_at
        $conversation->participantRecords()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $message->load('user:id,name,avatar_url');

        return response()->json(['data' => $message], 201);
    }

    // PATCH /chat/conversations/{conversation}/read
    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $conversation->participantRecords()
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);

        return response()->json(['message' => 'Marcado como lido']);
    }
}
