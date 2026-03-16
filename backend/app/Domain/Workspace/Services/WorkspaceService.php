<?php

namespace App\Domain\Workspace\Services;

use App\Domain\Board\Services\BoardService;
use App\Domain\Plan\Models\Plan;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Support\Str;

class WorkspaceService
{
    public function create(array $data, Plan $plan, User $owner): Workspace
    {
        $slug = $this->generateUniqueSlug($data['name']);

        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => $slug,
            'plan_id' => $plan->id,
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);

        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        return $workspace->load('plan');
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        if (isset($data['name']) && $data['name'] !== $workspace->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $workspace->id);
        }

        $oldPersona = $workspace->persona;

        $workspace->update($data);

        // If persona changed, recreate the default template
        if (isset($data['persona']) && $data['persona'] !== $oldPersona) {
            $boardService = new BoardService();
            $userId = auth()->id();
            $boardService->createDefaultTemplate($workspace, $data['persona'], $userId);
        }

        return $workspace->fresh()->load('plan');
    }

    private function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $slug = Str::slug($name);

        $query = Workspace::withTrashed()->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            $slug .= '-' . Str::lower(Str::random(6));
        }

        return $slug;
    }
}
