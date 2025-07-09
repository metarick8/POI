<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// class DebateResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return[

//         ];
//     }
// }


class DebateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'debate_id' => $this->id,
            'start_date' => $this->start_date ?? null,
            'start_time' => $this->start_time ?? null,
            'type' => $this->type ?? null,
            'status' => $this->status,
            'filter' => $this->filter ?? null,
            'motion' => $this->when(
                in_array($this->status, ['announced', 'applied', 'ongoing']),
                fn () => $this->motion ? [
                    'motion_id' => $this->motion->id,
                    'title' => $this->motion->title ?? 'Motion not set',
                    'type' => $this->motion->type ?? 'Type not specified',
                ] : null
            ),
            'chair_judge' => $this->when(
                in_array($this->status, ['announced', 'applied', 'ongoing', 'finished']),
                fn () => $this->chairJudge ? [
                    'chair_judge_id' => $this->chairJudge->id,
                    'name' => $this->chairJudge->name ?? 'Judge not set',
                ] : null
            ),
            'applicants_count' => $this->when(
                $this->status === 'applied',
                fn () => $this->applicants_count ?? 0
            ),
            'details' => $this->when(
                $this->status === 'finished',
                fn () => [
                    'winner' => $this->winner ?? 'No winner declared',
                    'summary' => $this->summary ?? 'No summary available',
                ]
            ),
            'reason' => $this->when(
                in_array($this->status, ['cancelled', 'bugged']),
                fn () => $this->cancellation_reason ?? 'No reason provided'
            ),
        ];
    }
}


/*
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_date' => $this->start_date ?? null,
            'start_time' => $this->start_time ?? null,
            'type' => $this->type ?? null,
            'status' => $this->status,
            'filter' => $this->filter ?? null,
            'motion' => $this->when(
                in_array($this->status, ['announced', 'applied', 'ongoing']),
                fn () => $this->motion ? [
                    'id' => $this->motion->id,
                    'sentence' => $this->motion->sentence ?? 'Motion not set',
                ] : null
            ),
            'chair_judge' => $this->when(
                in_array($this->status, ['announced', 'applied', 'ongoing', 'finished']),
                fn () => $this->chairJudge && $this->chairJudge->user ? [
                    'id' => $this->chairJudge->id,
                    'name' => $this->chairJudge->user->name ?? 'Judge not set',
                ] : null
            ),
            'panelist_judges' => $this->when(
                in_array($this->status, ['announced', 'applied', 'ongoing', 'finished']),
                fn () => $this->panelistJudges->map(fn ($panelistJudge) => [
                    'id' => $panelistJudge->id,
                    'name' => $panelistJudge->judge && $panelistJudge->judge->user
                        ? $panelistJudge->judge->user->name ?? 'Panelist judge not set'
                        : 'Panelist judge not set',
                ])->all()
            ),
            'debaters' => $this->when(
                in_array($this->status, ['applied', 'ongoing', 'finished']),
                fn () => $this->participantsDebaters->map(fn ($participant) => [
                    'debater_id' => $participant->debater_id,
                    'name' => $participant->debater && $participant->debater->user
                        ? $participant->debater->user->name ?? 'Debater not set'
                        : 'Debater not set',
                    'speaker_position' => $participant->speaker
                        ? ($participant->speaker->position ?? 'Position not set')
                        : 'Position not set',
                    'team_role' => $participant->speaker && $participant->speaker->team
                        ? ($participant->speaker->team->role ?? 'Team not set')
                        : 'Team not set',
                    'rank' => $participant->rank ?? null,
                ])->all()
            ),
            'applicants_count' => $this->when(
                $this->status === 'announced',
                fn () => $this->applicants_count ?? 0
            ),
            'debaters_count' => $this->when(
                in_array($this->status, ['applied', 'ongoing', 'finished']),
                fn () => $this->debaters_count ?? 0
            ),
*/
