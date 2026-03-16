<?php

namespace App\Domain\Sso\Services;

use App\Domain\Sso\Models\SamlConfig;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use OneLogin\Saml2\Auth as SamlAuth;
use OneLogin\Saml2\Settings as SamlSettings;
use OneLogin\Saml2\Utils as SamlUtils;

class SamlService
{
    public function getSettings(SamlConfig $config): array
    {
        $appUrl = config('app.url');
        $workspaceId = $config->workspace_id;

        return [
            'strict' => true,
            'debug' => config('app.debug'),
            'sp' => [
                'entityId' => "{$appUrl}/api/v1/sso/saml/{$workspaceId}/metadata",
                'assertionConsumerService' => [
                    'url' => "{$appUrl}/api/v1/sso/saml/{$workspaceId}/acs",
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            ],
            'idp' => [
                'entityId' => $config->idp_entity_id,
                'singleSignOnService' => [
                    'url' => $config->idp_sso_url,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'singleLogoutService' => $config->idp_slo_url ? [
                    'url' => $config->idp_slo_url,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ] : [],
                'x509cert' => $config->idp_x509_cert,
            ],
            'security' => [
                'authnRequestsSigned' => false,
                'wantAssertionsSigned' => true,
                'wantNameIdEncrypted' => false,
            ],
        ];
    }

    public function initiateLogin(SamlConfig $config): string
    {
        $auth = new SamlAuth($this->getSettings($config));
        return $auth->login(null, [], false, false, true);
    }

    public function processResponse(SamlConfig $config, string $samlResponse): array
    {
        $settings = new SamlSettings($this->getSettings($config));
        $auth = new SamlAuth($this->getSettings($config));

        // Set the SAMLResponse in POST
        $_POST['SAMLResponse'] = $samlResponse;

        $auth->processResponse();

        $errors = $auth->getErrors();
        if (!empty($errors)) {
            throw new \RuntimeException('SAML Error: ' . implode(', ', $errors) . '. Reason: ' . $auth->getLastErrorReason());
        }

        if (!$auth->isAuthenticated()) {
            throw new \RuntimeException('SAML Authentication failed.');
        }

        $attributes = $auth->getAttributes();
        $nameId = $auth->getNameId();
        $mapping = $config->getAttributeMap();

        return [
            'email' => $this->extractAttribute($attributes, $mapping['email'] ?? 'email', $nameId),
            'first_name' => $this->extractAttribute($attributes, $mapping['first_name'] ?? 'firstName'),
            'last_name' => $this->extractAttribute($attributes, $mapping['last_name'] ?? 'lastName'),
            'name_id' => $nameId,
        ];
    }

    public function getMetadataXml(SamlConfig $config): string
    {
        $settings = new SamlSettings($this->getSettings($config), true);
        $metadata = $settings->getSPMetadata();

        $errors = $settings->validateMetadata($metadata);
        if (!empty($errors)) {
            throw new \RuntimeException('SP Metadata validation error: ' . implode(', ', $errors));
        }

        return $metadata;
    }

    public function findOrCreateUser(Workspace $workspace, array $attributes): User
    {
        $email = $attributes['email'];
        if (!$email) {
            throw new \RuntimeException('SAML response does not contain an email address.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Check member limit
            $plan = $workspace->plan;
            if ($plan && $plan->max_members !== -1) {
                $currentMembers = $workspace->members()->count();
                if ($currentMembers >= $plan->max_members) {
                    throw new \RuntimeException('Limite de membros do workspace atingido. Contate o administrador.');
                }
            }

            // JIT provisioning: create user
            $name = trim(($attributes['first_name'] ?? '') . ' ' . ($attributes['last_name'] ?? ''));
            if (empty($name)) {
                $name = explode('@', $email)[0];
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
            ]);
        }

        // Ensure user is member of workspace
        if (!$workspace->members()->where('user_id', $user->id)->exists()) {
            // Check member limit for existing users
            $plan = $workspace->plan;
            if ($plan && $plan->max_members !== -1) {
                $currentMembers = $workspace->members()->count();
                if ($currentMembers >= $plan->max_members) {
                    throw new \RuntimeException('Limite de membros do workspace atingido. Contate o administrador.');
                }
            }

            $workspace->members()->attach($user->id, ['role' => 'member']);
        }

        return $user;
    }

    public function generateJwtForUser(User $user): string
    {
        return auth()->login($user);
    }

    private function extractAttribute(array $attributes, string $key, ?string $fallback = null): ?string
    {
        // Try direct key match
        if (isset($attributes[$key])) {
            return is_array($attributes[$key]) ? ($attributes[$key][0] ?? $fallback) : $attributes[$key];
        }

        // Try case-insensitive search
        foreach ($attributes as $attrKey => $attrValue) {
            if (strcasecmp($attrKey, $key) === 0) {
                return is_array($attrValue) ? ($attrValue[0] ?? $fallback) : $attrValue;
            }
        }

        return $fallback;
    }
}
