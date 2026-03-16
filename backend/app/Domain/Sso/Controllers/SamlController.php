<?php

namespace App\Domain\Sso\Controllers;

use App\Domain\Sso\Models\SamlConfig;
use App\Domain\Sso\Services\SamlService;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SamlController extends Controller
{
    public function __construct(
        private SamlService $samlService
    ) {}

    public function metadata(string $workspace)
    {
        $config = SamlConfig::where('workspace_id', $workspace)
            ->where('is_active', true)
            ->firstOrFail();

        $xml = $this->samlService->getMetadataXml($config);

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function login(string $workspace)
    {
        $config = SamlConfig::where('workspace_id', $workspace)
            ->where('is_active', true)
            ->firstOrFail();

        $redirectUrl = $this->samlService->initiateLogin($config);

        return redirect($redirectUrl);
    }

    public function acs(Request $request, string $workspace)
    {
        $config = SamlConfig::where('workspace_id', $workspace)
            ->where('is_active', true)
            ->firstOrFail();

        $workspaceModel = Workspace::findOrFail($workspace);

        try {
            $samlResponse = $request->input('SAMLResponse');
            if (!$samlResponse) {
                throw new \RuntimeException('SAMLResponse not found in request.');
            }

            $attributes = $this->samlService->processResponse($config, $samlResponse);
            $user = $this->samlService->findOrCreateUser($workspaceModel, $attributes);
            $token = $this->samlService->generateJwtForUser($user);

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

            return redirect("{$frontendUrl}/auth/sso-callback?token={$token}&workspace={$workspace}");
        } catch (\Throwable $e) {
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

            return redirect("{$frontendUrl}/login?sso_error=" . urlencode($e->getMessage()));
        }
    }
}
