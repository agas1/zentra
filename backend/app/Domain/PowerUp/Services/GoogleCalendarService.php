<?php

namespace App\Domain\PowerUp\Services;

use App\Domain\Board\Models\Card;
use App\Domain\PowerUp\Models\WorkspacePowerUp;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    public function getAuthUrl(Workspace $workspace): string
    {
        $params = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.calendar_redirect'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $workspace->id,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public function handleCallback(string $code, Workspace $workspace): WorkspacePowerUp
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.google.calendar_redirect'),
        ]);

        $tokens = $response->json();

        $powerUpService = new PowerUpService();
        $wpu = $powerUpService->getInstalled($workspace, 'google_calendar');

        if (!$wpu) {
            // Fallback: install using workspace owner if no authenticated user (OAuth callback)
            $user = auth()->user() ?? $workspace->owner;
            $wpu = $powerUpService->install($workspace, 'google_calendar', $user);
        }

        $userInfo = $this->getUserEmail($tokens['access_token']);

        $wpu->update([
            'config' => array_merge($wpu->config ?? [], [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? $wpu->getConfigValue('refresh_token'),
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600)->toISOString(),
                'email' => $userInfo,
                'auto_sync' => $wpu->getConfigValue('auto_sync', true),
            ]),
        ]);

        return $wpu->fresh();
    }

    public function syncCardToCalendar(Card $card, Workspace $workspace): ?string
    {
        if (!$card->due_date) {
            return null;
        }

        $wpu = (new PowerUpService())->getInstalled($workspace, 'google_calendar');
        if (!$wpu || !$wpu->getConfigValue('auto_sync', true)) {
            return null;
        }

        $accessToken = $this->getValidToken($wpu);
        if (!$accessToken) {
            return null;
        }

        $boardName = $card->board?->name ?? 'Orbita';
        $listName = $card->list?->name ?? '';

        $eventData = [
            'summary' => $card->title,
            'description' => ($card->description ?? '') . "\n\nBoard: {$boardName}" . ($listName ? "\nLista: {$listName}" : ''),
            'start' => [
                'date' => $card->due_date->format('Y-m-d'),
            ],
            'end' => [
                'date' => $card->due_date->format('Y-m-d'),
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => 60],
                ],
            ],
        ];

        try {
            if ($card->google_calendar_event_id) {
                $response = Http::withToken($accessToken)
                    ->put("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$card->google_calendar_event_id}", $eventData);
            } else {
                $response = Http::withToken($accessToken)
                    ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $eventData);
            }

            if ($response->successful()) {
                $eventId = $response->json('id');
                $card->update(['google_calendar_event_id' => $eventId]);
                return $eventId;
            }

            Log::warning('Google Calendar sync failed', [
                'card_id' => $card->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar sync error', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function syncAllCards(Workspace $workspace): int
    {
        $wpu = (new PowerUpService())->getInstalled($workspace, 'google_calendar');
        if (!$wpu) {
            return 0;
        }

        $cards = Card::whereHas('board', fn($q) => $q->where('workspace_id', $workspace->id))
            ->whereNotNull('due_date')
            ->where('is_archived', false)
            ->get();

        $synced = 0;
        foreach ($cards as $card) {
            if ($this->syncCardToCalendar($card, $workspace)) {
                $synced++;
            }
        }

        return $synced;
    }

    private function getValidToken(WorkspacePowerUp $wpu): ?string
    {
        $expiresAt = $wpu->getConfigValue('token_expires_at');
        $accessToken = $wpu->getConfigValue('access_token');
        $refreshToken = $wpu->getConfigValue('refresh_token');

        if (!$accessToken || !$refreshToken) {
            return null;
        }

        if ($expiresAt && now()->lt($expiresAt)) {
            return $accessToken;
        }

        // Refresh the token
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $tokens = $response->json();
                $wpu->update([
                    'config' => array_merge($wpu->config ?? [], [
                        'access_token' => $tokens['access_token'],
                        'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600)->toISOString(),
                    ]),
                ]);
                return $tokens['access_token'];
            }
        } catch (\Throwable $e) {
            Log::warning('Google token refresh failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function getUserEmail(string $accessToken): ?string
    {
        try {
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            return $response->json('email');
        } catch (\Throwable) {
            return null;
        }
    }
}
