<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\ParticipantsDebater;
use App\Models\Rate;
use Exception;

class RateService
{
    public function rateJudge($debaterId, $judgeId, $debateId, $rate, $opinion = null)
    {
        $debate = Debate::find($debateId);
        if (!$debate) {
            throw new Exception("Debate not found");
        }
        if ($debate->status !== 'finished') {
            throw new Exception("You cannot rate until the debate is finished");
        }
        if (!ParticipantsDebater::where('debate_id', $debateId)
            ->where('debater_id', $debaterId)->exists()) {
            throw new Exception("You are not authorized to rate this debate");
        }
        if ($debate->chair_judge_id !== $judgeId) {
            throw new Exception("You cannot rate this judge because they are not the chair judge of this debate");
        }
        return Rate::create([
            'participant_debater_id' => $debaterId,
            'judge_id'               => $judgeId,
            'rate'                   => $rate,
            'opinion'                => $opinion,
        ]);
    }
}
