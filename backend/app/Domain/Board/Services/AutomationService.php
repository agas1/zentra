<?php

namespace App\Domain\Board\Services;

use App\Domain\Board\Models\BoardAutomation;
use App\Domain\Board\Models\Card;
use App\Domain\Board\Models\CardComment;
use App\Domain\Board\Models\Checklist;
use App\Domain\Board\Models\ChecklistItem;

class AutomationService
{
    private static bool $executing = false;

    public function execute(string $trigger, Card $card, array $context = []): void
    {
        if (self::$executing) {
            return;
        }

        $automations = BoardAutomation::where('board_id', $card->board_id)
            ->where('trigger_type', $trigger)
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        foreach ($automations as $automation) {
            if ($this->matchesTrigger($automation, $card, $context)) {
                self::$executing = true;
                try {
                    $this->executeAction($automation, $card);
                } finally {
                    self::$executing = false;
                }
            }
        }
    }

    private function matchesTrigger(BoardAutomation $automation, Card $card, array $context): bool
    {
        return match ($automation->trigger_type) {
            'card_moved_to_list' => ($automation->trigger_config['list_id'] ?? null) === $card->list_id,
            'card_created' => empty($automation->trigger_config['list_id']) || $automation->trigger_config['list_id'] === $card->list_id,
            'checklist_completed' => true,
            'label_added' => isset($context['label_id']) && (
                empty($automation->trigger_config['label_id']) || $automation->trigger_config['label_id'] === $context['label_id']
            ),
            'member_assigned' => isset($context['user_id']) && (
                empty($automation->trigger_config['user_id']) || $automation->trigger_config['user_id'] === $context['user_id']
            ),
            default => false,
        };
    }

    private function executeAction(BoardAutomation $automation, Card $card): void
    {
        match ($automation->action_type) {
            'assign_member' => $card->members()->syncWithoutDetaching([$automation->action_config['user_id']]),
            'set_due_date' => $card->update(['due_date' => now()->addDays($automation->action_config['days'] ?? 3)]),
            'add_label' => $card->labels()->syncWithoutDetaching([$automation->action_config['label_id']]),
            'move_to_list' => $card->update(['list_id' => $automation->action_config['list_id']]),
            'mark_due_complete' => $card->update(['due_completed' => true]),
            'archive_card' => $card->update(['is_archived' => true]),
            'add_comment' => $this->addComment($automation, $card),
            'add_checklist' => $this->addChecklist($automation, $card),
            default => null,
        };
    }

    private function addComment(BoardAutomation $automation, Card $card): void
    {
        $text = $automation->action_config['text'] ?? '';
        if (empty($text)) {
            return;
        }

        CardComment::create([
            'card_id' => $card->id,
            'user_id' => $card->created_by_id,
            'body' => $text,
        ]);
    }

    private function addChecklist(BoardAutomation $automation, Card $card): void
    {
        $title = $automation->action_config['title'] ?? 'Checklist';
        $items = $automation->action_config['items'] ?? [];

        if (empty($items)) {
            return;
        }

        $maxPosition = $card->checklists()->max('position') ?? 0;

        $checklist = Checklist::create([
            'card_id' => $card->id,
            'title' => $title,
            'position' => $maxPosition + 1000,
        ]);

        foreach ($items as $i => $itemTitle) {
            ChecklistItem::create([
                'checklist_id' => $checklist->id,
                'title' => $itemTitle,
                'position' => ($i + 1) * 1000,
                'is_checked' => false,
            ]);
        }
    }
}
