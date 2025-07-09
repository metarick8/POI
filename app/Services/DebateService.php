<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\Participants_panelist_judge;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class DebateService
{
    public function index()
    {
        return Debate::with(['motion', 'chairJudge'])->paginate(2);
        $debates->load(['motion', 'chairJudge']);
    }

    public function create($request)
    {
        DB::beginTransaction();

        try {
            $debate = Debate::create([
                //'motion_id' => $request->get('motion_id'),
                //'chair_judge_id' => $request->get('chair_judge_id'),
                'start_date' => $request->get('date'),
                'type' => $request->get('type'),
                'start_time' => $request->get('time'),
            ]);
            // if ($request->has('panelist_judges'))
            //     foreach ($request->get('panelist_judges') as $panelistJudgeId)
            //         $debate->panelist_judges()->create([
            //             'debate_id' => $debate->id,
            //             'judge_id' => $panelistJudgeId,
            //             'created_at' => now()
            //         ]);

            DB::commit();
            return $debate;
            // return true;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }

    public function updateStatus(Debate $debate)
    {
        DB::beginTransaction();

        try {
            $debatersCount = $debate->debaters_count;
            $judgesCount = ($debate->chair_judge_id ? 1 : 0) + $debate->panelist_judges_count;

            if ($debate->status === 'announced' && $debatersCount === 8 && $judgesCount >= 1 && $judgesCount <= 3) {
                $debate->update(['status' => 'applied']);
            } elseif ($debate->status === 'applied' && now()->gte($debate->start_date . ' ' . $debate->start_time)) {
                $debate->update(['status' => 'ongoing']);
            }

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
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
            ]);

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
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
            ]);

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
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
            ]);

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }


    public function edit()
    {

    }

}

/*
<?php

namespace App\Services;

use App\Models\Debate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DebateService
{
    public function index($perPage = 50)
    {
        return Debate::with(['motion', 'chairJudge'])->paginate($perPage);
    }

    public function create($request)
    {
        DB::beginTransaction();

        try {
            $debate = Debate::create([
                'motion_id' => $request->get('motion_id'),
                'chair_judge_id' => $request->get('chair_judge_id'),
                'start_date' => $request->get('date'),
                'start_time' => $request->get('time'),
                'type' => $request->get('type'),
                'status' => 'announced',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->has('panelist_judges')) {
                foreach ($request->get('panelist_judges') as $panelistJudgeId) {
                    $debate->panelistJudges()->create([
                        'judge_id' => $panelistJudgeId,
                        'created_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to create debate: {$t->getMessage()}");
            return $t->getMessage();
        }
    }

    public function edit($request, Debate $debate)
    {
        DB::beginTransaction();

        try {
            $debate->update([
                'motion_id' => $request->get('motion_id', $debate->motion_id),
                'chair_judge_id' => $request->get('chair_judge_id', $debate->chair_judge_id),
                'start_date' => $request->get('date', $debate->start_date),
                'start_time' => $request->get('time', $debate->start_time),
                'type' => $request->get('type', $debate->type),
                'updated_at' => now(),
            ]);

            if ($request->has('panelist_judges')) {
                $debate->panelistJudges()->delete();
                foreach ($request->get('panelist_judges') as $panelistJudgeId) {
                    $debate->panelistJudges()->create([
                        'judge_id' => $panelistJudgeId,
                        'created_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return $debate;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to edit debate {$debate->id}: {$t->getMessage()}");
            return $t->getMessage();
        }
    }

    public function updateStatus(Debate $debate)
    {
        DB::beginTransaction();

        try {
            $debatersCount = $debate->debaters_count;
            $judgesCount = ($debate->chair_judge_id ? 1 : 0) + $debate->panelist_judges_count;
            $currentTime = now();
            $startDateTime = $debate->start_date . ' ' . $debate->start_time;

            Log::info("Updating debate {$debate->id}", [
                'status' => $debate->status,
                'debaters' => $debatersCount,
                'judges' => $judgesCount,
                'current_time' => $currentTime,
                'start_datetime' => $startDateTime,
                'timezone' => $currentTime->timezone->getName(),
            ]);

            if ($debate->status === 'announced' && $debatersCount === 8 && $judgesCount >= 1 && $judgesCount <= 3) {
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
}
*/
