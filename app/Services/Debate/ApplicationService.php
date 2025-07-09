<?php

namespace App\Services\Debate;

use App\Http\Requests\ResponseToDebateRequest;
use App\JSONResponseTrait;
use App\Models\Application;
use App\Models\Debate;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApplicationService
{
    use JSONResponseTrait;

    public function index()
    {
        return Application::all();
    }

    public function debateIndex(int $debateId)
    {
        $debate = Debate::findOrFail($debateId);
        if ($debate->is_null)
            return $this->errorResponse('Debate not found!', $debateId, [], 400);

        return $debate->applications();
    }

    public function request(Debate $debate)
    {
        if (!$user = JWTAuth::parseToken()->authenticate())
            return $this->errorResponse('User not found!', '', ['User not found!'], 400);

        if ($debate->is_null)
            return $this->errorResponse('Debate not found!', '', [], 400);
        Application::create([
            'user_id' => $user->id,
            'debate_id' => $debate->id,
            'status' => 'pending'
        ]);
    }

    public function response(ResponseToDebateRequest $request)
    {
        $applicationId = $request->get('application_id');
        $application = Application::findOrFail($applicationId);
       [$debaterIsMax, $judgeIsMax] = $this->applicationAvailabilty($applicationId, $application->debate_id);

        if ($debaterIsMax)
            return $this->errorResponse('This debate has reached the maximum numner of debaters', '');

        if ($judgeIsMax)
            return $this->errorResponse('This debate has reached the maximum number of judges', '');

        if ($request->get('response'))
            $application->status = 'accepted';
        else
            $application->status = 'rejected';
        $application->touch();
        $application->save();
    }

    public function resign(int $debateId) //or $applicationId
    {
        try {
            $currentPLayersinDebate = Application::where([
                ['debate_id', $debateId],
                ['status', 'accepted']
            ])->count();

            if ($currentPLayersinDebate = 8) {
                $debate = Debate::findOrFail($debateId)->get();
                $debate->status = 'announced';
                $debate->touch();

                //to be continued on the application logic
            }

            $application = Application::where([
                ['user_id', auth()->id],
                ['debate_id', $debateId]
            ])->first();

            $application->status = 'cancelled';
            $application->touch();
            $application->save();
        } catch (Throwable $t) {
            throw $t->getMessage();
        }
    }

    private function  applicationAvailabilty(int $applicationId, $debateId)
    {

        $debateApplications = Application::where([
            ['debate_id', $debateId],
            ['status', 'accepted']
        ])->get();

        $debaterNumbers = 0;
        $judgeNumbers = 0;

        $maxDebateNumbers = 8;
        $maxJudgeNumbers = 3;

        $authcontroller = app(\App\Http\Controllers\AuthController::class);

        foreach ($debateApplications as $debateApplication) {
            $actor = $authcontroller->getAuthenticatedActor($debateApplication->user_id);
            // [$actor, $actorResource] = $authcontroller->getAuthenticatedActor($debateApplication->user_id);
            if ($actor = 'debater')
                $debaterNumbers++;
            if ($actor = 'judge')
                $judgeNumbers++;
        }

        $debaterIsMaximum = 0;
        $judgeIsMaximum = 0;
        if ($debaterNumbers = $maxDebateNumbers)
            $debaterIsMaximum = 1;

        if ($judgeNumbers = $maxJudgeNumbers)
            $judgeIsMaximum = 1;

        return [$debaterIsMaximum, $judgeIsMaximum];
    }
}
