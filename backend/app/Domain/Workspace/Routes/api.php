<?php

use App\Domain\Workspace\Controllers\InvitationController;
use App\Domain\Workspace\Controllers\MemberController;
use App\Domain\Workspace\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Routes that DON'T need workspace middleware (user-level)
Route::middleware('jwt')->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::post('workspaces', [WorkspaceController::class, 'store']);
});

// Routes that NEED workspace middleware (workspace-level)
Route::middleware(['jwt', 'workspace'])->prefix('workspaces/{workspace}')->group(function () {
    Route::get('/', [WorkspaceController::class, 'show']);
    Route::put('/', [WorkspaceController::class, 'update'])->middleware('workspace.role:owner,admin');
    Route::delete('/', [WorkspaceController::class, 'destroy'])->middleware('workspace.role:owner');

    Route::get('/members', [MemberController::class, 'index']);
    Route::delete('/members/{userId}', [MemberController::class, 'destroy'])->middleware('workspace.role:owner,admin');
    Route::patch('/members/{userId}/role', [MemberController::class, 'updateRole'])->middleware('workspace.role:owner');

    Route::get('/invitations', [InvitationController::class, 'index'])->middleware('workspace.role:owner,admin');
    Route::post('/invitations', [InvitationController::class, 'store'])->middleware('workspace.role:owner,admin');
    Route::delete('/invitations/{invitationId}', [InvitationController::class, 'destroy'])->middleware('workspace.role:owner,admin');

    Route::post('/upgrade', [WorkspaceController::class, 'upgrade'])->middleware('workspace.role:owner');
});

// Accept invitation (needs JWT but no workspace middleware)
Route::middleware('jwt')->group(function () {
    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept']);
});
