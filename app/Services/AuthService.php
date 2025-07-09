<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\Debater;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthService
{
    public function createUser($request)
    {
        DB::beginTransaction();
        try {
            Log::debug('Create User Request Data: ', $request->all());
            $user = User::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
                'profile_picture_url' => $request->get('profile_picture_url'),
                'pp_public_id' => $request->get('pp_public_id'),
                'birth_date' => $request->get('birth_date'),
                'mobile_number' => $request->get('mobile_number'),
                'governorate' => $request->get('governorate'),
                'faculty_id' => $request->get('faculty_id'),
            ]);

            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            throw $t;
        }
    }

    public function createCoach($request)
    {
        DB::beginTransaction();
        try {
            $user = $this->createUser($request);
            Coach::create([
                'user_id' => $user->id,
            ]);
            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            throw $t;
        }
    }

    public function createDebater($request)
    {
        DB::beginTransaction();
        try {
            $user = $this->createUser($request);
            $coachId = $request->get('coach_id');
            if ($coachId) {
                $coach = Coach::find($coachId);
                if (!$coach) {
                    throw new \Exception("Invalid coach_id: $coachId. No corresponding coach found.");
                }
            }
            Debater::create([
                'user_id' => $user->id,
                'coach_id' => $coachId,
            ]);
            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            throw $t;
        }
    }
    public function createJudge($request)
    {
        DB::beginTransaction();
        try {
            $user = $this->createUser($request);
            Judge::create([
                'user_id' => $user->id,
            ]);
            DB::commit();
            return $user;
        } catch (Throwable $t) {
            DB::rollBack();
            throw $t;
        }
    }

    public function patch($request)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($request->get("user_id"));

            if ($request->has('governorate')) {
                $user->governorate = $request->get('governorate');
            }

            if ($request->has('profile_picture_url') && $request->has('pp_public_id')) {
                $user->profile_picture_url = $request->get('profile_picture_url');
                $authcontroller = app(\App\Http\Controllers\AuthController::class);
                $authcontroller->destroyImage($user->pp_public_id);
                $user->pp_public_id = $request->get('pp_public_id');
            }

            if ($request->has('mobile_number')) {
                $user->mobile_number = $request->get('mobile_number');
            }

            $user->touch();
            $user->save();

            DB::commit();
            return true;
        } catch (Throwable $t) {
            DB::rollBack();
            throw $t;
        }
    }
}
