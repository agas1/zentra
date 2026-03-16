<?php

namespace App\Domain\PowerUp\Services;

use App\Domain\PowerUp\Models\PowerUp;
use App\Domain\PowerUp\Models\WorkspacePowerUp;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;

class PowerUpService
{
    public function listAvailable(): \Illuminate\Database\Eloquent\Collection
    {
        return PowerUp::where('is_active', true)->orderBy('name')->get();
    }

    public function listInstalled(Workspace $workspace): \Illuminate\Database\Eloquent\Collection
    {
        return WorkspacePowerUp::where('workspace_id', $workspace->id)
            ->with(['powerUp', 'connectedBy:id,name'])
            ->get();
    }

    public function install(Workspace $workspace, string $slug, User $user): WorkspacePowerUp
    {
        return WorkspacePowerUp::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'power_up_slug' => $slug,
            ],
            [
                'is_enabled' => true,
                'connected_by_id' => $user->id,
                'connected_at' => now(),
            ]
        );
    }

    public function uninstall(Workspace $workspace, string $slug): void
    {
        WorkspacePowerUp::where('workspace_id', $workspace->id)
            ->where('power_up_slug', $slug)
            ->delete();
    }

    public function updateConfig(Workspace $workspace, string $slug, array $config): WorkspacePowerUp
    {
        $powerUp = WorkspacePowerUp::where('workspace_id', $workspace->id)
            ->where('power_up_slug', $slug)
            ->firstOrFail();

        $merged = array_merge($powerUp->config ?? [], $config);
        $powerUp->update(['config' => $merged]);

        return $powerUp->fresh();
    }

    public function isInstalled(Workspace $workspace, string $slug): bool
    {
        return WorkspacePowerUp::where('workspace_id', $workspace->id)
            ->where('power_up_slug', $slug)
            ->where('is_enabled', true)
            ->exists();
    }

    public function getInstalled(Workspace $workspace, string $slug): ?WorkspacePowerUp
    {
        return WorkspacePowerUp::where('workspace_id', $workspace->id)
            ->where('power_up_slug', $slug)
            ->where('is_enabled', true)
            ->first();
    }
}
