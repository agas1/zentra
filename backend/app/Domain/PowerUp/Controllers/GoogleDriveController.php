<?php

namespace App\Domain\PowerUp\Controllers;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\BoardService;
use App\Domain\PowerUp\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleDriveController
{
    public function __construct(
        private GoogleDriveService $driveService,
        private BoardService $boardService,
    ) {}

    public function auth(): JsonResponse
    {
        $workspace = request()->workspace;
        $url = $this->driveService->getAuthUrl($workspace);

        return response()->json(['data' => ['url' => $url]]);
    }

    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $code = $request->query('code');
        $workspaceId = $request->query('state');

        if (!$code || !$workspaceId) {
            return redirect($frontendUrl . '/power-ups?error=invalid_code');
        }

        try {
            $workspace = \App\Domain\Workspace\Models\Workspace::findOrFail($workspaceId);
            $this->driveService->handleCallback($code, $workspace);

            return redirect($frontendUrl . '/power-ups?connected=google_drive');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Google Drive callback error', ['error' => $e->getMessage()]);
            return redirect($frontendUrl . '/power-ups?error=callback_failed');
        }
    }

    public function files(Request $request): JsonResponse
    {
        $workspace = $request->workspace;
        $query = $request->query('q');

        $files = $this->driveService->searchFiles($workspace, $query);

        return response()->json(['data' => $files]);
    }

    public function attach(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'file_id' => 'required|string',
            'name' => 'required|string',
            'mime_type' => 'nullable|string',
            'size' => 'nullable|integer',
            'web_view_link' => 'required|string',
        ]);

        $driveFile = [
            'id' => $data['file_id'],
            'name' => $data['name'],
            'mimeType' => $data['mime_type'] ?? 'application/octet-stream',
            'size' => $data['size'] ?? 0,
            'webViewLink' => $data['web_view_link'],
        ];

        $attachment = $this->driveService->attachToCard($card, $driveFile, auth()->user());

        $this->boardService->logActivity($card, auth()->user(), 'attachment_added', [
            'filename' => $data['name'],
            'source' => 'google_drive',
        ]);

        return response()->json(['data' => $attachment], 201);
    }
}
