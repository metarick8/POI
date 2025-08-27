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
        if (!$user) {
            return $this->errorResponse('User not authenticated', '', ['User not authenticated'], 401);
        }

        if (!$user->can('applyJudge', [$debate, $judgeType])) {
            return $this->errorResponse('You cannot apply as a judge', '', ['Unauthorized or max judges reached'], 403);
        }

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
            $debate = $application->debate;
            $isJudge = in_array($application->type, ['chair_judge', 'panelist_judge']);
            if ($request->response === 'approved') {
                if ($application->type === 'debater' && $debate->debater_count >= 8) {
                    return $this->errorResponse('Max debaters reached', null, ['Max debaters reached'], 403);
                }
                if ($isJudge && $debate->judge_count >= 3) {
                    return $this->errorResponse('Max judges reached', null, ['Max judges reached'], 403);
                }
                if ($application->type === 'chair_judge' && $debate->chair_judge_id !== null) {
                    return $this->errorResponse('Chair judge already assigned', null, ['Chair judge already assigned'], 403);
                }

                $application->update(['status' => 'approved', 'updated_at' => now()]);

                if ($application->type === 'debater') {
                    $debate->update(['debater_count' => $debate->debater_count + 1, 'updated_at' => now()]);
                } elseif ($isJudge) {
                    $debate->update(['judge_count' => $debate->judge_count + 1, 'updated_at' => now()]);
                    if ($application->type === 'chair_judge') {
                        $judge = $application->user->judge;
                        if (!$judge) {
                            throw new \Exception('No judge record found for user');
                        }
                        $debate->update(['chair_judge_id' => $judge->id, 'updated_at' => now()]);
                    } else {
                        $judge = $application->user->judge;
                        if (!$judge) {
                            throw new \Exception('No judge record found for user');
                        }
                        // $debate->panelistJudges()->create([
                        //     'judge_id' => $judge->id,
                        //     'debate_id' => $debate->id,
                        //     'created_at' => now(),
                        // ]);
                        Participants_panelist_judge::create([
                            'judge_id' => $judge->id,
                            'debate_id' => $debate->id,
                            'created_at' => now()
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
}
