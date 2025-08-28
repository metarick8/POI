<?php

namespace App\Services\Debate;

use App\Http\Requests\ResponseToDebateRequest;
use App\Models\Application;
use App\Models\Debate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;
use App\JSONResponseTrait;
use App\Models\Participants_panelist_judge;

class ApplicationService
{
    use JSONResponseTrait;

    public function index()
    {
        return $applications = Application::with(['user.debater', 'user.judge', 'debate'])->where('status', 'pending')->get();
    }
    public function requestDebater(Debate $debate)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', '', ['User not authenticated'], 401);
        }

        if (!$user->can('applyDebater', $debate)) {
            return $this->errorResponse('You cannot apply to this debate', '', ['Unauthorized or max debaters reached'], 403);
        }

        DB::beginTransaction();
        try {
            $application = Application::create([
                'user_id' => $user->id,
                'debate_id' => $debate->id,
                'status' => 'pending',
                'type' => 'debater',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Debater application created for debate {$debate->id}", [
                'user_id' => $user->id,
                'debate_id' => $debate->id,
                'type' => 'debater',
                'timezone' => now()->timezone->getName(),
            ]);

            DB::commit();
            return $application;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to create debater application: {$t->getMessage()}");
            return $this->errorResponse('Failed to apply', '', [$t->getMessage()], 500);
        }
    }

    public function requestJudge(Debate $debate, string $judgeType)
    {
        $user = Auth::user();
        if (!$user)
            return $this->errorResponse('User not authenticated', '', ['User not authenticated'], 401);

        if (!$user->can('applyJudge', [$debate, $judgeType]))
            return $this->errorResponse('You cannot apply as a judge', '', ['Unauthorized or max judges reached'], 403);

        DB::beginTransaction();
        try {
            $application = Application::create([
                'user_id' => $user->id,
                'debate_id' => $debate->id,
                'status' => 'pending',
                'type' => $judgeType === 'chair' ? 'chair_judge' : 'panelist_judge',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Judge application created for debate {$debate->id}", [
                'user_id' => $user->id,
                'debate_id' => $debate->id,
                'type' => $judgeType === 'chair' ? 'chair_judge' : 'panelist_judge',
                'timezone' => now()->timezone->getName(),
            ]);

            DB::commit();
            return $application;
        } catch (Throwable $t) {
            DB::rollBack();
            Log::error("Failed to create judge application: {$t->getMessage()}");
            return $this->errorResponse('Failed to apply', '', [$t->getMessage()], 500);
        }
    }

    public function respond(ResponseToDebateRequest $request, Application $application)
    {
        DB::beginTransaction();
        try {
            // Lock the debate record to prevent concurrent updates
            $debate = Debate::where('id', $application->debate_id)->lockForUpdate()->firstOrFail();
            $isJudge = in_array($application->type, ['chair_judge', 'panelist_judge']);

            if ($request->response === 'approved') {
                // Check if approving this debater would exceed the limit
                if ($application->type === 'debater' && $debate->debater_count >= 8) {
                    DB::rollBack();
                    return $this->errorResponse('Max debaters reached', null, ['Max debaters reached'], 403);
                }
                if ($isJudge && $debate->judge_count >= 3) {
                    DB::rollBack();
                    return $this->errorResponse('Max judges reached', null, ['Max judges reached'], 403);
                }
                if ($application->type === 'chair_judge' && $debate->chair_judge_id !== null) {
                    DB::rollBack();
                    return $this->errorResponse('Chair judge already assigned', null, ['Chair judge already assigned'], 403);
                }
                if ($application->type === 'panelist_judge' && $debate->chair_judge_id === null && $debate->judge_count >= 2) {
                    DB::rollBack();
                    return $this->errorResponse('Chair judge not assigned', null, ['Chair judge not assigned'], 403);
                }

                // Update application status
                $application->update(['status' => 'approved', 'updated_at' => now()]);

                if ($application->type === 'debater') {
                    // Increment debater_count
                    $newDebaterCount = $debate->debater_count + 1;
                    $debate->update(['debater_count' => $newDebaterCount, 'updated_at' => now()]);

                    // Check if conditions are met for playersConfirmed status
                    if ($newDebaterCount === 8 && $debate->chair_judge_id !== null && $debate->judge_count <= 3) {
                        $debate->update(['status' => 'playersConfirmed', 'updated_at' => now()]);
                        Application::where('debate_id', $debate->id)
                            ->where('status', 'pending')
                            ->update(['status' => 'rejected', 'updated_at' => now()]);
                    }
                } elseif ($isJudge) {
                    // Increment judge_count
                    $debate->update(['judge_count' => $debate->judge_count + 1, 'updated_at' => now()]);

                    if ($application->type === 'chair_judge') {
                        $judge = $application->user->judge;
                        if (!$judge) {
                            throw new \Exception('No judge record found for user');
                        }
                        $debate->update(['chair_judge_id' => $judge->id, 'updated_at' => now()]);

                        // Check if conditions are met for playersConfirmed status
                        if ($debate->debater_count === 8 && $debate->chair_judge_id !== null && $debate->judge_count <= 3) {
                            $debate->update(['status' => 'playersConfirmed', 'updated_at' => now()]);
                            Application::where('debate_id', $debate->id)
                                ->where('status', 'pending')
                                ->update(['status' => 'rejected', 'updated_at' => now()]);
                        }
                    } else {
                        $judge = $application->user->judge;
                        if (!$judge) {
                            throw new \Exception('No judge record found for user');
                        }
                        Participants_panelist_judge::create([
                            'judge_id' => $judge->id,
                            'debate_id' => $debate->id,
                            'created_at' => now(),
                        ]);
                    }
                }
            } else {
                $application->update(['status' => 'rejected', 'updated_at' => now()]);
            }

            Log::info("Application {$application->id} updated", [
                'debate_id' => $debate->id,
                'user_id' => $application->user_id,
                'status' => $application->status,
                'type' => $application->type,
                'timezone' => now()->timezone->getName(),
            ]);

            DB::commit();
            return $application;
        } catch (\Throwable $t) {
            DB::rollBack();
            Log::error("Failed to process application {$application->id}: {$t->getMessage()}", [
                'user_id' => $application->user_id,
                'debate_id' => $application->debate_id,
                'type' => $application->type,
            ]);
            return $this->errorResponse('Failed to process application', null, [$t->getMessage()], 500);
        }
    }

    public function isPlayersConfirmedStatusUpdated(Debate $debate): bool
    {
        if ($debate->status !== 'announced')
            return false;
        if ($debate->debater_count === 8 && $debate->chair_judge_id !== null && $debate->judge_count <= 3) {
            DB::transaction();
            try {
                $debate->update(['status' => 'playersConfirmed', 'updated_at' => now()]);
                Application::where('debate_id', $debate->id)
                    ->where('status', 'pending')
                    ->update(["status" => "rejected"]);
                DB::commit();
                return true;
            } catch (Throwable $t) {
                DB::rollBack();
                return false;
            }
        }
        return false;
    }
}
