<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardTemplateController
{
    public function index(Request $request): JsonResponse
    {
        $templates = BoardTemplate::where('workspace_id', $request->workspace->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $templates]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'lists' => 'required|array|min:1',
            'lists.*.name' => 'required|string|max:100',
            'lists.*.position' => 'required|numeric',
            'labels' => 'nullable|array',
            'labels.*.name' => 'required|string|max:50',
            'labels.*.color' => 'required|string|max:20',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.name' => 'required|string|max:100',
            'custom_fields.*.type' => 'required|string|in:text,number,date,dropdown,checkbox',
            'custom_fields.*.options' => 'nullable|array',
            'custom_fields.*.position' => 'required|numeric',
            'automations' => 'nullable|array',
            'automations.*.name' => 'required|string|max:150',
            'automations.*.trigger_type' => 'required|string',
            'automations.*.trigger_config' => 'nullable|array',
            'automations.*.action_type' => 'required|string',
            'automations.*.action_config' => 'nullable|array',
            'automations.*.is_active' => 'sometimes|boolean',
            'automations.*.position' => 'sometimes|numeric',
        ]);

        // Free plan: limit to 1 template (the default one)
        $plan = $request->workspace->plan;
        if ($plan && $plan->price_monthly <= 0) {
            $count = BoardTemplate::where('workspace_id', $request->workspace->id)->count();
            if ($count >= 1) {
                return response()->json([
                    'error' => [
                        'code' => 'TEMPLATE_LIMIT',
                        'message' => 'Plano gratis permite apenas 1 template. Faca upgrade para criar mais.',
                    ],
                ], 403);
            }
        }

        $template = BoardTemplate::create([
            'workspace_id' => $request->workspace->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'lists' => $data['lists'],
            'labels' => $data['labels'] ?? [],
            'custom_fields' => $data['custom_fields'] ?? [],
            'automations' => $data['automations'] ?? [],
            'created_by_id' => auth()->id(),
        ]);

        return response()->json(['data' => $template], 201);
    }

    public function update(Request $request, BoardTemplate $template): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:150',
            'description' => 'nullable|string|max:1000',
            'lists' => 'sometimes|array|min:1',
            'lists.*.name' => 'required|string|max:100',
            'lists.*.position' => 'required|numeric',
            'labels' => 'nullable|array',
            'labels.*.name' => 'required|string|max:50',
            'labels.*.color' => 'required|string|max:20',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.name' => 'required|string|max:100',
            'custom_fields.*.type' => 'required|string|in:text,number,date,dropdown,checkbox',
            'custom_fields.*.options' => 'nullable|array',
            'custom_fields.*.position' => 'required|numeric',
            'automations' => 'nullable|array',
            'automations.*.name' => 'required|string|max:150',
            'automations.*.trigger_type' => 'required|string',
            'automations.*.trigger_config' => 'nullable|array',
            'automations.*.action_type' => 'required|string',
            'automations.*.action_config' => 'nullable|array',
            'automations.*.is_active' => 'sometimes|boolean',
            'automations.*.position' => 'sometimes|numeric',
        ]);

        $template->update($data);

        return response()->json(['data' => $template]);
    }

    public function destroy(BoardTemplate $template): JsonResponse
    {
        if ($template->is_default) {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_DELETE_DEFAULT',
                    'message' => 'O template padrao nao pode ser excluido.',
                ],
            ], 403);
        }

        $template->delete();

        return response()->json(null, 204);
    }

    public function createFromBoard(Request $request, Board $board): JsonResponse
    {
        // Free plan: limit to 1 template
        $plan = $request->workspace->plan;
        if ($plan && $plan->price_monthly <= 0) {
            $count = BoardTemplate::where('workspace_id', $request->workspace->id)->count();
            if ($count >= 1) {
                return response()->json([
                    'error' => [
                        'code' => 'TEMPLATE_LIMIT',
                        'message' => 'Plano gratis permite apenas 1 template. Faca upgrade para criar mais.',
                    ],
                ], 403);
            }
        }

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
        ]);

        $board->load([
            'lists' => fn($q) => $q->where('is_archived', false)->orderBy('position'),
            'labels',
            'customFields',
            'automations',
        ]);

        $lists = $board->lists->map(fn($list, $i) => [
            'name' => $list->name,
            'position' => ($i + 1) * 1000,
        ])->values()->toArray();

        $labels = $board->labels->map(fn($label) => [
            'name' => $label->name,
            'color' => $label->color,
        ])->values()->toArray();

        $customFields = $board->customFields->map(fn($field) => [
            'name' => $field->name,
            'type' => $field->type,
            'options' => $field->options,
            'position' => $field->position,
        ])->values()->toArray();

        $automations = $this->convertAutomationIdsToNames($board);

        $template = BoardTemplate::create([
            'workspace_id' => $request->workspace->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'lists' => $lists,
            'labels' => $labels,
            'custom_fields' => $customFields,
            'automations' => $automations,
            'created_by_id' => auth()->id(),
        ]);

        return response()->json(['data' => $template], 201);
    }

    public function applyToBoard(Request $request, BoardTemplate $template, Board $board): JsonResponse
    {
        $plan = $request->workspace->plan;

        // Delete existing structure
        $board->lists()->delete();
        $board->labels()->delete();
        $board->customFields()->delete();
        $board->automations()->delete();

        // Create lists and build name→id map
        $listNameToId = [];
        foreach ($template->lists as $listData) {
            $list = $board->lists()->create([
                'name' => $listData['name'],
                'position' => $listData['position'],
            ]);
            $listNameToId[$listData['name']] = $list->id;
        }

        // Create labels and build name→id map
        $labelNameToId = [];
        if ($template->labels) {
            foreach ($template->labels as $labelData) {
                $label = $board->labels()->create([
                    'name' => $labelData['name'],
                    'color' => $labelData['color'],
                ]);
                $labelNameToId[$labelData['name']] = $label->id;
            }
        }

        // Create custom fields (if plan supports)
        if ($template->custom_fields && $plan && $plan->hasFeature('custom_fields')) {
            foreach ($template->custom_fields as $fieldData) {
                $board->customFields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'options' => $fieldData['options'] ?? null,
                    'position' => $fieldData['position'] ?? 0,
                ]);
            }
        }

        // Create automations (if plan supports) with name→id resolution
        if ($template->automations && $plan && $plan->hasFeature('automations')) {
            foreach ($template->automations as $autoData) {
                $triggerConfig = $this->resolveNamesToIds(
                    $autoData['trigger_config'] ?? [],
                    $listNameToId,
                    $labelNameToId
                );
                $actionConfig = $this->resolveNamesToIds(
                    $autoData['action_config'] ?? [],
                    $listNameToId,
                    $labelNameToId
                );

                $board->automations()->create([
                    'name' => $autoData['name'],
                    'trigger_type' => $autoData['trigger_type'],
                    'trigger_config' => $triggerConfig,
                    'action_type' => $autoData['action_type'],
                    'action_config' => $actionConfig,
                    'is_active' => $autoData['is_active'] ?? true,
                    'position' => $autoData['position'] ?? 0,
                ]);
            }
        }

        $board->load('lists', 'labels', 'customFields', 'automations');

        return response()->json(['data' => $board]);
    }

    private function convertAutomationIdsToNames(Board $board): array
    {
        $listMap = $board->lists->pluck('name', 'id')->toArray();
        $labelMap = $board->labels->pluck('name', 'id')->toArray();

        return $board->automations->map(function ($auto) use ($listMap, $labelMap) {
            $triggerConfig = $auto->trigger_config ?? [];
            $actionConfig = $auto->action_config ?? [];

            // Convert list_id → list_name
            if (isset($triggerConfig['list_id'])) {
                $triggerConfig['list_name'] = $listMap[$triggerConfig['list_id']] ?? null;
                unset($triggerConfig['list_id']);
            }
            if (isset($actionConfig['list_id'])) {
                $actionConfig['list_name'] = $listMap[$actionConfig['list_id']] ?? null;
                unset($actionConfig['list_id']);
            }

            // Convert label_id → label_name
            if (isset($triggerConfig['label_id'])) {
                $triggerConfig['label_name'] = $labelMap[$triggerConfig['label_id']] ?? null;
                unset($triggerConfig['label_id']);
            }
            if (isset($actionConfig['label_id'])) {
                $actionConfig['label_name'] = $labelMap[$actionConfig['label_id']] ?? null;
                unset($actionConfig['label_id']);
            }

            // Drop user_id references (not templatizable)
            unset($triggerConfig['user_id'], $actionConfig['user_id']);

            return [
                'name' => $auto->name,
                'trigger_type' => $auto->trigger_type,
                'trigger_config' => $triggerConfig,
                'action_type' => $auto->action_type,
                'action_config' => $actionConfig,
                'is_active' => $auto->is_active,
                'position' => $auto->position,
            ];
        })->values()->toArray();
    }

    private function resolveNamesToIds(array $config, array $listNameToId, array $labelNameToId): array
    {
        if (isset($config['list_name'])) {
            $id = $listNameToId[$config['list_name']] ?? null;
            if ($id) {
                $config['list_id'] = $id;
            }
            unset($config['list_name']);
        }

        if (isset($config['label_name'])) {
            $id = $labelNameToId[$config['label_name']] ?? null;
            if ($id) {
                $config['label_id'] = $id;
            }
            unset($config['label_name']);
        }

        return $config;
    }
}
