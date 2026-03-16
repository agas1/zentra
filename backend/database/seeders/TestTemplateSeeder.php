<?php

namespace Database\Seeders;

use App\Domain\Board\Models\BoardTemplate;
use App\Domain\Board\Services\BoardService;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Seeder;

class TestTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $boardService = new BoardService();

        // Delete all non-default templates (old test data)
        $deleted = BoardTemplate::where('is_default', false)->delete();
        $this->command->info("Removidos {$deleted} templates de teste antigos.");

        $workspaces = Workspace::all();
        if ($workspaces->isEmpty()) {
            $this->command->error('Nenhum workspace encontrado.');
            return;
        }

        foreach ($workspaces as $workspace) {
            // Skip if workspace already has a default template
            if (BoardTemplate::where('workspace_id', $workspace->id)->where('is_default', true)->exists()) {
                $this->command->info("Workspace '{$workspace->name}' ja tem template default, pulando...");
                continue;
            }

            $userId = $workspace->members()->first()?->id;
            $persona = $workspace->persona ?? 'studio';

            $boardService->createDefaultTemplate($workspace, $persona, $userId);
            $this->command->info("Template default ('{$persona}') criado para workspace '{$workspace->name}'");
        }
    }
}
