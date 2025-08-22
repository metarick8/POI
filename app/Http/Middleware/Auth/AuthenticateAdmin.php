<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next)
    {
        Log::debug('AuthenticateAdmin middleware processing', [
            'guard' => 'admin',
            'user' => Auth::guard('admin')->check() ? Auth::guard('admin')->user()->toArray() : null,
        ]);

        if (!Auth::guard('admin')->check()) {
            Log::warning('AuthenticateAdmin: Authentication failed');
            return response()->json([
                'success' => false,
                'message' => 'This user is not an admin',
                'data' => '',
                'errors' => ['Authentication required'],
            ], 403);
        }

        Log::debug('AuthenticateAdmin: Admin check passed');
        return $next($request);
    }
}
