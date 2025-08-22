<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\JSONResponseTrait;

class CheckAdminRole
{
    use JSONResponseTrait;

    public function handle(Request $request, Closure $next)
    {
        $guards = ['user', 'debater', 'judge', 'coach', 'admin'];
        foreach ($guards as $guard) {
            if ($user = Auth::guard($guard)->user()) {
                if ($user->role === 'admin') {
                    return $next($request);
                }
            }
        }

        return $this->errorResponse('Unauthorized', '', ['User is not an admin'], 403);
    }
}
