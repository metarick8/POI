<?php

namespace App\Http\Controllers\Debate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyDebaterRequest;
use App\Http\Requests\ApplyJudgeRequest;
use App\Http\Requests\ResponseToDebateRequest;
use App\Models\Debate;
use App\Models\Application;
use App\Services\Debate\ApplicationService;
use App\JSONResponseTrait;

class ApplicationController extends Controller
{
    use JSONResponseTrait;

    protected $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function applyDebater(ApplyDebaterRequest $request)
    {
        return 1;
        $debate = Debate::findOrFail($request->debate_id);
        $result = $this->applicationService->requestDebater($debate);

        if ($result instanceof Application) {
            return $this->successResponse('Debater application submitted', $result);
        }

        return $result;
    }

    public function applyJudge(ApplyJudgeRequest $request)
    {
        $debate = Debate::findOrFail($request->debate_id);
        $result = $this->applicationService->requestJudge($debate, $request->judge_type);

        if ($result instanceof Application) {
            return $this->successResponse('Judge application submitted', $result);
        }

        return $result;
    }

    public function respond(ResponseToDebateRequest $request)
    {
        $application = Application::findOrFail($request->application_id);
        $result = $this->applicationService->respond($request, $application);

        if ($result instanceof Application) {
            return $this->successResponse("Application {$request->response}", $result);
        }

        return $result;
    }
}
