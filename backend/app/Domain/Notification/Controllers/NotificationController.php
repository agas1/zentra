<?php

namespace App\Domain\Notification\Controllers;

use App\Domain\Notification\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController
{
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unreadCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => ['message' => 'Não autorizado.']], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['data' => $notification]);
    }

    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Todas as notificações marcadas como lidas.']);
    }
}
