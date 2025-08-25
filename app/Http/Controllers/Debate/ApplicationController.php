<?php

namespace App\Http\Controllers\Debate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyDebaterRequest;
use App\Http\Requests\ApplyJudgeRequest;
use App\Http\Requests\ResponseToDebateRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Debate;
use App\Models\Application;
use App\Services\Debate\ApplicationService;
use App\JSONResponseTrait;
use Illuminate\Support\Facades\Log;

class ApplicationController extends Controller
{
    use JSONResponseTrait;

    protected $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function index()
    {
        try {
            $applications = $this->applicationService->index();
            Log::info('Retrieved applications', [
                'count' => $applications->count(),
                'admin_id' => auth('admin')->id(),
            ]);

            if ($applications->isEmpty()) {
                return $this->successResponse('No applications found', []);
            }

            return $this->successResponse('Applications retrieved successfully', ApplicationResource::collection($applications));
        } catch (\Exception $e) {
            Log::error('Failed to retrieve applications', [
                'error' => $e->getMessage(),
                'admin_id' => auth('admin')->id(),
            ]);
            return $this->errorResponse('Failed to retrieve applications', null, [$e->getMessage()], 500);
        }
    }

    public function applyDebater(Debate $debate)
    {

        //$debate = Debate::findOrFail($request->debate_id); //no need for debate id from request

        $result = $this->applicationService->requestDebater($debate);
        if ($result instanceof Application) {
            return $this->successResponse('Debater application submitted', $result);
        }

        return $result;
    }

    public function applyJudge(ApplyJudgeRequest $request, Debate $debate)
    {
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
