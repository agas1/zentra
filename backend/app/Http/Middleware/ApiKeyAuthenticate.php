<?php

namespace App\Http\Middleware;

use App\Domain\Api\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-Api-Key');

        if (!$rawKey) {
            return response()->json([
                'error' => [
                    'code' => 'API_KEY_REQUIRED',
                    'message' => 'API key is required. Pass it via X-Api-Key header.',
                ],
            ], 401);
        }

        $apiKey = ApiKey::findByRawKey($rawKey);

        if (!$apiKey) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'Invalid API key.',
                ],
            ], 401);
        }

        if ($apiKey->isExpired()) {
            return response()->json([
                'error' => [
                    'code' => 'API_KEY_EXPIRED',
                    'message' => 'This API key has expired.',
                ],
            ], 401);
        }

        $workspace = $apiKey->workspace()->with('plan')->first();

        if (!$workspace || !$workspace->is_active) {
            return response()->json([
                'error' => [
                    'code' => 'WORKSPACE_INACTIVE',
                    'message' => 'Workspace is inactive.',
                ],
            ], 403);
        }

        if (!$workspace->plan->hasFeature('api_access')) {
            return response()->json([
                'error' => [
                    'code' => 'API_ACCESS_NOT_AVAILABLE',
                    'message' => 'Acesso à API requer plano Business.',
                ],
            ], 403);
        }

        // Update last_used_at without touching timestamps
        $apiKey->updateQuietly(['last_used_at' => now()]);

        $request->merge(['workspace' => $workspace]);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
