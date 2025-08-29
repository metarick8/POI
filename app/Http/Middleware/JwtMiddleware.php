<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('JwtMiddleware invoked', ['token' => $request->bearerToken()]);

        // List all possible guards
        $guards = ['user', 'debater', 'judge', 'coach'];

        foreach ($guards as $guard) {
            Log::debug('Attempting authentication with guard', ['guard' => $guard]);
            Auth::shouldUse($guard);

            try {
                // Get the token from the request
                $token = $request->bearerToken();
                if (!$token) {
                    Log::error('No token provided for guard', ['guard' => $guard]);
                    continue;
                }

                // Manually set the token for JWTAuth
                JWTAuth::setToken($token);

                // Get the payload to log the sub claim
                $payload = JWTAuth::parseToken()->getPayload();
                Log::debug('Token payload for guard', [
                    'guard' => $guard,
                    'payload' => $payload->toArray()
                ]);

                // Attempt to authenticate using the guard's provider
                $user = Auth::guard($guard)->user();
                if (!$user) {
                    // If no user is authenticated, try to authenticate via JWTAuth
                    $user = JWTAuth::parseToken()->authenticate();
                }

                if ($user) {
                    Log::debug('Authenticated with guard', [
                        'guard' => $guard,
                        'user_id' => $user->id,
                        'email' => $user->email ?? 'N/A'
                    ]);
                    Auth::guard($guard)->setUser($user);
                    return $next($request);
                }
            } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
                Log::error('Token expired for guard', ['guard' => $guard, 'error' => $e->getMessage()]);
                return response()->json(['error' => 'Token expired'], 401);
            } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                Log::error('Token invalid for guard', ['guard' => $guard, 'error' => $e->getMessage()]);
                continue;
            } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                Log::error('JWT error for guard', ['guard' => $guard, 'error' => $e->getMessage()]);
                continue;
            }
        }

        Log::error('Authentication failed for all guards', ['token' => $request->bearerToken()]);
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
