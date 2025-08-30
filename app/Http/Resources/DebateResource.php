<?php

namespace App\Http\Resources;

use App\Models\Debater;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Base debater mapping function for ParticipantsDebater
        $mapParticipantDebater = function ($participant) {
            return [
                'debater_id' => $participant->debater_id ?? null,
                'name' => $participant->debater && $participant->debater->user
                    ? trim($participant->debater->user->first_name . ' ' . $participant->debater->user->last_name) ?: null
                    : null,
                'speaker_position' => $participant->speaker
                    ? ($participant->speaker->position ?? null)
                    : null,
                'team_role' => $participant->speaker && $participant->speaker->team
                    ? ($participant->speaker->team->role ?? null)
                    : null,
                'rank' => $participant->rank ?? null,
                'team_number' => $participant->team_number ?? null,
            ];
        };

        // Fallback mapping for debaters relationship (from applications)
        $mapDebater = function ($debater) {
            $debaterModel = Debater::where('user_id', $debater->id)->first();
            return [
                'debater_id' => $debaterModel ? $debaterModel->id : null,
                'name' => $debater->first_name && $debater->last_name
                    ? trim($debater->first_name . ' ' . $debater->last_name) ?: null
                    : null,
                'speaker_position' => null,
                'team_role' => null,
                'rank' => null,
                'team_number' => null,
            ];
        };

        // Always prepare the debaters array (used for both teams and debaters sections)
        $debatersArray = $this->participantsDebaters->isNotEmpty()
            ? $this->participantsDebaters->map($mapParticipantDebater)->all()
            : $this->debaters->map($mapDebater)->all();

        // Determine teams based on status
        $teamsData = $this->status === 'teamsConfirmed'
            ? $this->participantsDebaters
            ->groupBy('team_number')
            ->map(function ($group, $teamNumber) use ($mapParticipantDebater) {
                return [
                    'team_number' => $teamNumber,
                    'debaters' => $group->map($mapParticipantDebater)->take(2)->all(),
                ];
            })
            ->values()
            ->all()
            : null;

        return [
            'debate_id' => $this->id ?? null,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'start_time' => $this->start_time ?? null,
            'type' => $this->type ?? null,
            'status' => $this->status ?? null,
            'filter' => $this->filter ?? null,
            'motion' => $this->motion ? [
                'motion_id' => $this->motion->id ?? null,
                'title' => $this->motion->sentence ?? null,
                'type' => $this->motion->sub_classifications ?? null,
            ] : null,
            'chair_judge' => $this->chairJudge && $this->chairJudge->user ? [
                'chair_judge_id' => $this->chairJudge->id ?? null,
                'name' => trim($this->chairJudge->user->first_name . ' ' . $this->chairJudge->user->last_name) ?: null,
            ] : null,
            'panelist_judges' => $this->panelistJudges->map(function ($panelistJudge) {
                return [
                    'id' => $panelistJudge->id ?? null,
                    'name' => $panelistJudge->judge && $panelistJudge->judge->user
                        ? trim($panelistJudge->judge->user->first_name . ' ' . $panelistJudge->judge->user->last_name) ?: null
                        : null,
                ];
            })->all(),
            'teams' => $teamsData,
            'debaters' => $debatersArray,
            'applicants_count' => $this->applicants_count ?? 0,
            'debaters_count' => $this->debater_count ?? 0,
            'judge_count' => $this->judge_count ?? 0,
        ];
    }
}
