<?php

namespace App\Policies;

use App\Models\Debate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicationPolicy
{
    use HandlesAuthorization;

    public function applyDebater(User $user, Debate $debate): bool
    {
        if ($debate->status !== 'announced') {
            return false;
        }

        $hasApplied = $debate->applications()->where('user_id', $user->id)->exists();
        if ($hasApplied) {
            return false;
        }

        return $debate->debater_count < 8;
    }

    public function applyJudge(User $user, Debate $debate, string $judgeType): bool
    {

        if (!in_array($debate->status, ['announced', 'applied']))
            return false;


        $hasApplied = $debate->applications()->where('user_id', $user->id)->exists();
        if ($hasApplied)
            return false;

        if ($judgeType === 'chair')
            return $debate->chair_judge_id === null && $debate->judge_count < 3;

            return $debate->judge_count < 3;
    }

    public function manageApplications(User $user): bool
    {
        return $user->role === 'admin';
    }
}
