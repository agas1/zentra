<?php

namespace Database\Seeders;

use App\Domain\Board\Services\BoardService;
use App\Domain\Plan\Models\Plan;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@opera.com'],
            ['name' => 'Admin', 'password' => 'password']
        );

        if ($user->ownedWorkspaces()->count() === 0) {
            $plan = Plan::where('is_default', true)->first();

            $workspace = Workspace::create([
                'name' => 'Meu Workspace',
                'slug' => 'meu-workspace-' . Str::random(6),
                'plan_id' => $plan->id,
                'owner_id' => $user->id,
                'is_active' => true,
            ]);

            $workspace->members()->attach($user->id, [
                'role' => 'owner',
            ]);

            // Create default board with design pipeline template
            $boardService = new BoardService();
            $boardService->createBoard([
                'name' => 'Meu Primeiro Quadro',
                'description' => 'Quadro de gestão de design',
                'background_color' => '#0079bf',
                'template' => 'design_pipeline',
            ], $user, $workspace);
        }
    }
}
