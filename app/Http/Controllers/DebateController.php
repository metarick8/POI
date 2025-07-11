<?php

namespace App\Http\Controllers;

use App\Http\Requests\DebateInitializeRequest;
use App\Http\Requests\DebateUpdateRequest;
use App\Http\Resources\DebateResource;
use App\JSONResponseTrait;
use App\Models\Debate;
use App\Models\Motion;
use App\Services\DebateService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DebateController extends Controller
{
    use JSONResponseTrait;
    protected $debateService;
    public function __construct(DebateService $debateService)
    {
        $this->debateService = $debateService;
    }

    public function show(Debate $debate)
    {
        $debate->load([
            'motion:id,sentence',
            'chairJudge.user:id,name',
            'panelistJudges.judge.user:id,name',
            'participantsDebaters.debater.user:id,name',
            'participantsDebaters.speaker:id,team_id,position',
            'participantsDebaters.speaker.team:id,role',
        ]);
        return new DebateResource($debate);
    }

    public function create(DebateInitializeRequest $request)
    {
        $debate = $this->debateService->create($request);

        if (is_string($debate))
            return $this->errorResponse('Failed to create debate: ' . $debate, 500);

        return $this->successResponse('Debate created successfully!', new DebateResource($debate));
    }

    public function edit(DebateUpdateRequest $request, Debate $debate)
    {
        $result = $this->debateService->edit($request, $debate);

        if (is_string($result))
            return $this->errorResponse('Failed to edit debate: ' . $result, 500);

        return $this->successResponse('Debate edited successfully!', new DebateResource($result));
    }

    public function updateStatus(Debate $debate)
    {
        $result = $this->debateService->updateStatus($debate);

        if (is_string($result)) {
            return $this->errorResponse('Failed to update status: ' . $result, 500);
        }

        return $this->successResponse('Status updated successfully!', new DebateResource($result));
    }

    public function cancel(Request $request, Debate $debate)
    {
        $request->validate(['reason' => 'required|string']);
        $result = $this->debateService->cancel($debate, $request->reason);

        if (is_string($result)) {
            return $this->errorResponse('Failed to cancel debate: ' . $result, 500);
        }

        return $this->successResponse('Debate cancelled successfully!', new DebateResource($result));
    }

    public function markAsBugged(Request $request, Debate $debate)
    {
        $request->validate(['reason' => 'required|string']);
        $result = $this->debateService->markAsBugged($debate, $request->reason);

        if (is_string($result)) {
            return $this->errorResponse('Failed to mark debate as bugged: ' . $result, 500);
        }

        return $this->successResponse('Debate marked as bugged successfully!', new DebateResource($result));
    }

    public function finish(Request $request, Debate $debate)
    {
        $request->validate([
            'winner' => 'required|string',
            'summary' => 'required|string',
        ]);
        $result = $this->debateService->finish($debate, $request->winner, $request->summary);

        if (is_string($result)) {
            return $this->errorResponse('Failed to finish debate: ' . $result, 500);
        }

        return $this->successResponse('Debate finished successfully!', new DebateResource($result));
    }
}
