<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['error' => ['message' => 'User not found']], 401);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => ['message' => 'Token expired']], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => ['message' => 'Token invalid']], 401);
        }

        return $next($request);
    }
}
