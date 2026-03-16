<?php

namespace App\Domain\Workspace\Controllers;

use App\Domain\Plan\Models\Plan;
use App\Domain\Workspace\Models\Workspace;
use App\Domain\Workspace\Requests\StoreWorkspaceRequest;
use App\Domain\Workspace\Requests\UpdateWorkspaceRequest;
use App\Domain\Workspace\Services\WorkspaceService;
use App\Http\Controllers\Controller;

class WorkspaceController extends Controller
{
    public function __construct(private WorkspaceService $service) {}

    public function index()
    {
        $user = auth()->user();

        $workspaces = $user->workspaces()
            ->with('plan')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $workspaces]);
    }

    public function store(StoreWorkspaceRequest $request)
    {
        $plan = Plan::where('is_default', true)->firstOrFail();
        $user = auth()->user();

        $workspace = $this->service->create($request->validated(), $plan, $user);

        return response()->json([
            'data' => $workspace,
            'message' => 'Workspace created successfully',
        ], 201);
    }

    public function show(Workspace $workspace)
    {
        $workspace->load(['plan', 'members']);

        return response()->json(['data' => $workspace]);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace)
    {
        $workspace = $this->service->update($workspace, $request->validated());

        return response()->json([
            'data' => $workspace,
            'message' => 'Workspace updated successfully',
        ]);
    }

    public function destroy(Workspace $workspace)
    {
        $workspace->update(['is_active' => false]);

        return response()->json(['message' => 'Workspace deactivated successfully']);
    }

    public function upgrade(\Illuminate\Http\Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::where('id', $data['plan_id'])->where('is_active', true)->firstOrFail();
        $currentPlan = $workspace->plan;

        // Validate downgrade: check if current usage exceeds new plan limits
        $membersCount = $workspace->members()->count();
        if (!$newPlan->isUnlimited('max_members') && $membersCount > $newPlan->max_members) {
            return response()->json([
                'error' => [
                    'code' => 'DOWNGRADE_MEMBER_LIMIT',
                    'message' => "O plano {$newPlan->name} permite apenas {$newPlan->max_members} membros. Voce tem {$membersCount}.",
                ],
            ], 422);
        }

        $boardsCount = $workspace->boards()->count();
        if (!$newPlan->isUnlimited('max_boards') && $boardsCount > $newPlan->max_boards) {
            return response()->json([
                'error' => [
                    'code' => 'DOWNGRADE_BOARD_LIMIT',
                    'message' => "O plano {$newPlan->name} permite apenas {$newPlan->max_boards} quadros. Voce tem {$boardsCount}.",
                ],
            ], 422);
        }

        $isCurrentFree = $currentPlan->price_monthly <= 0;
        $isNewFree = $newPlan->price_monthly <= 0;

        // Free → Free: direct swap
        if ($isCurrentFree && $isNewFree) {
            $workspace->update(['plan_id' => $newPlan->id]);

            return response()->json([
                'data' => $workspace->fresh()->load('plan'),
                'message' => 'Plano atualizado com sucesso.',
            ]);
        }

        // Free → Paid: payment required via checkout
        if ($isCurrentFree && !$isNewFree) {
            return response()->json([
                'error' => [
                    'code' => 'PAYMENT_REQUIRED',
                    'message' => 'Pagamento necessario. Use o checkout para assinar este plano.',
                    'plan_id' => $newPlan->id,
                ],
            ], 402);
        }

        // Paid → Free: cancel subscription and revert
        if (!$isCurrentFree && $isNewFree) {
            if ($workspace->stripe_subscription_id) {
                app(\App\Domain\Billing\Services\StripeService::class)
                    ->cancelSubscription($workspace);
            }

            $workspace->update([
                'plan_id' => $newPlan->id,
                'subscription_status' => 'none',
                'subscription_ends_at' => null,
            ]);

            return response()->json([
                'data' => $workspace->fresh()->load('plan'),
                'message' => 'Plano alterado para Free. Assinatura cancelada.',
            ]);
        }

        // Paid → Paid: change plan via Stripe (pro-rating)
        if ($workspace->stripe_subscription_id) {
            return response()->json([
                'error' => [
                    'code' => 'USE_BILLING_CHANGE_PLAN',
                    'message' => 'Use a rota de billing para mudar entre planos pagos.',
                ],
            ], 422);
        }

        // Fallback: no subscription yet, require payment
        return response()->json([
            'error' => [
                'code' => 'PAYMENT_REQUIRED',
                'message' => 'Pagamento necessario. Use o checkout para assinar este plano.',
                'plan_id' => $newPlan->id,
            ],
        ], 402);
    }
}
