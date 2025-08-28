<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\ParticipantsDebater;
use App\Models\Speaker;
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
            $debaterCount = $debate->debater_count;
            $judgeCount = $debate->judge_count;
            $currentTime = now();
            $startDateTime = $debate->start_date . ' ' . $debate->start_time;

            Log::info("Updating debate {$debate->id}", [
                'status' => $debate->status,
                'debaters' => $debaterCount,
                'judges' => $judgeCount,
                'current_time' => $currentTime,
                'start_datetime' => $startDateTime,
                'timezone' => $currentTime->timezone->getName(),
            ]);

            if ($debate->status === 'announced' && $debaterCount === 8 && $judgeCount >= 1 && $judgeCount <= 3) {
                $debate->update(['status' => 'applied', 'updated_at' => now()]);
                Log::info("Debate {$debate->id} updated to applied");
            } elseif ($debate->status === 'applied' && $currentTime->gte($startDateTime)) {
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
            ]);
            
            $debate->motion_id = $request->motion_id;
            $debate->save();

            $participants = ParticipantsDebater::where('debate_id', $debate->id)->get();

            if ($participants->isEmpty()) {
                Log::warning('No participants found for debate', ['debate_id' => $debate->id]);
                DB::rollBack();
                return 'No participants found for this debate';
            }

            $positions = $request->positions;
            $processedDebaters = [];

            foreach ($positions as $position){
                $teamId = $position['team_id'];
                $debaterIds = $position['debater_ids'];

                $speakers = Speaker::where('team_id', $teamId)->get();
                if ($speakers->count() < 2) {
                    Log::warning('Insufficient speakers for team', ['team_id' => $teamId, 'speaker_count' => $speakers->count()]);
                    DB::rollBack();
                    return 'Insufficient speakers for team ' . $teamId;
                }

                $speakerIds = $speakers->pluck('id')->take(2)->values();
                $debater1Id = $debaterIds[0];
                $debater2Id = $debaterIds[1];

                $participant1 = $participants->where('debater_id', $debater1Id)->where('team_number', $teamId)->first();
                $participant2 = $participants->where('debater_id', $debater2Id)->where('team_number', $teamId)->first();

                if (!$participant1 || !$participant2) {
                    Log::warning('Debaters not found in participants for team', ['team_id' => $teamId, 'debater_ids' => $debaterIds]);
                    DB::rollBack();
                    return 'One or more debaters not found in participants for team ' . $teamId;
                }

                $participant1->update(['speaker_id' => $speakerIds[0], 'rank' => 1]);
                $participant2->update(['speaker_id' => $speakerIds[1], 'rank' => 2]);

                $processedDebaters = array_merge($processedDebaters, [$debater1Id, $debater2Id]);
            }

            $unprocessedParticipants = $participants->whereNotIn('debater_id', $processedDebaters);
            if ($unprocessedParticipants->isNotEmpty()) {
                Log::warning('Unprocessed participants found', ['debate_id' => $debate->id, 'unprocessed_debater_ids' => $unprocessedParticipants->pluck('debater_id')->toArray()]);
                DB::rollBack();
                return 'Some participants were not assigned positions';
            }

            Log::info('Debate preparation completed successfully', ['debate_id' => $debate->id]);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to prepare debate', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage(),
            ]);
            return 'Failed to prepare debate due to an unexpected error';
        }
    }
}
