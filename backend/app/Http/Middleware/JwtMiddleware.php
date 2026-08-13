<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token is required'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        return $next($request);
    }
}
