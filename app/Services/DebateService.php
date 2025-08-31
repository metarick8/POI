<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\ParticipantsDebater;
use App\Models\Speaker;
use App\Models\Team;
use App\Models\Participants_panelist_judge;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DebateService
{
    public function index(array $status = [], $perPage = 4)
    {
        $query = Debate::with(['motion', 'chairJudge', 'debaters']);

        if (!empty($status)) {
            $query->whereIn('status', $status);
        }
        return $query->get();
    }
    public function indexForAdmin()
    {
        return Debate::with(['motion', 'chairJudge'])->get();
    }
    public function create($request)
    {
        DB::beginTransaction();

        try {
            $debate = Debate::create([
                'start_date' => $request->get('date'),
                'start_time' => $request->get('time'),
                'type' => $request->get('type'),
                'status' => 'announced',
                'judge_count' => 0,
                'debater_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Debate created", [
                'debate_id' => $debate->id,
                'start_date' => $debate->start_date,
                'start_time' => $debate->start_time,
                'type' => $debate->type,
                'status' => $debate->status,
                'timezone' => now()->timezone->getName(),
            ]);

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to create debate: {$t->getMessage()}");
            return $t->getMessage();
        }
    }

    public function updateStatus(Debate $debate)
    {
        DB::beginTransaction();

        try {
            $debaterCount = $debate->participantsDebaters()->count();
            $judgeCount = $this->getJudgeCount($debate);
            $currentTime = Carbon::now();
            $startDateTime = Carbon::parse($debate->start_date . ' ' . $debate->start_time);

            Log::info("Updating debate {$debate->id}", [
                'status' => $debate->status,
                'debaters' => $debaterCount,
                'judges' => $judgeCount,
                'current_time' => $currentTime,
                'start_datetime' => $startDateTime,
            ]);

            // Check if debate should move to playersConfirmed
            if ($debate->status === 'announced' && $debaterCount === 8 && $judgeCount >= 1 && $judgeCount <= 3) {
                $debate->update(['status' => 'playersConfirmed', 'updated_at' => now()]);
                Log::info("Debate {$debate->id} updated to playersConfirmed");
            }

            // Check if debate should move to debatePreparation (15 minutes before start)
            elseif ($debate->status === 'teamsConfirmed' && $currentTime->diffInMinutes($startDateTime, false) <= 15) {
                $debate->update(['status' => 'debatePreparation', 'updated_at' => now()]);
                Log::info("Debate {$debate->id} updated to debatePreparation");
            }

            // Check if debate should move to ongoing
            elseif ($debate->status === 'debatePreparation' && $currentTime->gte($startDateTime)) {
                $debate->update(['status' => 'ongoing', 'updated_at' => now()]);
                Log::info("Debate {$debate->id} updated to ongoing");
            }

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to update debate {$debate->id}: {$t->getMessage()}");
            return $t->getMessage();
        }
    }

    /**
     * Get total judge count including chair and panelist judges
     */
    private function getJudgeCount(Debate $debate): int
    {
        $chairCount = $debate->chair_judge_id ? 1 : 0;
        $panelistCount = Participants_panelist_judge::where('debate_id', $debate->id)->count();

        return $chairCount + $panelistCount;
    }

    /**
     * Assign teams to players after players are confirmed
     */
    public function assignTeams(Debate $debate, array $teamAssignments): array
    {
        DB::beginTransaction();

        try {
            if ($debate->status !== 'playersConfirmed') {
                throw new Exception('Debate must be in playersConfirmed status to assign teams');
            }

            // Validate team assignments
            if (count($teamAssignments) !== 4) {
                throw new Exception('Must have exactly 4 teams');
            }

            foreach ($teamAssignments as $teamNumber => $debaterIds) {
                if (count($debaterIds) !== 2) {
                    throw new Exception("Team {$teamNumber} must have exactly 2 debaters");
                }

                // Update participants with team numbers
                foreach ($debaterIds as $debaterId) {
                    ParticipantsDebater::where('debate_id', $debate->id)
                        ->where('debater_id', $debaterId)
                        ->update(['team_number' => $teamNumber]);
                }
            }

            $debate->update(['status' => 'teamsConfirmed', 'updated_at' => now()]);

            Log::info("Teams assigned for debate {$debate->id}");

            DB::commit();
            return ['success' => true, 'message' => 'Teams assigned successfully'];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to assign teams for debate {$debate->id}: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add panelist judge to debate (max 2)
     */
    public function addPanelistJudge(Debate $debate, int $judgeId): array
    {
        DB::beginTransaction();

        try {
            // Check current panelist count
            $currentPanelistCount = Participants_panelist_judge::where('debate_id', $debate->id)->count();

            if ($currentPanelistCount >= 2) {
                throw new Exception('Maximum of 2 panelist judges allowed per debate');
            }

            // Check if judge is already assigned
            $exists = Participants_panelist_judge::where('debate_id', $debate->id)
                ->where('judge_id', $judgeId)
                ->exists();

            if ($exists) {
                throw new Exception('Judge is already assigned as panelist to this debate');
            }

            // Check if judge is chair judge
            if ($debate->chair_judge_id === $judgeId) {
                throw new Exception('Judge is already assigned as chair judge to this debate');
            }

            Participants_panelist_judge::create([
                'debate_id' => $debate->id,
                'judge_id' => $judgeId,
            ]);

            Log::info("Panelist judge added to debate", [
                'debate_id' => $debate->id,
                'judge_id' => $judgeId
            ]);

            DB::commit();
            return ['success' => true, 'message' => 'Panelist judge added successfully'];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to add panelist judge: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function cancel(Debate $debate, string $reason)
    {
        DB::beginTransaction();

        try {
            $debate->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'updated_at' => now(),
            ]);

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to cancel debate {$debate->id}: {$t->getMessage()}");
            return $t->getMessage();
        }
    }

    public function markAsBugged(Debate $debate, string $reason)
    {
        DB::beginTransaction();

        try {
            $debate->update([
                'status' => 'bugged',
                'cancellation_reason' => $reason,
                'updated_at' => now(),
            ]);

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to mark debate {$debate->id} as bugged: {$t->getMessage()}");
            return $t->getMessage();
        }
    }

    public function finish(Debate $debate, string $summary, array $ranks)
    {
        DB::beginTransaction();

        try {
            // Validate debate status
            if ($debate->status !== 'ongoing') {
                throw new Exception('Debate must be in ongoing status to finish');
            }

            // Validate team structure: 8 debaters in 4 teams
            $participants = $debate->participantsDebaters()->with('speaker.team')->get();
            if ($participants->count() !== 8) {
                throw new Exception('Debate must have exactly 8 debaters');
            }

            // Group debaters by team_number
            $teams = $participants->groupBy('team_number')->map(function ($teamParticipants) {
                return $teamParticipants->pluck('debater_id')->toArray();
            });

            if ($teams->count() !== 4) {
                throw new Exception('Debate must have exactly 4 teams');
            }

            // Validate each team has exactly 2 debaters
            foreach ($teams as $teamNumber => $debaters) {
                if (count($debaters) !== 2) {
                    throw new Exception("Team number {$teamNumber} must have exactly 2 debaters");
                }
            }

            // Validate ranks: must be exactly 4 unique ranks (1 to 4)
            if (count($ranks) !== 4) {
                throw new Exception('Exactly 4 team ranks must be provided');
            }

            $rankValues = array_values($ranks);
            $expectedRanks = [1, 2, 3, 4];
            if (array_diff($rankValues, $expectedRanks) || count(array_unique($rankValues)) !== 4) {
                throw new Exception('Ranks must be unique values from 1 to 4');
            }

            // Validate that all team numbers in ranks exist
            foreach (array_keys($ranks) as $teamNumber) {
                if (!isset($teams[$teamNumber])) {
                    throw new Exception("Invalid team number: {$teamNumber}");
                }
            }

            // Determine the winner (team with rank 1)
            $winningTeamNumber = array_search(1, $ranks);
            if ($winningTeamNumber === false) {
                throw new Exception('A team must be ranked 1 to determine the winner');
            }

            // Get the team role for the winning team
            $winningParticipant = $participants->firstWhere('team_number', $winningTeamNumber);
            $winningTeamRole = $winningParticipant->speaker->team->role ?? null;
            if (!in_array($winningTeamRole, ['OG', 'OO', 'CG', 'CO'])) {
                throw new Exception('Invalid winning team role');
            }

            // Update debate with winner, summary, and ranks
            $debate->update([
                'status' => 'finished',
                'winner' => $winningTeamRole,
                'summary' => $summary,
                'final_ranks' => $ranks, // Store as JSON
                'updated_at' => now(),
            ]);

            // Update individual debater ranks in participants_debaters
            foreach ($participants as $participant) {
                $teamNumber = $participant->team_number;
                $participant->update(['rank' => $ranks[$teamNumber]]);
            }

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to finish debate {$debate->id}: {$t->getMessage()}");
            return $t->getMessage();
        }
    }
    public function getPreparationStatus(Debate $debate, $judgeId)
    {
        $isShowButton = false;
        $isAbleToPrepare = false;

        // Check if debate is in teamsConfirmed status
        if ($debate->status === 'teamsConfirmed') {
            // isShowButton: true if the authenticated judge is the chair judge
            $isShowButton = $debate->chair_judge_id === $judgeId;

            // isAbleToPrepare: true if current time is within 30 minutes of debate start
            $debateDateTime = \Carbon\Carbon::parse($debate->start_date->toDateString() . ' ' . $debate->start_time);
            $now = now();
            $isAbleToPrepare = $now->diffInMinutes($debateDateTime, false) <= 30 && $now->lessThanOrEqualTo($debateDateTime);
        }

        return [
            'isShowButton' => $isShowButton,
            'isAbleToPrepare' => $isAbleToPrepare,
        ];
    }

    /**
     * Submit debate results (for chair judge)
     */
    public function submitResults(Debate $debate, array $data): array
    {
        DB::beginTransaction();

        try {
            if ($debate->status !== 'ongoing') {
                throw new Exception('Can only submit results for ongoing debates');
            }

            $debate->update([
                'status' => 'finished',
                'winner' => $data['winner'],
                'summary' => $data['summary'],
                'final_ranks' => $data['ranks'], // JSON field with team rankings
                'updated_at' => now(),
            ]);

            // Update participant ranks based on team rankings
            if (isset($data['ranks'])) {
                foreach ($data['ranks'] as $teamNumber => $rank) {
                    ParticipantsDebater::where('debate_id', $debate->id)
                        ->where('team_number', $teamNumber)
                        ->update(['rank' => $rank]);
                }
            }

            Log::info('Debate results submitted', [
                'debate_id' => $debate->id,
                'winner' => $data['winner']
            ]);

            DB::commit();
            return ['success' => true, 'message' => 'Results submitted successfully'];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to submit results for debate {$debate->id}: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check debates that need to move to preparation phase
     */
    public function checkDebatesForPreparation(): array
    {
        $results = [];
        $currentTime = Carbon::now();

        // Get debates that should move to preparation phase
        $debates = Debate::where('status', 'teamsConfirmed')
            ->whereDate('start_date', $currentTime->toDateString())
            ->get()
            ->filter(function ($debate) use ($currentTime) {
                $startTime = Carbon::parse($debate->start_date . ' ' . $debate->start_time);
                $minutesUntilStart = $currentTime->diffInMinutes($startTime, false);
                return $minutesUntilStart <= 15 && $minutesUntilStart > 0;
            });

        foreach ($debates as $debate) {
            try {
                $debate->update(['status' => 'debatePreparation']);
                $results[] = ['debate_id' => $debate->id, 'success' => true];

                Log::info("Debate {$debate->id} moved to preparation phase");
            } catch (Exception $e) {
                $results[] = ['debate_id' => $debate->id, 'success' => false, 'error' => $e->getMessage()];
                Log::error("Failed to move debate {$debate->id} to preparation: {$e->getMessage()}");
            }
        }

        return $results;
    }
}
