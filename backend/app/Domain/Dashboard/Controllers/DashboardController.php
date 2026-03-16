<?php

namespace App\Domain\Dashboard\Controllers;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardList;
use App\Domain\Board\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    public function metrics(Request $request): JsonResponse
    {
        $workspace = $request->workspace;

        $boards = $workspace->boards()
            ->where('is_archived', false)
            ->with(['lists' => fn($q) => $q->where('is_archived', false)->orderBy('position')])
            ->get();

        $boardIds = $boards->pluck('id');

        if ($boardIds->isEmpty()) {
            return response()->json([
                'data' => [
                    'overview' => [
                        'total_boards' => 0,
                        'total_cards' => 0,
                        'cards_completed' => 0,
                        'cards_overdue' => 0,
                        'active_members' => $workspace->members()->count(),
                    ],
                    'cards_by_status' => [],
                    'boards_summary' => [],
                    'completion_trend' => [],
                ],
            ]);
        }

        // Build a map of last list IDs per board (highest position = completed)
        $lastListMap = []; // board_id => last_list_id
        $lastListIds = [];
        foreach ($boards as $board) {
            $lastList = $board->lists->last();
            if ($lastList) {
                $lastListMap[$board->id] = $lastList->id;
                $lastListIds[] = $lastList->id;
            }
        }

        // Single query: count cards per list (non-archived) for all boards at once
        $cardCountsByList = Card::whereIn('board_id', $boardIds)
            ->where('is_archived', false)
            ->select('list_id', DB::raw('count(*) as cnt'))
            ->groupBy('list_id')
            ->pluck('cnt', 'list_id');

        // Derive totals from the grouped counts
        $totalCards = $cardCountsByList->sum();
        $cardsCompleted = collect($lastListIds)->sum(fn($id) => $cardCountsByList->get($id, 0));

        // Overdue cards
        $cardsOverdue = Card::whereIn('board_id', $boardIds)
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('list_id', $lastListIds)
            ->count();

        $activeMembers = $workspace->members()->count();

        // Cards by status
        $allListIds = $boards->pluck('lists')->flatten()->pluck('id');
        $cardsByStatus = Card::whereIn('cards.list_id', $allListIds)
            ->where('cards.is_archived', false)
            ->join('board_lists', 'cards.list_id', '=', 'board_lists.id')
            ->selectRaw('board_lists.name as list_name, count(*) as count')
            ->groupBy('board_lists.name')
            ->orderByDesc('count')
            ->get();

        // Boards summary - no more N+1 loops, use pre-fetched cardCountsByList
        $boardsSummary = [];
        foreach ($boards as $board) {
            $boardListIds = $board->lists->pluck('id');
            $boardTotalCards = $boardListIds->sum(fn($id) => $cardCountsByList->get($id, 0));

            $boardLastListId = $lastListMap[$board->id] ?? null;
            $boardCompleted = $boardLastListId ? ($cardCountsByList->get($boardLastListId, 0)) : 0;

            $progress = $boardTotalCards > 0 ? round(($boardCompleted / $boardTotalCards) * 100) : 0;

            $boardsSummary[] = [
                'id' => $board->id,
                'name' => $board->name,
                'client_name' => $board->client_name,
                'background_color' => $board->background_color,
                'total_cards' => $boardTotalCards,
                'completed' => $boardCompleted,
                'progress' => $progress,
            ];
        }

        // Completion trend (last 4 weeks) - single query with grouping
        $fourWeeksAgo = Carbon::now()->subWeeks(3)->startOfWeek();
        $trendRaw = Card::whereIn('list_id', $lastListIds)
            ->where('is_archived', false)
            ->where('updated_at', '>=', $fourWeeksAgo)
            ->select(DB::raw("date_trunc('week', updated_at) as week_start"), DB::raw('count(*) as cnt'))
            ->groupBy('week_start')
            ->pluck('cnt', 'week_start');

        $completionTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $key = $weekStart->toDateTimeString();
            $completionTrend[] = [
                'week' => $weekStart->toDateString(),
                'completed' => $trendRaw->get($key, 0),
            ];
        }

        return response()->json([
            'data' => [
                'overview' => [
                    'total_boards' => $boards->count(),
                    'total_cards' => $totalCards,
                    'cards_completed' => $cardsCompleted,
                    'cards_overdue' => $cardsOverdue,
                    'active_members' => $activeMembers,
                ],
                'cards_by_status' => $cardsByStatus,
                'boards_summary' => $boardsSummary,
                'completion_trend' => $completionTrend,
            ],
        ]);
    }
}
