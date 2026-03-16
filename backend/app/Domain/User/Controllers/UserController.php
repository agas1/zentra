<?php

namespace App\Domain\User\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController
{
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'sometimes|string|max:150',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar if it's a local file
            if ($user->avatar_url && str_starts_with($user->avatar_url, '/storage/avatars/')) {
                $oldPath = str_replace('/storage/', '', $user->avatar_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');
            $user->avatar_url = '/storage/' . $path;
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        $user->save();

        return response()->json(['data' => $user]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!$user->password) {
            return response()->json(['error' => ['message' => 'Conta vinculada ao Google. Nao e possivel alterar a senha.']], 400);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => ['message' => 'Senha atual incorreta.']], 422);
        }

        $user->update(['password' => $request->new_password]);

        return response()->json(['message' => 'Senha atualizada com sucesso.']);
    }
}
