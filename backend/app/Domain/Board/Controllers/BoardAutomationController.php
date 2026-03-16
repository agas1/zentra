<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardAutomation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardAutomationController
{
    private const TRIGGER_TYPES = 'card_moved_to_list,card_created,checklist_completed,label_added,member_assigned';
    private const ACTION_TYPES = 'assign_member,set_due_date,add_label,move_to_list,mark_due_complete,archive_card,add_comment,add_checklist';

    public function index(Board $board): JsonResponse
    {
        $automations = $board->automations()->orderBy('position')->get();

        return response()->json(['data' => $automations]);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $plan = $board->workspace->plan;
        if (!$plan->hasFeature('automations')) {
            return response()->json([
                'error' => ['message' => 'Automações não estão disponíveis no plano atual. Faça upgrade para usar esta funcionalidade.'],
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'trigger_type' => 'required|string|in:' . self::TRIGGER_TYPES,
            'trigger_config' => 'nullable|array',
            'action_type' => 'required|string|in:' . self::ACTION_TYPES,
            'action_config' => 'nullable|array',
        ]);

        $maxPosition = $board->automations()->max('position') ?? 0;

        $automation = $board->automations()->create([
            'name' => $data['name'],
            'trigger_type' => $data['trigger_type'],
            'trigger_config' => $data['trigger_config'] ?? [],
            'action_type' => $data['action_type'],
            'action_config' => $data['action_config'] ?? [],
            'position' => $maxPosition + 1000,
        ]);

        return response()->json(['data' => $automation], 201);
    }

    public function update(Request $request, BoardAutomation $automation): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:150',
            'trigger_type' => 'sometimes|string|in:' . self::TRIGGER_TYPES,
            'trigger_config' => 'sometimes|array',
            'action_type' => 'sometimes|string|in:' . self::ACTION_TYPES,
            'action_config' => 'sometimes|array',
        ]);

        $automation->update($data);

        return response()->json(['data' => $automation->fresh()]);
    }

    public function toggle(BoardAutomation $automation): JsonResponse
    {
        $automation->update(['is_active' => !$automation->is_active]);

        return response()->json(['data' => $automation]);
    }

    public function destroy(BoardAutomation $automation): JsonResponse
    {
        $automation->delete();

        return response()->json(null, 204);
    }

    public function presets(Board $board): JsonResponse
    {
        $lists = $board->lists()->where('is_archived', false)->orderBy('position')->get();
        $listNames = $lists->pluck('name', 'id')->toArray();

        // Detectar template pelo nome das listas
        $names = array_values($listNames);
        $isDesignPipeline = count(array_intersect($names, ['Briefing', 'Em Criação', 'Revisão Cliente', 'Aprovado', 'Entregue'])) >= 3;

        $findList = fn (string $name) => $lists->firstWhere('name', $name)?->id;

        $presets = [];

        if ($isDesignPipeline) {
            // Presets para pipeline de design
            $briefingId = $findList('Briefing');
            $emCriacaoId = $findList('Em Criação');
            $revisaoId = $findList('Revisão Cliente');
            $aprovadoId = $findList('Aprovado');
            $entregueId = $findList('Entregue');

            if ($briefingId) {
                $presets[] = [
                    'category' => 'design',
                    'name' => 'Novo briefing → Checklist automático',
                    'description' => 'Quando um card é criado em Briefing, adiciona checklist com itens essenciais do briefing',
                    'trigger_type' => 'card_created',
                    'trigger_config' => ['list_id' => $briefingId],
                    'action_type' => 'add_checklist',
                    'action_config' => [
                        'title' => 'Briefing',
                        'items' => [
                            'Referências coletadas',
                            'Dimensões definidas',
                            'Paleta de cores definida',
                            'Prazo alinhado com cliente',
                            'Arquivos fonte recebidos',
                        ],
                    ],
                ];
            }

            if ($briefingId) {
                $presets[] = [
                    'category' => 'design',
                    'name' => 'Novo briefing → Prazo de 5 dias',
                    'description' => 'Define automaticamente um prazo de 5 dias para novos cards em Briefing',
                    'trigger_type' => 'card_created',
                    'trigger_config' => ['list_id' => $briefingId],
                    'action_type' => 'set_due_date',
                    'action_config' => ['days' => 5],
                ];
            }

            if ($emCriacaoId) {
                $presets[] = [
                    'category' => 'design',
                    'name' => 'Checklist completo → Mover para Em Criação',
                    'description' => 'Quando todos os itens do checklist são marcados, move o card automaticamente',
                    'trigger_type' => 'checklist_completed',
                    'trigger_config' => [],
                    'action_type' => 'move_to_list',
                    'action_config' => ['list_id' => $emCriacaoId],
                ];
            }

            if ($revisaoId) {
                $presets[] = [
                    'category' => 'design',
                    'name' => 'Enviado para revisão → Comentário automático',
                    'description' => 'Adiciona um lembrete quando o card vai para revisão do cliente',
                    'trigger_type' => 'card_moved_to_list',
                    'trigger_config' => ['list_id' => $revisaoId],
                    'action_type' => 'add_comment',
                    'action_config' => ['text' => '📋 Aguardando aprovação do cliente. Lembre-se de enviar o link de visualização.'],
                ];
            }

            if ($aprovadoId) {
                $presets[] = [
                    'category' => 'design',
                    'name' => 'Aprovado → Marcar prazo concluído',
                    'description' => 'Quando o design é aprovado, marca o prazo como concluído',
                    'trigger_type' => 'card_moved_to_list',
                    'trigger_config' => ['list_id' => $aprovadoId],
                    'action_type' => 'mark_due_complete',
                    'action_config' => [],
                ];
            }

            if ($entregueId) {
                $presets[] = [
                    'category' => 'design',
                    'name' => 'Entregue → Arquivar card',
                    'description' => 'Arquiva automaticamente cards que foram entregues ao cliente',
                    'trigger_type' => 'card_moved_to_list',
                    'trigger_config' => ['list_id' => $entregueId],
                    'action_type' => 'archive_card',
                    'action_config' => [],
                ];
            }
        }

        // Presets genéricos (servem pra qualquer board)
        $lastList = $lists->last();
        $firstList = $lists->first();

        if ($lastList) {
            $presets[] = [
                'category' => 'geral',
                'name' => 'Concluído → Arquivar card',
                'description' => "Quando um card é movido para \"{$lastList->name}\", arquiva automaticamente",
                'trigger_type' => 'card_moved_to_list',
                'trigger_config' => ['list_id' => $lastList->id],
                'action_type' => 'archive_card',
                'action_config' => [],
            ];
        }

        $presets[] = [
            'category' => 'geral',
            'name' => 'Novo card → Prazo de 3 dias',
            'description' => 'Define um prazo automático de 3 dias para qualquer card criado',
            'trigger_type' => 'card_created',
            'trigger_config' => [],
            'action_type' => 'set_due_date',
            'action_config' => ['days' => 3],
        ];

        if ($lastList) {
            $presets[] = [
                'category' => 'geral',
                'name' => 'Checklist completo → Mover para ' . $lastList->name,
                'description' => 'Quando todos os itens são marcados, move o card para a última lista',
                'trigger_type' => 'checklist_completed',
                'trigger_config' => [],
                'action_type' => 'move_to_list',
                'action_config' => ['list_id' => $lastList->id],
            ];
        }

        // Presets para atendimento
        $presets[] = [
            'category' => 'atendimento',
            'name' => 'Novo ticket → Checklist de atendimento',
            'description' => 'Adiciona checklist padrão de atendimento ao cliente',
            'trigger_type' => 'card_created',
            'trigger_config' => [],
            'action_type' => 'add_checklist',
            'action_config' => [
                'title' => 'Atendimento',
                'items' => [
                    'Entender a solicitação do cliente',
                    'Verificar histórico do cliente',
                    'Resolver ou encaminhar',
                    'Confirmar resolução com cliente',
                    'Registrar solução aplicada',
                ],
            ],
        ];

        $presets[] = [
            'category' => 'atendimento',
            'name' => 'Novo ticket → SLA de 2 dias',
            'description' => 'Define um SLA automático de 48h para novos tickets',
            'trigger_type' => 'card_created',
            'trigger_config' => [],
            'action_type' => 'set_due_date',
            'action_config' => ['days' => 2],
        ];

        return response()->json(['data' => $presets]);
    }
}
