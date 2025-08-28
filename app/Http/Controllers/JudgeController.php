<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use Illuminate\Http\Request;
use App\Models\Rate;
use App\Models\Judge;

class JudgeController extends Controller
{
    public function getJudgeRates($judgeId)
    {
        $judge = Judge::find($judgeId);
        if (!$judge) {
            return response()->json(['error' => 'Judge not found'], 404);
        }

        $rates = Rate::with('participantDebater.user')
            ->where('judge_id', $judgeId)
            ->get();

        return response()->json([
            'Judge' => $judge,
            'Rates' => $rates
        ]);
    }

    public function getJudgeRatesByDebate($debateId)
    {
        $debate = Debate::find($debateId);
        if (!$debate) {
            return response()->json(['error' => 'Debate not found'], 404);
        }

        
        $rates = Rate::select('rates.*')
            ->join('participants_debaters', 'rates.participant_debater_id', '=', 'participants_debaters.id')
            ->where('rates.judge_id', $debate->chair_judge_id)
            ->where('participants_debaters.debate_id', $debate->id)
            ->with('participantDebater.user')
            ->get();

        $result = $rates->map(function ($rate) {
            return [
                'debater' => [
                    'id' => $rate->participantDebater->user->id,
                    'name' => $rate->participantDebater->user->name,
                    'email' => $rate->participantDebater->user->email
                ],
                'rate' => $rate->rate,
                'opinion' => $rate->opinion
            ];
        });

        return response()->json([
            'debate_id' => $debate->id,
            'rates' => $result
        ]);
    }
}
