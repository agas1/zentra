<?php

namespace App\Domain\PowerUp\Controllers;

use App\Domain\PowerUp\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleCalendarController
{
    public function __construct(private GoogleCalendarService $calendarService) {}

    public function auth(): JsonResponse
    {
        $workspace = request()->workspace;
        $url = $this->calendarService->getAuthUrl($workspace);

        return response()->json(['data' => ['url' => $url]]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $code = $request->query('code');
        $workspaceId = $request->query('state');

        if (!$code || !$workspaceId) {
            return redirect($frontendUrl . '/power-ups?error=invalid_code');
        }

        try {
            $workspace = \App\Domain\Workspace\Models\Workspace::findOrFail($workspaceId);
            $this->calendarService->handleCallback($code, $workspace);

            return redirect($frontendUrl . '/power-ups?connected=google_calendar');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Google Calendar callback error', ['error' => $e->getMessage()]);
            return redirect($frontendUrl . '/power-ups?error=callback_failed');
        }
    }

    public function sync(): JsonResponse
    {
        $workspace = request()->workspace;
        $synced = $this->calendarService->syncAllCards($workspace);

        return response()->json([
            'message' => "{$synced} cards sincronizados com o Google Calendar.",
            'data' => ['synced_count' => $synced],
        ]);
    }
}
