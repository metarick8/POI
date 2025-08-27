<?php

namespace App\Services\Debate;

use App\Models\Application;
use App\Models\Debate;
use App\Models\ParticipantsDebater;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamService
{
    public function indexTeams(int $debateId)
    {
        $applications = Application::where('debate_id', $debateId)
            ->where('status', 'approved')
            ->get();

        return $applications;
    }
    public function assignTeams(Debate $debate, array $teams)
    {
        try {
            return DB::transaction(function () use ($teams, $debate) {
                // Delete existing team assignments
                ParticipantsDebater::where('debate_id', $debate->id)->delete();

                $assignedTeams = [];

                foreach ($teams as $teamIndex => $debaters) {
                    $teamNumber = $teamIndex + 1;

                    foreach ($debaters as $debaterId) {
                        $created = ParticipantsDebater::create([
                            'debate_id' => $debate->id,
                            'team_number' => $teamNumber,
                            'debater_id' => $debaterId,
                        ]);

                        if (!$created) {
                            Log::error('Failed to create team assignment', [
                                'debate_id' => $debate->id,
                                'team_number' => $teamNumber,
                                'debater_id' => $debaterId,
                            ]);
                            return 'Failed to assign team';
                        }

                        $assignedTeams[] = [
                            'team_number' => $teamNumber,
                            'debater_id' => $debaterId,
                        ];
                    }
                }

                // Update debate status to teamsConfirmed
                $updated = $debate->update([
                    'status' => 'teamsConfirmed',
                    'updated_at' => now(),
                ]);

                if (!$updated) {
                    Log::error('Failed to update debate status', [
                        'debate_id' => $debate->id,
                        'status' => 'debatePreperation',
                    ]);
                    return 'Failed to update debate status';
                }

                Log::debug('Team assignments saved and debate status updated', [
                    'debate_id' => $debate->id,
                    'teams' => $assignedTeams,
                    'status' => 'debatePreperation',
                ]);

                return $assignedTeams;
            });
        } catch (\Exception $e) {
            Log::error('Transaction failed for team assignment', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage(),
            ]);
            return 'Transaction failed: ' . $e->getMessage();
        }
    }
}
