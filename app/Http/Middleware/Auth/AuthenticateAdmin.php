<?php

namespace App\Http\Middleware\Auth;

use App\Http\Middleware\JwtMiddleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateAdmin extends JwtMiddleware
{
       public function handle(Request $request, Closure $next): Response
    {
        Log::debug('AuthenticateDebater invoked', ['token' => $request->bearerToken()]);

        $guard = 'admin';
        Log::debug('Attempting authentication with guard', ['guard' => $guard]);
        Auth::shouldUse($guard);

        try {
            // Get the token from the request
            $token = $request->bearerToken();
            if (!$token) {
                Log::error('No token provided for debater guard');
                return response()->json(['error' => 'Token not provided'], 401);
            }

            // Manually set the token for JWTAuth
            JWTAuth::setToken($token);

            // Get the payload to log the sub claim
            $payload = JWTAuth::parseToken()->getPayload();
            Log::debug('Token payload for debater guard', [
                'guard' => $guard,
                'payload' => $payload->toArray()
            ]);

            // Attempt to authenticate using the debater guard
            $user = Auth::guard($guard)->user();
            if (!$user) {
                // If no user is authenticated, try to authenticate via JWTAuth
                $user = JWTAuth::parseToken()->authenticate();
            }

            if ($user) {
                Log::debug('Authenticated with debater guard', [
                    'guard' => $guard,
                    'user_id' => $user->id,
                    'email' => $user->email ?? 'N/A'
                ]);
                Auth::guard($guard)->setUser($user);
                return $next($request);
            }

            Log::error('Authentication failed for debater guard', ['token' => $token]);
            return response()->json(['error' => 'Unauthorized'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            Log::error('Token expired for debater guard', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            Log::error('Token invalid for debater guard', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            Log::error('JWT error for debater guard', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Token error'], 401);
        }
    }
}
