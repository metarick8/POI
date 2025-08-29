<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\ParticipantsDebater;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DebateService
{
    public function index(array $status = [], $perPage = 4)
    {
        $query = Debate::with(['motion', 'chairJudge','debaters']);

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
}
