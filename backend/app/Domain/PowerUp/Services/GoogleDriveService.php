<?php

namespace App\Domain\PowerUp\Services;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Models\CardAttachment;
use App\Domain\PowerUp\Models\WorkspacePowerUp;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    public function getAuthUrl(Workspace $workspace): string
    {
        $params = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.drive_redirect'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.readonly',
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
            'redirect_uri' => config('services.google.drive_redirect'),
        ]);

        $tokens = $response->json();

        $powerUpService = new PowerUpService();
        $wpu = $powerUpService->getInstalled($workspace, 'google_drive');

        if (!$wpu) {
            $user = auth()->user() ?? $workspace->owner;
            $wpu = $powerUpService->install($workspace, 'google_drive', $user);
        }

        $email = $this->getUserEmail($tokens['access_token']);

        $wpu->update([
            'config' => array_merge($wpu->config ?? [], [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? $wpu->getConfigValue('refresh_token'),
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600)->toISOString(),
                'email' => $email,
            ]),
        ]);

        return $wpu->fresh();
    }

    public function searchFiles(Workspace $workspace, ?string $query = null): array
    {
        $wpu = (new PowerUpService())->getInstalled($workspace, 'google_drive');
        if (!$wpu) {
            return [];
        }

        $accessToken = $this->getValidToken($wpu);
        if (!$accessToken) {
            return [];
        }

        $q = "trashed = false";
        if ($query) {
            $escaped = str_replace("'", "\\'", $query);
            $q .= " and name contains '{$escaped}'";
        }

        try {
            $response = Http::withToken($accessToken)->get('https://www.googleapis.com/drive/v3/files', [
                'q' => $q,
                'fields' => 'files(id,name,mimeType,size,webViewLink,iconLink,thumbnailLink,modifiedTime)',
                'pageSize' => 30,
                'orderBy' => 'modifiedTime desc',
            ]);

            if ($response->successful()) {
                return $response->json('files', []);
            }
        } catch (\Throwable $e) {
            Log::warning('Google Drive search failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    public function attachToCard(Card $card, array $driveFile, User $user): CardAttachment
    {
        return CardAttachment::create([
            'card_id' => $card->id,
            'filename' => $driveFile['name'],
            'path' => '',
            'mime_type' => $driveFile['mimeType'] ?? 'application/octet-stream',
            'size' => (int) ($driveFile['size'] ?? 0),
            'uploaded_by_id' => $user->id,
            'source' => 'google_drive',
            'external_url' => $driveFile['webViewLink'] ?? '',
            'external_id' => $driveFile['id'],
        ]);
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
            Log::warning('Google Drive token refresh failed', ['error' => $e->getMessage()]);
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
