<?php

namespace App\Domain\Sso\Controllers;

use App\Domain\Sso\Models\SamlConfig;
use App\Domain\Sso\Services\SamlService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SsoConfigController extends Controller
{
    public function __construct(
        private SamlService $samlService
    ) {}

    public function show(Request $request)
    {
        $workspace = $request->workspace;

        $config = SamlConfig::where('workspace_id', $workspace->id)->first();

        if (!$config) {
            return response()->json(['data' => null]);
        }

        // Include cert presence but not the actual cert for display
        $data = $config->toArray();
        $data['has_certificate'] = !empty($config->idp_x509_cert);
        $data['idp_x509_cert'] = $config->idp_x509_cert; // Include for editing

        // Include SP URLs for configuration
        $appUrl = config('app.url');
        $data['sp_metadata_url'] = "{$appUrl}/api/v1/sso/saml/{$workspace->id}/metadata";
        $data['sp_acs_url'] = "{$appUrl}/api/v1/sso/saml/{$workspace->id}/acs";
        $data['sp_entity_id'] = "{$appUrl}/api/v1/sso/saml/{$workspace->id}/metadata";

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $workspace = $request->workspace;

        // Check if workspace plan has SSO feature
        if (!$workspace->plan || !$workspace->plan->hasFeature('sso')) {
            return response()->json([
                'error' => ['message' => 'SSO nao esta disponivel no seu plano atual. Faca upgrade para o plano Business.'],
            ], 403);
        }

        $validated = $request->validate([
            'idp_entity_id' => 'required|string|max:500',
            'idp_sso_url' => 'required|url|max:1000',
            'idp_slo_url' => 'nullable|url|max:1000',
            'idp_x509_cert' => 'required|string',
            'domain' => 'required|string|max:255',
            'sso_enforced' => 'boolean',
            'is_active' => 'boolean',
            'attribute_mapping' => 'nullable|array',
        ]);

        // Validate domain uniqueness (excluding current workspace)
        $existingDomain = SamlConfig::where('domain', $validated['domain'])
            ->where('workspace_id', '!=', $workspace->id)
            ->exists();

        if ($existingDomain) {
            return response()->json([
                'error' => ['message' => 'Este dominio ja esta configurado em outro workspace.'],
            ], 422);
        }

        $config = SamlConfig::updateOrCreate(
            ['workspace_id' => $workspace->id],
            $validated
        );

        return response()->json(['data' => $config]);
    }

    public function testConnection(Request $request)
    {
        $workspace = $request->workspace;

        $config = SamlConfig::where('workspace_id', $workspace->id)->first();

        if (!$config) {
            return response()->json([
                'error' => ['message' => 'Nenhuma configuracao SSO encontrada.'],
            ], 404);
        }

        try {
            // Validate the settings can be constructed
            $this->samlService->getMetadataXml($config);

            return response()->json([
                'data' => [
                    'status' => 'ok',
                    'message' => 'Configuracao SAML valida. O SP metadata foi gerado com sucesso.',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'data' => [
                    'status' => 'error',
                    'message' => 'Erro na configuracao: ' . $e->getMessage(),
                ],
            ]);
        }
    }

    public function destroy(Request $request)
    {
        $workspace = $request->workspace;

        SamlConfig::where('workspace_id', $workspace->id)->delete();

        return response()->json(['message' => 'Configuracao SSO removida.']);
    }

    public function discover(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');
        $domain = substr(strrchr($email, '@'), 1);

        $config = SamlConfig::where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return response()->json(['data' => ['sso_available' => false]]);
        }

        return response()->json([
            'data' => [
                'sso_available' => true,
                'sso_enforced' => $config->sso_enforced,
                'workspace_id' => $config->workspace_id,
                'login_url' => config('app.url') . "/api/v1/sso/saml/{$config->workspace_id}/login",
            ],
        ]);
    }
}
