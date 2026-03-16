<?php

namespace App\Domain\PowerUp\Controllers;

use App\Domain\PowerUp\Models\PowerUp;
use App\Domain\PowerUp\Services\PowerUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PowerUpController
{
    public function __construct(private PowerUpService $service) {}

    public function index(): JsonResponse
    {
        $powerUps = $this->service->listAvailable();
        $workspace = request()->workspace;
        $installed = $this->service->listInstalled($workspace);
        $installedSlugs = $installed->pluck('power_up_slug')->toArray();

        $data = $powerUps->map(function (PowerUp $pu) use ($installedSlugs, $installed) {
            $arr = $pu->toArray();
            $arr['is_installed'] = in_array($pu->slug, $installedSlugs);
            $wpu = $installed->firstWhere('power_up_slug', $pu->slug);
            $arr['config'] = $wpu?->config;
            $arr['connected_by'] = $wpu?->connectedBy;
            $arr['connected_at'] = $wpu?->connected_at;
            return $arr;
        });

        return response()->json(['data' => $data]);
    }

    public function installed(): JsonResponse
    {
        $workspace = request()->workspace;
        $installed = $this->service->listInstalled($workspace);

        return response()->json(['data' => $installed]);
    }

    public function install(string $slug): JsonResponse
    {
        $workspace = request()->workspace;
        $plan = $workspace->plan;

        if (!$plan->hasFeature('power_ups')) {
            return response()->json([
                'error' => ['message' => 'Power-Ups estao disponiveis apenas nos planos Pro e Business. Faca upgrade para usar esta funcionalidade.'],
            ], 403);
        }

        $powerUp = PowerUp::where('slug', $slug)->where('is_active', true)->first();
        if (!$powerUp) {
            return response()->json(['error' => ['message' => 'Power-Up nao encontrado.']], 404);
        }

        $wpu = $this->service->install($workspace, $slug, auth()->user());

        return response()->json(['data' => $wpu->load(['powerUp', 'connectedBy:id,name'])], 201);
    }

    public function uninstall(string $slug): JsonResponse
    {
        $workspace = request()->workspace;
        $this->service->uninstall($workspace, $slug);

        return response()->json(null, 204);
    }

    public function updateConfig(Request $request, string $slug): JsonResponse
    {
        $workspace = request()->workspace;

        $data = $request->validate([
            'config' => 'required|array',
        ]);

        $wpu = $this->service->updateConfig($workspace, $slug, $data['config']);

        return response()->json(['data' => $wpu]);
    }
}
