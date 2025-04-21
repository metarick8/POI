<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Models\Debater;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class CoachController extends Controller
{
        public function profile()
        {
            $guard = $this->detectGuard();
            return $actor = Auth::guard($guard)->user();
        }
        private function detectGuard()
        {
            $guards = ['user', 'coach', 'judge', 'debater'];

            foreach ($guards as $guard) {
                try {
                    if (Auth::guard($guard)->user()) {
                        return $guard;
                    }
                } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                    continue;
                }
            }

            return 'user';
        }

}
