<?php

namespace App\Http\Middleware;

use App\Domain\Workspace\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check route parameter first (for nested workspace routes), then header
        $workspaceParam = $request->route('workspace') ?? $request->header('X-Workspace-Id');

        if (!$workspaceParam) {
            return response()->json([
                'error' => [
                    'code' => 'WORKSPACE_REQUIRED',
                    'message' => 'Workspace ID is required (route parameter or X-Workspace-Id header).',
                ],
            ], 400);
        }

        // If SubstituteBindings already resolved to a Model, use it directly
        if ($workspaceParam instanceof Workspace) {
            $workspace = $workspaceParam->loadMissing('plan');
        } else {
            $workspace = Workspace::with('plan')->find($workspaceParam);
        }

        if (!$workspace) {
            return response()->json([
                'error' => [
                    'code' => 'WORKSPACE_NOT_FOUND',
                    'message' => 'Workspace not found.',
                ],
            ], 404);
        }

        if (!$workspace->is_active) {
            return response()->json([
                'error' => [
                    'code' => 'WORKSPACE_INACTIVE',
                    'message' => 'Workspace is inactive.',
                ],
            ], 403);
        }

        $user = $request->user();
        $role = $workspace->memberRole($user);

        if (!$role) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_WORKSPACE_MEMBER',
                    'message' => 'You are not a member of this workspace.',
                ],
            ], 403);
        }

        $request->merge(['workspace' => $workspace]);
        $request->attributes->set('workspace_role', $role);

        return $next($request);
    }
}
