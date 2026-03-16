<?php

namespace App\Domain\Board\Services;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardList;
use App\Domain\Board\Models\BoardTemplate;
use App\Domain\Board\Models\Card;
use App\Domain\Board\Models\CardActivity;
use App\Domain\Board\Models\CardAttachment;
use App\Domain\PowerUp\Jobs\SendSlackNotification;
use App\Domain\PowerUp\Jobs\SyncCalendarEvent;
use App\Domain\PowerUp\Services\PowerUpService;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BoardService
{
    public function listForWorkspace(Workspace $workspace)
    {
        return $workspace->boards()
            ->where('is_archived', false)
            ->orderByDesc('is_starred')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function createBoard(array $data, User $user, Workspace $workspace): Board
    {
        $board = Board::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'client_name' => $data['client_name'] ?? null,
            'description' => $data['description'] ?? null,
            'background_color' => $data['background_color'] ?? '#0079bf',
            'created_by_id' => $user->id,
        ]);

        $template = $data['template'] ?? 'default';
        $this->applyTemplate($board, $template);
        $this->createLabelsForTemplate($board, $template);

        return $board;
    }

    public function getTemplateForPersona(string $persona): string
    {
        return match ($persona) {
            'agency' => 'agency_pipeline',
            'studio' => 'design_pipeline',
            'freelancer' => 'freelancer_simple',
            'client' => 'client_portal',
            default => 'design_pipeline',
        };
    }

    public function getDefaultBoardName(string $persona): string
    {
        return match ($persona) {
            'agency' => 'Pipeline de Produção',
            'studio' => 'Meu Primeiro Quadro',
            'freelancer' => 'Meus Projetos',
            'client' => 'Meus Pedidos',
            default => 'Meu Primeiro Quadro',
        };
    }

    public function createDefaultTemplate(Workspace $workspace, string $persona, ?string $userId = null): BoardTemplate
    {
        // Delete any existing default template for this workspace
        BoardTemplate::where('workspace_id', $workspace->id)
            ->where('is_default', true)
            ->delete();

        $data = $this->getDefaultTemplateData($persona);

        return BoardTemplate::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'description' => $data['description'],
            'lists' => $data['lists'],
            'labels' => $data['labels'],
            'custom_fields' => $data['custom_fields'],
            'automations' => $data['automations'],
            'is_default' => true,
            'created_by_id' => $userId,
        ]);
    }

    public function getDefaultTemplateData(string $persona): array
    {
        return match ($persona) {
            'agency' => [
                'name' => 'Producao de Campanha',
                'description' => 'Gerencie campanhas do briefing a entrega com controle de equipe, prazos e aprovacoes do cliente.',
                'lists' => [
                    ['name' => 'Briefing', 'position' => 1000],
                    ['name' => 'Pauta', 'position' => 2000],
                    ['name' => 'Producao', 'position' => 3000],
                    ['name' => 'Revisao Interna', 'position' => 4000],
                    ['name' => 'Aprovacao Cliente', 'position' => 5000],
                    ['name' => 'Ajustes', 'position' => 6000],
                    ['name' => 'Publicado', 'position' => 7000],
                ],
                'labels' => [
                    ['name' => 'Urgente', 'color' => '#ef4444'],
                    ['name' => 'Social Media', 'color' => '#ec4899'],
                    ['name' => 'Blog', 'color' => '#22c55e'],
                    ['name' => 'Email Mkt', 'color' => '#f97316'],
                    ['name' => 'Ads', 'color' => '#3b82f6'],
                    ['name' => 'Video', 'color' => '#8b5cf6'],
                ],
                'custom_fields' => [
                    ['name' => 'Cliente', 'type' => 'text', 'options' => null, 'position' => 1],
                    ['name' => 'Prazo de Entrega', 'type' => 'date', 'options' => null, 'position' => 2],
                    ['name' => 'Tipo de Conteudo', 'type' => 'dropdown', 'options' => ['Post', 'Stories', 'Reels', 'Artigo', 'Email', 'Banner'], 'position' => 3],
                    ['name' => 'Plataforma', 'type' => 'dropdown', 'options' => ['Instagram', 'Facebook', 'LinkedIn', 'Google Ads', 'TikTok', 'Blog'], 'position' => 4],
                ],
                'automations' => [
                    [
                        'name' => 'Aprovacao Cliente → Marcar prazo concluido',
                        'trigger_type' => 'card_moved_to_list',
                        'trigger_config' => ['list_name' => 'Aprovacao Cliente'],
                        'action_type' => 'mark_due_complete',
                        'action_config' => [],
                        'is_active' => true,
                        'position' => 1000,
                    ],
                    [
                        'name' => 'Revisao Interna → Comentar',
                        'trigger_type' => 'card_moved_to_list',
                        'trigger_config' => ['list_name' => 'Revisao Interna'],
                        'action_type' => 'add_comment',
                        'action_config' => ['text' => 'Pronto para revisao da equipe.'],
                        'is_active' => true,
                        'position' => 2000,
                    ],
                ],
            ],
            'studio' => [
                'name' => 'Projeto de Design',
                'description' => 'Fluxo completo para projetos criativos, do briefing a entrega final com controle de versoes e aprovacoes.',
                'lists' => [
                    ['name' => 'Briefing', 'position' => 1000],
                    ['name' => 'Pesquisa & Referencia', 'position' => 2000],
                    ['name' => 'Em Criacao', 'position' => 3000],
                    ['name' => 'Revisao', 'position' => 4000],
                    ['name' => 'Aprovado', 'position' => 5000],
                    ['name' => 'Entregue', 'position' => 6000],
                ],
                'labels' => [
                    ['name' => 'Logo', 'color' => '#6366f1'],
                    ['name' => 'Identidade Visual', 'color' => '#ec4899'],
                    ['name' => 'Social Media', 'color' => '#f97316'],
                    ['name' => 'Web Design', 'color' => '#3b82f6'],
                    ['name' => 'Embalagem', 'color' => '#22c55e'],
                    ['name' => 'Ilustracao', 'color' => '#8b5cf6'],
                ],
                'custom_fields' => [
                    ['name' => 'Formato Final', 'type' => 'dropdown', 'options' => ['AI', 'PSD', 'Figma', 'PDF', 'PNG', 'SVG'], 'position' => 1],
                    ['name' => 'Prioridade', 'type' => 'dropdown', 'options' => ['Alta', 'Media', 'Baixa'], 'position' => 2],
                    ['name' => 'Numero de Revisoes', 'type' => 'number', 'options' => null, 'position' => 3],
                    ['name' => 'Data de Entrega', 'type' => 'date', 'options' => null, 'position' => 4],
                ],
                'automations' => [
                    [
                        'name' => 'Aprovado → Checklist de entrega',
                        'trigger_type' => 'card_moved_to_list',
                        'trigger_config' => ['list_name' => 'Aprovado'],
                        'action_type' => 'add_checklist',
                        'action_config' => ['title' => 'Arquivos de Entrega'],
                        'is_active' => true,
                        'position' => 1000,
                    ],
                    [
                        'name' => 'Revisao → Comentar',
                        'trigger_type' => 'card_moved_to_list',
                        'trigger_config' => ['list_name' => 'Revisao'],
                        'action_type' => 'add_comment',
                        'action_config' => ['text' => 'Aguardando aprovacao do cliente.'],
                        'is_active' => true,
                        'position' => 2000,
                    ],
                ],
            ],
            'freelancer' => [
                'name' => 'Meus Projetos',
                'description' => 'Organize seus projetos pessoais de forma simples. Controle prazos, prioridades e entregas.',
                'lists' => [
                    ['name' => 'Orcamento', 'position' => 1000],
                    ['name' => 'A Fazer', 'position' => 2000],
                    ['name' => 'Em Andamento', 'position' => 3000],
                    ['name' => 'Revisao', 'position' => 4000],
                    ['name' => 'Concluido', 'position' => 5000],
                ],
                'labels' => [
                    ['name' => 'Urgente', 'color' => '#ef4444'],
                    ['name' => 'Design', 'color' => '#8b5cf6'],
                    ['name' => 'Desenvolvimento', 'color' => '#3b82f6'],
                    ['name' => 'Conteudo', 'color' => '#22c55e'],
                    ['name' => 'Consultoria', 'color' => '#f97316'],
                ],
                'custom_fields' => [
                    ['name' => 'Valor do Projeto', 'type' => 'number', 'options' => null, 'position' => 1],
                    ['name' => 'Prazo', 'type' => 'date', 'options' => null, 'position' => 2],
                    ['name' => 'Prioridade', 'type' => 'dropdown', 'options' => ['Alta', 'Media', 'Baixa'], 'position' => 3],
                ],
                'automations' => [
                    [
                        'name' => 'Concluido → Marcar prazo concluido',
                        'trigger_type' => 'card_moved_to_list',
                        'trigger_config' => ['list_name' => 'Concluido'],
                        'action_type' => 'mark_due_complete',
                        'action_config' => [],
                        'is_active' => true,
                        'position' => 1000,
                    ],
                ],
            ],
            'client' => [
                'name' => 'Meus Pedidos',
                'description' => 'Acompanhe o status dos seus pedidos e aprovacoes de forma simples e visual.',
                'lists' => [
                    ['name' => 'Solicitacoes', 'position' => 1000],
                    ['name' => 'Em Producao', 'position' => 2000],
                    ['name' => 'Para Aprovar', 'position' => 3000],
                    ['name' => 'Aprovado', 'position' => 4000],
                ],
                'labels' => [
                    ['name' => 'Urgente', 'color' => '#ef4444'],
                    ['name' => 'Logo', 'color' => '#6366f1'],
                    ['name' => 'Social Media', 'color' => '#f97316'],
                    ['name' => 'Material Grafico', 'color' => '#22c55e'],
                ],
                'custom_fields' => [
                    ['name' => 'Prazo Desejado', 'type' => 'date', 'options' => null, 'position' => 1],
                    ['name' => 'Tipo de Pedido', 'type' => 'dropdown', 'options' => ['Logo', 'Post', 'Banner', 'Apresentacao', 'Video', 'Outro'], 'position' => 2],
                ],
                'automations' => [
                    [
                        'name' => 'Aprovado → Marcar concluido',
                        'trigger_type' => 'card_moved_to_list',
                        'trigger_config' => ['list_name' => 'Aprovado'],
                        'action_type' => 'mark_due_complete',
                        'action_config' => [],
                        'is_active' => true,
                        'position' => 1000,
                    ],
                ],
            ],
            default => $this->getDefaultTemplateData('studio'),
        };
    }

    public function applyTemplate(Board $board, string $template): void
    {
        $lists = match ($template) {
            'agency_pipeline' => ['Briefing', 'Produção', 'Revisão Interna', 'Revisão Cliente', 'Aprovado', 'Entregue'],
            'design_pipeline' => ['Briefing', 'Em Criação', 'Revisão Cliente', 'Aprovado', 'Entregue'],
            'freelancer_simple' => ['A Fazer', 'Em Progresso', 'Em Revisão', 'Concluído'],
            'client_portal' => ['Solicitações', 'Em Andamento', 'Para Revisão', 'Aprovado'],
            default => ['A Fazer', 'Em Progresso', 'Concluído'],
        };

        foreach ($lists as $i => $name) {
            BoardList::create([
                'board_id' => $board->id,
                'name' => $name,
                'position' => ($i + 1) * 1000,
            ]);
        }
    }

    public function createLabelsForTemplate(Board $board, string $template): void
    {
        $labels = match ($template) {
            'agency_pipeline' => [
                ['name' => 'Campanha', 'color' => '#61bd4f'],
                ['name' => 'Social Media', 'color' => '#f2d600'],
                ['name' => 'Blog', 'color' => '#ff9f1a'],
                ['name' => 'Email Mkt', 'color' => '#eb5a46'],
                ['name' => 'Branding', 'color' => '#c377e0'],
                ['name' => 'Ads', 'color' => '#0079bf'],
            ],
            'freelancer_simple' => [
                ['name' => 'Urgente', 'color' => '#eb5a46'],
                ['name' => 'Design', 'color' => '#c377e0'],
                ['name' => 'Desenvolvimento', 'color' => '#0079bf'],
                ['name' => 'Conteúdo', 'color' => '#61bd4f'],
                ['name' => 'Revisão', 'color' => '#f2d600'],
            ],
            'client_portal' => [
                ['name' => 'Logo', 'color' => '#61bd4f'],
                ['name' => 'Social Media', 'color' => '#f2d600'],
                ['name' => 'Website', 'color' => '#0079bf'],
                ['name' => 'Marketing', 'color' => '#ff9f1a'],
                ['name' => 'Urgente', 'color' => '#eb5a46'],
            ],
            default => [
                ['name' => 'Logo', 'color' => '#61bd4f'],
                ['name' => 'Branding', 'color' => '#f2d600'],
                ['name' => 'Social Media', 'color' => '#ff9f1a'],
                ['name' => 'Web', 'color' => '#eb5a46'],
                ['name' => 'Print', 'color' => '#c377e0'],
                ['name' => 'Ilustração', 'color' => '#0079bf'],
            ],
        };

        foreach ($labels as $label) {
            $board->labels()->create($label);
        }
    }

    public function createList(Board $board, array $data): BoardList
    {
        $maxPosition = $board->lists()->max('position') ?? 0;

        return BoardList::create([
            'board_id' => $board->id,
            'name' => $data['name'],
            'position' => $maxPosition + 1000,
        ]);
    }

    public function reorderLists(Board $board, array $positions): void
    {
        foreach ($positions as $item) {
            BoardList::where('id', $item['id'])
                ->where('board_id', $board->id)
                ->update(['position' => $item['position']]);
        }
    }

    public function createCard(BoardList $list, array $data, User $user): Card
    {
        $maxPosition = Card::where('list_id', $list->id)->max('position') ?? 0;

        $card = Card::create([
            'list_id' => $list->id,
            'board_id' => $list->board_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'position' => $maxPosition + 1000,
            'due_date' => $data['due_date'] ?? null,
            'created_by_id' => $user->id,
        ]);

        $this->logActivity($card, $user, 'card_created', [
            'list_name' => $list->name,
        ]);

        app(AutomationService::class)->execute('card_created', $card);

        // Power-Up hooks
        $workspace = $list->board->workspace;
        $this->dispatchSlackNotification($workspace, 'card_created', [
            'user_name' => $user->name,
            'card_title' => $card->title,
            'list_name' => $list->name,
            'board_name' => $list->board->name,
        ]);

        if ($card->due_date) {
            $this->dispatchCalendarSync($card, $workspace);
        }

        return $card;
    }

    public function moveCard(Card $card, string $listId, float $position, User $user): Card
    {
        $oldList = $card->list;
        $card->update([
            'list_id' => $listId,
            'position' => $position,
        ]);

        $newList = BoardList::find($listId);

        $this->logActivity($card, $user, 'card_moved', [
            'from_list' => $oldList->name,
            'to_list' => $newList->name,
        ]);

        $freshCard = $card->fresh();
        app(AutomationService::class)->execute('card_moved_to_list', $freshCard);

        // Slack notification
        $workspace = $freshCard->board->workspace;
        $this->dispatchSlackNotification($workspace, 'card_moved', [
            'user_name' => $user->name,
            'card_title' => $freshCard->title,
            'from_list' => $oldList->name,
            'to_list' => $newList->name,
            'board_name' => $freshCard->board->name,
        ]);

        return $freshCard;
    }

    public function reorderCards(string $listId, array $positions): void
    {
        foreach ($positions as $item) {
            Card::where('id', $item['id'])
                ->where('list_id', $listId)
                ->update(['position' => $item['position']]);
        }
    }

    public function uploadAttachment(Card $card, UploadedFile $file, User $user): CardAttachment
    {
        $path = $file->store("attachments/{$card->board_id}/{$card->id}", 'public');

        $attachment = CardAttachment::create([
            'card_id' => $card->id,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by_id' => $user->id,
        ]);

        if (str_starts_with($file->getMimeType(), 'image/') && !$card->cover_url) {
            $this->setAttachmentAsCover($attachment);
        }

        $this->logActivity($card, $user, 'attachment_added', [
            'filename' => $file->getClientOriginalName(),
        ]);

        return $attachment;
    }

    public function setAttachmentAsCover(CardAttachment $attachment): void
    {
        CardAttachment::where('card_id', $attachment->card_id)
            ->where('id', '!=', $attachment->id)
            ->update(['is_cover' => false]);

        $attachment->update(['is_cover' => true]);
        $attachment->card->update(['cover_url' => '/storage/' . $attachment->path]);
    }

    public function duplicateCard(Card $card, User $user): Card
    {
        $card->load(['labels', 'members', 'checklists.items']);

        $maxPosition = Card::where('list_id', $card->list_id)->max('position') ?? 0;

        $newCard = Card::create([
            'list_id' => $card->list_id,
            'board_id' => $card->board_id,
            'title' => $card->title . ' (cópia)',
            'description' => $card->description,
            'position' => $maxPosition + 1000,
            'due_date' => $card->due_date,
            'created_by_id' => $user->id,
        ]);

        // Copy labels
        if ($card->labels->isNotEmpty()) {
            $newCard->labels()->attach($card->labels->pluck('id'));
        }

        // Copy members
        if ($card->members->isNotEmpty()) {
            $newCard->members()->attach($card->members->pluck('id'));
        }

        // Copy checklists and items
        foreach ($card->checklists as $checklist) {
            $newChecklist = $newCard->checklists()->create([
                'title' => $checklist->title,
                'position' => $checklist->position,
            ]);
            foreach ($checklist->items as $item) {
                $newChecklist->items()->create([
                    'title' => $item->title,
                    'position' => $item->position,
                    'is_checked' => false,
                ]);
            }
        }

        $this->logActivity($newCard, $user, 'card_created', [
            'list_name' => $card->list?->name ?? '',
            'duplicated_from' => $card->id,
        ]);

        return $newCard->load(['labels', 'members:id,name', 'checklists.items']);
    }

    public function logActivity(Card $card, User $user, string $action, ?array $data = null): CardActivity
    {
        return CardActivity::create([
            'card_id' => $card->id,
            'user_id' => $user->id,
            'action' => $action,
            'data' => $data,
        ]);
    }

    public function dispatchSlackNotification(Workspace $workspace, string $event, array $data): void
    {
        if (app(PowerUpService::class)->isInstalled($workspace, 'slack')) {
            SendSlackNotification::dispatch($workspace, $event, $data);
        }
    }

    public function dispatchCalendarSync(Card $card, Workspace $workspace): void
    {
        if (app(PowerUpService::class)->isInstalled($workspace, 'google_calendar')) {
            SyncCalendarEvent::dispatch($card, $workspace);
        }
    }
}
