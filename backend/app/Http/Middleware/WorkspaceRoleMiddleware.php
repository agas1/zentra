<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $currentRole = $request->attributes->get('workspace_role');

        if (!$currentRole || !in_array($currentRole, $roles)) {
            return response()->json([
                'error' => [
                    'code' => 'INSUFFICIENT_ROLE',
                    'message' => 'You do not have the required role to perform this action.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
