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

        // Handle debaters based on status
        $debatersData = $this->status === 'teamsConfirmed'
            ? [
                'teams' => $this->participantsDebaters
                    ->groupBy('team_number')
                    ->map(function ($group, $teamNumber) use ($mapParticipantDebater) {
                        return [
                            'team_number' => $teamNumber,
                            'debaters' => $group->map($mapParticipantDebater)->take(2)->all(),
                        ];
                    })
                    ->values()
                    ->all(),
            ]
            : [
                'debaters' => $this->participantsDebaters->isNotEmpty()
                    ? $this->participantsDebaters->map($mapParticipantDebater)->all()
                    : $this->debaters->map($mapDebater)->all(),
            ];

        return [
            'debate_id' => $this->id ?? null,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'start_time' => $this->start_time ?? null,
            'type' => $this->type ?? null,
            'status' => $this->status ?? null,
            'filter' => $this->filter ?? null,
            'motion' => $this->motion ? [
                'motion_id' => $this->motion->id ?? null,
                'title' => $this->motion->title ?? null,
                'type' => $this->motion->type ?? null,
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
            ...$debatersData,
            'applicants_count' => $this->applicants_count ?? 0,
            'debaters_count' => $this->debater_count ?? 0,
            'judge_count' => $this->judge_count ?? 0,
            // 'meeting_id' => $this->meeting_id ?? null,
            // 'start_url' => $this->start_url ?? null,
            // 'join_url' => $this->join_url ?? null,
            // 'password' => $this->password ?? null,
            // 'recording_type' => $this->recording_type ?? null,
            // 'zoom_recording_url' => $this->zoom_recording_url ?? null,
            // 'cloudinary_recording_id' => $this->cloudinary_recording_id ?? null,
            // 'cloudinary_recording_url' => $this->cloudinary_recording_url ?? null,
            // 'recording_uploaded_at' => $this->recording_uploaded_at ? $this->recording_uploaded_at->toDateTimeString() : null,
            // 'final_ranks' => $this->final_ranks ?? null,
            // 'winner' => $this->winner ?? null,
            // 'summary' => $this->summary ?? null,
            // 'cancellation_reason' => $this->cancellation_reason ?? null,
            // 'is_able_to_apply' => $this->isAbleToApply ?? false,
            // 'has_recording' => $this->hasRecording() ?? false,
            // 'is_ready_for_preparation' => $this->isReadyForPreparation() ?? false,
            // 'can_start_zoom_meeting' => $this->canStartZoomMeeting() ?? false,
        ];
    }
}
