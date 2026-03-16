<?php

namespace Database\Seeders;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardList;
use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\BoardService;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DashboardWarmupSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@opera.com')->firstOrFail();
        $workspace = $user->workspaces()->first();

        if (!$workspace) {
            $this->command->error('Workspace not found for admin user.');
            return;
        }

        // Switch to agency persona for full dashboard experience
        $workspace->update(['persona' => 'agency']);
        $this->command->info("Persona atualizada para 'agency'.");

        $boardService = new BoardService();

        // ─── Board 1: Acme Corp (agency_pipeline) ─── mostly in production
        $board1 = $boardService->createBoard([
            'name' => 'Campanha Verão 2026',
            'client_name' => 'Acme Corp',
            'template' => 'agency_pipeline',
            'background_color' => '#0079bf',
        ], $user, $workspace);
        $this->seedBoard($board1, $user, [
            // [list_index, title, due_date_offset_days (null = no due)]
            [0, 'Briefing de redes sociais', null],
            [0, 'Pesquisa de concorrentes', -2],          // overdue
            [1, 'Criação de posts Instagram', 5],
            [1, 'Criação de posts LinkedIn', 7],
            [1, 'Banner campanha principal', 3],
            [2, 'Revisão interna - Stories', -1],          // overdue
            [2, 'Revisão interna - Carrossel', 2],
            [3, 'Vídeo promocional 30s', 10],
            [4, 'Logo adaptação verão', null],
            [4, 'Paleta de cores aprovada', null],
            [5, 'Manual de marca entregue', -5],           // completed (delivered)
            [5, 'Kit redes sociais Jan', -10],             // completed
            [5, 'Email marketing template', -15],          // completed
        ]);
        $this->command->info("Board '{$board1->name}' criado com 13 cards.");

        // ─── Board 2: TechStart (agency_pipeline) ─── new client, early stage
        $board2 = $boardService->createBoard([
            'name' => 'Rebranding TechStart',
            'client_name' => 'TechStart',
            'template' => 'agency_pipeline',
            'background_color' => '#61bd4f',
        ], $user, $workspace);
        $this->seedBoard($board2, $user, [
            [0, 'Questionário de marca', null],
            [0, 'Moodboard referências', 3],
            [0, 'Análise de mercado', 5],
            [1, 'Proposta de logo v1', 10],
            [1, 'Tipografia seleção', 12],
            [2, 'Revisão paleta cores', 7],
            [5, 'Contrato assinado', -20],                 // completed
        ]);
        $this->command->info("Board '{$board2->name}' criado com 7 cards.");

        // ─── Board 3: Bella Vista (agency_pipeline) ─── almost done
        $board3 = $boardService->createBoard([
            'name' => 'Social Media Mensal',
            'client_name' => 'Bella Vista Restaurante',
            'template' => 'agency_pipeline',
            'background_color' => '#ff9f1a',
        ], $user, $workspace);
        $this->seedBoard($board3, $user, [
            [1, 'Posts semana 4', 2],
            [3, 'Posts semana 3 - revisão cliente', 0],    // due today
            [4, 'Posts semana 2 aprovados', -3],
            [4, 'Stories semana 2 aprovados', -3],
            [5, 'Posts semana 1 entregues', -7],           // completed
            [5, 'Stories semana 1 entregues', -7],         // completed
            [5, 'Relatório mensal anterior', -30],         // completed
            [5, 'Grid planejamento entregue', -14],        // completed
        ]);
        $this->command->info("Board '{$board3->name}' criado com 8 cards.");

        // ─── Board 4: Internal (no client) ─── agency internal tasks
        $board4 = $boardService->createBoard([
            'name' => 'Tarefas Internas',
            'client_name' => null,
            'template' => 'agency_pipeline',
            'background_color' => '#c377e0',
        ], $user, $workspace);
        $this->seedBoard($board4, $user, [
            [0, 'Atualizar portfólio site', 15],
            [0, 'Contratar estagiário design', null],
            [1, 'Redesign proposta comercial', 10],
            [1, 'Template de apresentação novo', 8],
            [2, 'Review processo de onboarding', -3],      // overdue
            [5, 'Setup ferramentas 2026', -25],            // completed
            [5, 'Planejamento Q1 concluído', -20],         // completed
        ]);
        $this->command->info("Board '{$board4->name}' criado com 7 cards.");

        // ─── Board 5: Acme Corp second board ─── email marketing
        $board5 = $boardService->createBoard([
            'name' => 'Email Marketing Q1',
            'client_name' => 'Acme Corp',
            'template' => 'agency_pipeline',
            'background_color' => '#eb5a46',
        ], $user, $workspace);
        $this->seedBoard($board5, $user, [
            [0, 'Briefing newsletter março', 5],
            [1, 'Template email promocional', 8],
            [1, 'Copywriting newsletter fev', 3],
            [3, 'Newsletter janeiro - rev cliente', -1],   // overdue
            [4, 'Welcome email aprovado', -5],
            [5, 'Newsletter dezembro entregue', -30],      // completed
            [5, 'Automação Black Friday', -60],            // completed
        ]);
        $this->command->info("Board '{$board5->name}' criado com 7 cards.");

        // Summary
        $totalCards = Card::whereIn('board_id', $workspace->boards()->pluck('id'))->count();
        $this->command->newLine();
        $this->command->info("=== Resumo ===");
        $this->command->info("Workspace: {$workspace->name} (persona: agency)");
        $this->command->info("Boards: {$workspace->boards()->count()}");
        $this->command->info("Total cards: {$totalCards}");
        $this->command->info("Clientes: Acme Corp (2 boards), TechStart (1), Bella Vista (1), Sem cliente (1)");
        $this->command->info("Dashboard pronto para visualização!");
    }

    private function seedBoard(Board $board, User $user, array $cards): void
    {
        $lists = $board->lists()->orderBy('position')->get();

        foreach ($cards as $index => [$listIndex, $title, $dueDaysOffset]) {
            $dueDate = null;
            if ($dueDaysOffset !== null) {
                $dueDate = Carbon::now()->addDays($dueDaysOffset)->toDateString();
            }

            Card::create([
                'list_id' => $lists[$listIndex]->id,
                'board_id' => $board->id,
                'title' => $title,
                'position' => ($index + 1) * 1000,
                'due_date' => $dueDate,
                'is_archived' => false,
                'created_by_id' => $user->id,
            ]);
        }
    }
}
