<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withCommands([
        \App\Domain\Billing\Commands\SyncStripePlansCommand::class,
        \App\Domain\Board\Commands\AutoUnarchiveCardsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtAuthenticate::class,
            'workspace' => \App\Http\Middleware\WorkspaceMiddleware::class,
            'workspace.role' => \App\Http\Middleware\WorkspaceRoleMiddleware::class,
            'api_key' => \App\Http\Middleware\ApiKeyAuthenticate::class,
        ]);

        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':global',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => ['message' => 'Resource not found.']], 404);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => ['message' => 'Unauthenticated.']], 401);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() && !$e instanceof ValidationException) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message = $status === 500 ? 'Internal server error.' : $e->getMessage();

                return response()->json(['error' => ['message' => $message]], $status);
            }
        });
    })->create();
