<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\Debater;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthService
{
    public function createUser($request)
    {
        DB::beginTransaction();
        $user = null;
        try {
            $user = User::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
                'profile_picture_url' => $request->get('profile_picture_url'),
                'pp_public_id' => $request->get('public_id'),
            ]);
            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }

    public function createCoach($request)
    {
        DB::beginTransaction();
        $user = null;
        try {
            $user = $this->createUser($request);
            Coach::create([
                'user_id' => $user->id,
            ]);
            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }

    public function createDebater($request)
    {
        DB::beginTransaction();
        $user = null;;
        try {
            $user = $this->createUser($request);
            Debater::create([
                'user_id' => $user->id,
                'coach_id' => $request->get('coach_id'),
            ]);
            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }

    public function createJudge($request)
    {
        DB::beginTransaction();
        $user = null;
        try {
            $user = $this->createUser($request);
            Judge::create([
                'user_id' => $user->id,
            ]);
            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }
}
