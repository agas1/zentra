<?php

namespace App\Domain\Workspace\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->workspace;

        $members = $workspace->members()->get();

        return response()->json(['data' => $members]);
    }

    public function destroy(Request $request, string $userId)
    {
        $workspace = $request->workspace;

        $member = $workspace->members()->where('user_id', $userId)->first();

        if (! $member) {
            return response()->json([
                'error' => [
                    'code' => 'MEMBER_NOT_FOUND',
                    'message' => 'User is not a member of this workspace.',
                ],
            ], 404);
        }

        if ($member->pivot->role === 'owner') {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_REMOVE_OWNER',
                    'message' => 'Cannot remove the workspace owner.',
                ],
            ], 403);
        }

        $workspace->members()->detach($userId);

        return response()->json(['message' => 'Member removed successfully']);
    }

    public function updateRole(Request $request, string $userId)
    {
        $workspace = $request->workspace;

        $member = $workspace->members()->where('user_id', $userId)->first();

        if (! $member) {
            return response()->json([
                'error' => [
                    'code' => 'MEMBER_NOT_FOUND',
                    'message' => 'User is not a member of this workspace.',
                ],
            ], 404);
        }

        if ($member->pivot->role === 'owner') {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_CHANGE_OWNER_ROLE',
                    'message' => 'Cannot change the workspace owner role.',
                ],
            ], 403);
        }

        $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        $workspace->members()->updateExistingPivot($userId, [
            'role' => $request->input('role'),
        ]);

        return response()->json(['message' => 'Member role updated successfully']);
    }
}
