<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\Card;
use App\Domain\Board\Models\CardCustomFieldValue;
use App\Domain\Board\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomFieldController
{
    public function index(Board $board): JsonResponse
    {
        $fields = $board->customFields()->orderBy('position')->get();

        return response()->json(['data' => $fields]);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        // Check custom_fields feature
        $plan = $board->workspace->plan;
        if (!$plan->hasFeature('custom_fields')) {
            return response()->json(['error' => ['message' => 'Campos customizados nao estao disponiveis no plano atual.']], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:text,number,date,dropdown,checkbox',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100',
        ]);

        $maxPosition = $board->customFields()->max('position') ?? 0;
        $data['position'] = $maxPosition + 1;

        $field = $board->customFields()->create($data);

        return response()->json(['data' => $field], 201);
    }

    public function update(Request $request, Board $board, CustomField $customField): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|string|in:text,number,date,dropdown,checkbox',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100',
        ]);

        $customField->update($data);

        return response()->json(['data' => $customField]);
    }

    public function destroy(Board $board, CustomField $customField): JsonResponse
    {
        $customField->delete();

        return response()->json(null, 204);
    }

    public function updateValue(Request $request, Card $card, CustomField $customField): JsonResponse
    {
        $data = $request->validate([
            'value' => 'nullable|string|max:5000',
        ]);

        $fieldValue = CardCustomFieldValue::updateOrCreate(
            [
                'card_id' => $card->id,
                'custom_field_id' => $customField->id,
            ],
            ['value' => $data['value']]
        );

        return response()->json(['data' => $fieldValue]);
    }

    public function cardValues(Card $card): JsonResponse
    {
        $values = $card->customFieldValues()->with('customField')->get();

        return response()->json(['data' => $values]);
    }
}
