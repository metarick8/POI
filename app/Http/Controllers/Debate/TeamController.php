<?php

namespace App\Http\Controllers\Debate;

use App\Http\Controllers\Controller;
use App\Http\Requests\selectTeamsRequest;
use App\Http\Resources\ApplicationResource;
use App\JSONResponseTrait;
use App\Models\Application;
use App\Models\Debate;
use App\Services\Debate\TeamService;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    use JSONResponseTrait;
    protected $teamService;
    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }
    public function listTeams(Debate $debate)
    {
        if($debate->status !== "playersConfirmed")
        {
            Log::warning('Attempted to assign teams for debate in invalid status', [
                'debate_id' => $debate->id,
                'status' => $debate->status,
            ]);
            return $this->errorResponse(
                'Teams can only be listed when debate status is playersConfirmed',
                null,
                ['Invalid debate status'],
                403
            );
        }
         $result = $this->teamService->indexTeams($debate->id);
         return $this->successResponse("Here's list of debaters:", ApplicationResource::collection($result));

    }
    public function selectTeams(selectTeamsRequest $request)
    {
        Log::info('Starting team assignment for debate', ['debate_id' => $request->debate_id]);

        // Check if debate exists 
        $debate = Debate::find($request->debate_id);
        if (!$debate) {
            Log::warning('Debate not found for team assignment', ['debate_id' => $request->debate_id]);
            return $this->errorResponse('Debate not found', null, ['Invalid debate ID'], 404);
        }

        // Check if debate is in playersConfirmed status
        if ($debate->status !== 'playersConfirmed') {
            Log::warning('Attempted to assign teams for debate in invalid status', [
                'debate_id' => $request->debate_id,
                'status' => $debate->status,
            ]);
            return $this->errorResponse(
                'Teams can only be assigned when debate status is playersConfirmed',
                null,
                ['Invalid debate status'],
                403
            );
        }

        return $result = $this->teamService->assignTeams($debate, $request->teams);

        // Check if service returned an error (string)
        if (is_string($result)) {
            Log::error('Failed to assign teams for debate', [
                'debate_id' => $request->debate_id,
                'error' => $result,
            ]);
            return $this->errorResponse('Failed to assign teams', null, [$result], 500);
        }

        Log::info('Teams assigned successfully for debate', ['debate_id' => $request->debate_id]);

        return $this->successResponse('Teams assigned successfully', $result);
    }
}
