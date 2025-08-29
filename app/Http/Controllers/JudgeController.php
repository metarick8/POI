<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use Illuminate\Http\Request;
use App\Models\Rate;
use App\Models\Judge;
use Illuminate\Support\Facades\Log;

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

        Log::info('getting debate info', [
            'debate_id' => $debate->id,
            'chair_judge_id' => $debate->chair_judge_id,
        ]);

        $ratesQuery = Rate::select('rates.*')
            ->join('participants_debaters', 'rates.participant_debater_id', '=', 'participants_debaters.id')
            ->where('participants_debaters.debate_id', $debate->id)
            ->where('rates.judge_id', function ($query) use ($debate) {
                $query->select('chair_judge_id')
                      ->from('debates')
                      ->where('id', $debate->id)
                      ->limit(1);
            });

        Log::debug('SQL rates', [
            'sql' => $ratesQuery->toSql(),
            'bindings' => $ratesQuery->getBindings(),
        ]);

        $rates = $ratesQuery
            ->with('participantDebater.user')
            ->get();

        Log::info('counting query results', ['count' => $rates->count(), 'raw' => $rates->toArray()]);

        if ($rates->isEmpty()) {
            Log::warning('no debate rates found', [
                'debate_id' => $debate->id,
                'chair_judge_id' => $debate->chair_judge_id,
            ]);
        }

        $result = $rates->map(function ($rate) {
            $participant = $rate->participantDebater;
            $user = $participant ? $participant->debaterUser : null;

            return [
                'debater' => [
                    'id' => $rate->participant_debater_id ?? null,
                    'name' => $user ? $user->first_name ?? 'unknown' : 'unknown',
                    'email' => $user ? $user->email ?? null : null
                ],
                'rate' => $rate->rate,
                'opinion' => $rate->opinion
            ];
        })->all();

        return response()->json([
            'debate_id' => $debate->id,
            'rates' => $result
        ]);
    }

}
