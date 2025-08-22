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
    // protected string $guard;

    // public function __construct(string $guard = 'user')
    // {
    //     $this->guard = $guard;
    // }

    public function handle(Request $request, Closure $next): Response
        {
    //     Auth::shouldUse($this->guard);

    //     try {
    //         if (!$user = JWTAuth::parseToken()->authenticate()) {
    //             return response()->json(['error' => 'Unauthorized'], 401);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Token invalid or expired'], 403);
    //     }

    //     return $next($request);

        Log::debug('JwtMiddleware invoked', ['token' => $request->bearerToken()]);

        // List all possible guards
        $guards = ['user', 'debater', 'judge', 'coach', 'admin'];

        foreach ($guards as $guard) {
            Auth::shouldUse($guard);
            try {
                $payload = JWTAuth::parseToken()->getPayload();
                Log::debug('Token payload for guard', [
                    'guard' => $guard,
                    'payload' => $payload->toArray()
                ]);
                if ($user = JWTAuth::parseToken()->authenticate()) {
                    Log::debug('Authenticated with guard', [
                        'guard' => $guard,
                        'user_id' => $user->id,
                        'email' => $user->email ?? 'N/A'
                    ]);
                    Auth::setUser($user);
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
