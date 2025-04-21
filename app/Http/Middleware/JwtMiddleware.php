<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    protected string $guard;
    
    public function __construct(string $guard = 'user')
    {
        $this->guard = $guard;
    }

    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse($this->guard);

        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalid or expired'], 403);
        }

        return $next($request);
    }
}
