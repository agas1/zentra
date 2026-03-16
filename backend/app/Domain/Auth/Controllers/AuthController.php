<?php

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Requests\RegisterRequest;
use App\Domain\Board\Services\BoardService;
use App\Domain\Plan\Models\Plan;
use App\Domain\Sso\Models\SamlConfig;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // Check if email domain requires SSO
        $domain = substr(strrchr($credentials['email'], '@'), 1);
        $ssoConfig = SamlConfig::where('domain', $domain)
            ->where('is_active', true)
            ->where('sso_enforced', true)
            ->first();

        if ($ssoConfig) {
            $loginUrl = config('app.url') . "/api/v1/sso/saml/{$ssoConfig->workspace_id}/login";
            return response()->json([
                'error' => [
                    'message' => 'Este dominio exige login via SSO.',
                    'code' => 'SSO_REQUIRED',
                    'sso_login_url' => $loginUrl,
                ],
            ], 403);
        }

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => ['message' => 'Invalid credentials']], 401);
        }

        $user = auth()->user();

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $user->load('workspaces'),
            ],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $plan = Plan::where('is_default', true)->firstOrFail();

        $persona = $request->persona ?? 'studio';

        $workspace = Workspace::create([
            'name' => $request->workspace_name,
            'slug' => Str::slug($request->workspace_name) . '-' . Str::random(6),
            'plan_id' => $plan->id,
            'owner_id' => $user->id,
            'is_active' => true,
            'persona' => $persona,
        ]);

        $workspace->members()->attach($user->id, ['role' => 'owner']);

        // Create default board based on persona
        $boardService = new BoardService();
        $template = $boardService->getTemplateForPersona($persona);
        $boardService->createBoard([
            'name' => $boardService->getDefaultBoardName($persona),
            'template' => $template,
        ], $user, $workspace);

        // Create default template based on persona
        $boardService->createDefaultTemplate($workspace, $persona, $user->id);

        $token = auth()->login($user);

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $user->load('workspaces'),
                'workspace' => $workspace->load('plan'),
            ],
        ], 201);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        try {
            $token = auth()->refresh();
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => ['message' => 'Token invalid or refresh expired']], 401);
        }

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
            ],
        ]);
    }

    public function me()
    {
        return response()->json([
            'data' => auth()->user()->load('workspaces'),
        ]);
    }

    public function googleRedirect()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json(['data' => ['url' => $url]]);
    }

    public function googleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Check if email domain requires SSO
        $domain = substr(strrchr($googleUser->getEmail(), '@'), 1);
        $ssoConfig = SamlConfig::where('domain', $domain)
            ->where('is_active', true)
            ->where('sso_enforced', true)
            ->first();

        if ($ssoConfig) {
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            return redirect($frontendUrl . '/login?sso_error=' . urlencode('Este dominio exige login via SSO.'));
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;

            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);
        }

        if ($isNewUser) {
            $plan = Plan::where('is_default', true)->firstOrFail();

            $workspace = Workspace::create([
                'name' => $user->name . "'s Workspace",
                'slug' => Str::slug($user->name) . '-' . Str::random(6),
                'plan_id' => $plan->id,
                'owner_id' => $user->id,
                'is_active' => true,
                'persona' => 'studio',
            ]);

            $workspace->members()->attach($user->id, ['role' => 'owner']);

            // Create default board (studio template, will be updated after onboarding)
            $boardService = new BoardService();
            $boardService->createBoard([
                'name' => 'Meu Primeiro Quadro',
                'template' => 'design_pipeline',
            ], $user, $workspace);

            // Create default template (studio, will be replaced after onboarding persona selection)
            $boardService->createDefaultTemplate($workspace, 'studio', $user->id);
        }

        $token = auth()->login($user);

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        $callbackUrl = $frontendUrl . '/auth/callback?token=' . $token;
        if ($isNewUser) {
            $callbackUrl .= '&needs_onboarding=1';
        }

        return redirect($callbackUrl);
    }
}
