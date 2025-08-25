<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckBannedUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('user')->user();

        if ($user && $user->isBanned()) {
            Log::warning('Banned user attempted access', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return response()->json([
                'message' => 'Your account is banned',
                'errors' => ['You are not allowed to access this resource'],
            ], 403);
        }

        return $next($request);
    }
}
