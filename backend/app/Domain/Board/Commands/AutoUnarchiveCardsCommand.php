<?php

namespace App\Domain\Board\Commands;

use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\BoardService;
use Illuminate\Console\Command;

class AutoUnarchiveCardsCommand extends Command
{
    protected $signature = 'cards:auto-unarchive';

    protected $description = 'Restaura automaticamente cards com data de desarquivamento vencida';

    public function handle(BoardService $boardService): int
    {
        $cards = Card::where('is_archived', true)
            ->whereNotNull('unarchive_at')
            ->where('unarchive_at', '<=', now())
            ->get();

        if ($cards->isEmpty()) {
            $this->info('Nenhum card para desarquivar.');
            return 0;
        }

        $count = 0;

        foreach ($cards as $card) {
            $targetListId = $card->unarchive_list_id ?? $card->list_id;

            $card->update([
                'is_archived' => false,
                'list_id' => $targetListId,
                'archive_reason' => null,
                'unarchive_at' => null,
                'unarchive_list_id' => null,
            ]);

            $boardService->logActivity($card, $card->createdBy, 'card_auto_restored', [
                'list_name' => $card->list?->name,
            ]);

            $count++;
        }

        $this->info("Desarquivados {$count} card(s).");

        return 0;
    }
}
