<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParticipateInDebateRequest;
use App\JSONResponseTrait;
use App\Models\Debate;
use App\Models\Debater;
use App\Models\ParticipantsDebater;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DebaterController extends Controller
{
    use JSONResponseTrait;

    public function participate(ParticipateInDebateRequest $request, Debate $debate) {
        $user = JWTAuth::parseToken()->authenticate();
        $participation = ParticipantsDebater::create([
            'debate_id'=>$debate->id,
            'debater_id'=>Debater::firstWhere('user_id',$user->id)->id,
            'rank' => 0
        ]);
        return $this->successResponse('Participation recorded successfully!', $participation);

    }

}
