<?php

namespace App\Domain\Api\Controllers;

use App\Domain\Api\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController
{
    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::where('workspace_id', $request->workspace->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $workspace = $request->workspace;

        if (!$workspace->plan->hasFeature('api_access')) {
            return response()->json([
                'error' => [
                    'code' => 'API_ACCESS_NOT_AVAILABLE',
                    'message' => 'Acesso à API requer plano Business.',
                ],
            ], 403);
        }

        $result = ApiKey::generate($workspace, $request->name);

        return response()->json([
            'data' => $result['api_key'],
            'raw_key' => $result['raw_key'],
            'message' => 'API key criada. Copie a chave agora — ela não será exibida novamente.',
        ], 201);
    }

    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->workspace_id !== $request->workspace->id) {
            return response()->json([
                'error' => ['message' => 'Resource does not belong to this workspace.'],
            ], 403);
        }

        $apiKey->delete();

        return response()->json(null, 204);
    }
}
