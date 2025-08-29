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
        $query = Debate::with(['motion', 'chairJudge']);

        if (!empty($status)) {
            $query->whereIn('status', $status);
        }
        return $query->paginate($perPage);
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

    public function finish(Debate $debate, string $winner, string $summary)
    {
        DB::beginTransaction();

        try {
            $debate->update([
                'status' => 'finished',
                'winner' => $winner,
                'summary' => $summary,
                'updated_at' => now(),
            ]);

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to finish debate {$debate->id}: {$t->getMessage()}");
            return $t->getMessage();
        }
    }
    public function prepare(Request $request, Debate $debate)
    {
        DB::beginTransaction();

        try {
            Log::info('Attempting to prepare debate', [
                'debate_id' => $debate->id,
                'motion_id' => $request->motion_id,
                'status' => $debate->status,
            ]);

            // Validate debate is ready for preparation
            if ($debate->status !== 'debatePreparation') {
                throw new Exception('Debate must be in debatePreparation status');
            }
            
            // Set motion if provided
            if ($request->motion_id) {
                $debate->motion_id = $request->motion_id;
                $debate->save();
            }

            $participants = ParticipantsDebater::where('debate_id', $debate->id)->get();

            if ($participants->count() !== 8) {
                throw new Exception('Must have exactly 8 participants for debate preparation');
            }

            // Validate we have 4 teams with 2 players each
            $teamCounts = $participants->groupBy('team_number')->map->count();
            if ($teamCounts->count() !== 4 || $teamCounts->contains(fn($count) => $count !== 2)) {
                throw new Exception('Must have exactly 4 teams with 2 players each');
            }

            $positions = $request->positions ?? [];
            
            // Get static teams and speakers
            $teams = Team::all()->keyBy('id'); // OG, OO, CG, CO
            $speakers = Speaker::all()->keyBy('id'); // 8 speaker positions
            
            if ($teams->count() !== 4) {
                throw new Exception('Must have exactly 4 static teams (OG, OO, CG, CO)');
            }

            if ($speakers->count() !== 8) {
                throw new Exception('Must have exactly 8 static speaker positions');
            }

            // Assign speakers to participants
            foreach ($positions as $position) {
                $teamNumber = $position['team_number'];
                $debaterIds = $position['debater_ids'];
                $speakerIds = $position['speaker_ids']; // Speaker positions for this team

                if (count($debaterIds) !== 2 || count($speakerIds) !== 2) {
                    throw new Exception("Team {$teamNumber} must have exactly 2 debaters and 2 speaker positions");
                }

                // Validate speakers belong to the correct team
                $teamSpeakers = $speakers->whereIn('id', $speakerIds)->where('team_id', $teamNumber);
                if ($teamSpeakers->count() !== 2) {
                    throw new Exception("Invalid speaker assignments for team {$teamNumber}");
                }

                // Update participants
                for ($i = 0; $i < 2; $i++) {
                    $participant = $participants->where('debater_id', $debaterIds[$i])
                                               ->where('team_number', $teamNumber)
                                               ->first();
                    
                    if (!$participant) {
                        throw new Exception("Participant not found for debater {$debaterIds[$i]} in team {$teamNumber}");
                    }

                    $participant->update([
                        'speaker_id' => $speakerIds[$i],
                        'rank' => $i + 1 // First speaker = 1, Second speaker = 2
                    ]);
                }
            }

            // Validate all participants have been assigned speaker positions
            $unassignedParticipants = ParticipantsDebater::where('debate_id', $debate->id)
                                                        ->whereNull('speaker_id')
                                                        ->count();
            
            if ($unassignedParticipants > 0) {
                throw new Exception('All participants must be assigned speaker positions');
            }

            Log::info('Debate preparation completed successfully', ['debate_id' => $debate->id]);
            
            DB::commit();
            return ['success' => true, 'message' => 'Debate preparation completed successfully'];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to prepare debate', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
