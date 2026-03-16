<?php

namespace App\Domain\Notification\Services;

use App\Domain\Board\Models\Card;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;

class NotificationService
{
    public function notifyCardMembers(Card $card, User $actor, string $type, string $title, ?string $body = null, array $extra = []): void
    {
        $memberIds = $card->members()->pluck('users.id')->toArray();

        // Don't notify the actor themselves
        $recipientIds = array_filter($memberIds, fn ($id) => $id !== $actor->id);

        if (empty($recipientIds)) {
            return;
        }

        $data = array_merge([
            'card_id' => $card->id,
            'card_title' => $card->title,
            'board_id' => $card->board_id,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
        ], $extra);

        foreach ($recipientIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }

    public function notifyUser(string $userId, string $type, string $title, ?string $body = null, array $data = []): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
