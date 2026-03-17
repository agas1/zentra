<?php

namespace App\Domain\Workspace\Controllers;

use App\Domain\Workspace\Mail\InvitationMail;
use App\Domain\Workspace\Models\Invitation;
use App\Domain\Workspace\Requests\InviteMemberRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvitationController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->workspace;

        $invitations = Invitation::where('workspace_id', $workspace->id)
            ->pending()
            ->with('inviter')
            ->get();

        return response()->json(['data' => $invitations]);
    }

    public function store(InviteMemberRequest $request)
    {
        $workspace = $request->workspace;

        $pendingCount = Invitation::where('workspace_id', $workspace->id)
            ->pending()
            ->count();

        $memberCount = $workspace->members()->count();

        if ($workspace->plan->max_members >= 0 && ($memberCount + $pendingCount) >= $workspace->plan->max_members) {
            return response()->json([
                'error' => [
                    'code' => 'MEMBER_LIMIT_REACHED',
                    'message' => 'Workspace has reached the maximum number of members allowed by the current plan.',
                ],
            ], 422);
        }

        $email = $request->validated()['email'];

        $alreadyMember = $workspace->members()->where('email', $email)->exists();

        if ($alreadyMember) {
            return response()->json([
                'error' => [
                    'code' => 'ALREADY_MEMBER',
                    'message' => 'This email is already a member of the workspace.',
                ],
            ], 422);
        }

        $existingInvite = Invitation::where('workspace_id', $workspace->id)
            ->where('email', $email)
            ->pending()
            ->exists();

        if ($existingInvite) {
            return response()->json([
                'error' => [
                    'code' => 'ALREADY_INVITED',
                    'message' => 'A pending invitation already exists for this email.',
                ],
            ], 422);
        }

        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $request->validated()['role'] ?? 'member',
            'token' => bin2hex(random_bytes(32)),
            'invited_by_id' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);

        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $acceptUrl = $frontendUrl . '/invitation/' . $invitation->token;

        $emailError = null;
        try {
            Mail::to($email)->send(new InvitationMail(
                invitation: $invitation,
                workspaceName: $workspace->name,
                inviterName: auth()->user()->name,
                acceptUrl: $acceptUrl,
            ));
        } catch (\Throwable $e) {
            $emailError = $e->getMessage();
            \Log::warning('Failed to send invitation email: ' . $emailError);
        }

        return response()->json([
            'data' => $invitation,
            'message' => $emailError ? 'Invitation created but email failed: ' . $emailError : 'Invitation sent successfully',
        ], 201);
    }

    public function destroy(Request $request, string $invitationId)
    {
        $invitation = Invitation::where('id', $invitationId)
            ->where('workspace_id', $request->workspace->id)
            ->firstOrFail();

        $invitation->delete();

        return response()->json(['message' => 'Invitation cancelled successfully']);
    }

    public function accept(string $token)
    {
        $invitation = Invitation::where('token', $token)
            ->pending()
            ->first();

        if (! $invitation) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_INVITATION',
                    'message' => 'Invitation is invalid, expired, or already accepted.',
                ],
            ], 404);
        }

        $workspace = $invitation->workspace()->with('plan')->first();

        if (! $workspace->canAddMember()) {
            return response()->json([
                'error' => [
                    'code' => 'MEMBER_LIMIT_REACHED',
                    'message' => 'Workspace has reached the maximum number of members allowed by the current plan.',
                ],
            ], 422);
        }

        $user = auth()->user();

        $alreadyMember = $workspace->members()->where('user_id', $user->id)->exists();

        if ($alreadyMember) {
            $invitation->update(['accepted_at' => now()]);

            return response()->json([
                'data' => $workspace,
                'message' => 'You are already a member of this workspace.',
            ]);
        }

        $workspace->members()->attach($user->id, ['role' => $invitation->role]);

        $invitation->update(['accepted_at' => now()]);

        return response()->json([
            'data' => $workspace->load('plan'),
            'message' => 'Invitation accepted successfully',
        ]);
    }
}
