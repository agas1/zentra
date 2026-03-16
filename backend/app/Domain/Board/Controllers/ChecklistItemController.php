<?php

namespace App\Domain\Board\Controllers;

use App\Domain\Board\Models\Checklist;
use App\Domain\Board\Models\ChecklistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistItemController
{
    public function store(Request $request, Checklist $checklist): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
        ]);

        $maxPosition = $checklist->items()->max('position') ?? 0;

        $item = $checklist->items()->create([
            'title' => $data['title'],
            'position' => $maxPosition + 1000,
        ]);

        return response()->json(['data' => $item], 201);
    }

    public function update(Request $request, ChecklistItem $item): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'position' => 'sometimes|numeric',
        ]);

        $item->update($data);

        return response()->json(['data' => $item]);
    }

    public function toggle(ChecklistItem $item): JsonResponse
    {
        $item->update(['is_checked' => !$item->is_checked]);

        $automationTriggered = false;

        if ($item->is_checked) {
            $checklist = $item->checklist->load('items');
            $allChecked = $checklist->items->every(fn ($i) => $i->is_checked);

            if ($allChecked) {
                $card = $checklist->card;
                app(\App\Domain\Board\Services\AutomationService::class)->execute('checklist_completed', $card);
                $automationTriggered = true;
            }
        }

        return response()->json(['data' => $item, 'automation_triggered' => $automationTriggered]);
    }

    public function destroy(ChecklistItem $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, 204);
    }
}
