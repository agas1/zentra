<?php

namespace App\Domain\Plan\Controllers;

use App\Domain\Plan\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController
{
    public function index(): JsonResponse
    {
        $plans = Plan::active()->ordered()->get();

        return response()->json(['data' => $plans]);
    }

    public function usage(Request $request): JsonResponse
    {
        $workspace = $request->workspace;
        $plan = $workspace->plan;

        $membersCount = $workspace->members()->count();
        $boardsCount = $workspace->boards()->count();

        // Calculate storage: sum of all attachment sizes across all boards
        $storageUsedBytes = $workspace->boards()
            ->join('cards', 'boards.id', '=', 'cards.board_id')
            ->join('card_attachments', 'cards.id', '=', 'card_attachments.card_id')
            ->sum('card_attachments.size');

        $storageUsedMb = round($storageUsedBytes / (1024 * 1024), 1);

        return response()->json([
            'data' => [
                'plan' => $plan,
                'usage' => [
                    'members' => [
                        'used' => $membersCount,
                        'limit' => $plan->max_members,
                        'unlimited' => $plan->isUnlimited('max_members'),
                    ],
                    'boards' => [
                        'used' => $boardsCount,
                        'limit' => $plan->max_boards,
                        'unlimited' => $plan->isUnlimited('max_boards'),
                    ],
                    'storage_mb' => [
                        'used' => $storageUsedMb,
                        'limit' => $plan->max_storage_mb,
                        'unlimited' => false,
                    ],
                ],
            ],
        ]);
    }
}
