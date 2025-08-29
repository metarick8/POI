<?php

namespace App\Http\Controllers;

use App\Http\Requests\DebateInitializeRequest;
use App\Http\Requests\DebatePreparationRequest;
use App\Http\Requests\DebateResultRequest;
use App\Http\Requests\ListDebatesRequest;
use App\Http\Requests\preparationStatusRequest;
use App\Http\Requests\selectTeamsRequest;
use App\Http\Resources\DebateResource;
use App\JSONResponseTrait;
use App\Models\Debate;
use App\Services\DebateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Tymon\JWTAuth\Facades\JWTAuth;

class DebateController extends Controller
{
    use JSONResponseTrait;

    protected $debateService, $authController;

    public function __construct(DebateService $debateService, AuthController $authController)
    {
        $this->debateService = $debateService;
        $this->authController = $authController;
    }

    public function index(ListDebatesRequest $request)
    {
        if (!$user = JWTAuth::parseToken()->authenticate())
            return response()->json(['error' => 'User not found'], 404);

        [$actor, $actorResource] = $this->authController->getAuthenticatedActor($user->id);
        if (!$user) {
            Log::error('No authenticated user found in DebateController');
            return $this->errorResponse('Unauthorized', null, ['No authenticated user found'], 401);
        }
        Log::debug('Authenticated user in DebateController', [
            'guard' => $actor,
            'user_id' => $user->id,
            'model' => get_class($user)
        ]);

        $debates = $this->debateService->index($request->validatedStatus());
        $debates->getCollection()->transform(function ($debate) use ($user, $actor) {
            $isAbleToApply = false;
            if ($actor === 'debater')
                $isAbleToApply = $user->can('applyDebater', $debate);
            elseif ($actor === 'judge')
                $isAbleToApply = $user->can('applyJudge', [$debate, 'panelist']) || ($debate->chair_judge_id === null && $user->can('applyJudge', [$debate, 'chair']));

            $debate->isAbleToApply = $isAbleToApply;
            return $debate;
        });
        return $this->successResponse('Debates retrieved successfully', DebateResource::collection($debates));
    }
    public function indexForAdmin()
    {
        $debates = $this->debateService->indexForAdmin();
        return $this->successResponse("list of debates:", DebateResource::collection($debates));
    }
    public function show(Debate $debate)
    {
        $debate->load([
            'motion:id,sentence',
            'chairJudge.user:id,name',
            'panelistJudges.judge.user:id,name',
            'debaters.user:id,name',
        ]);
        return $this->successResponse('Debate retrieved successfully', new DebateResource($debate));
    }

    public function create(DebateInitializeRequest $request)
    {
        Log::debug('Create debate request received', $request->all());

        $debate = $this->debateService->create($request);

        if (is_string($debate)) {
            Log::error('Debate creation failed', ['error' => $debate]);
            return $this->errorResponse('Failed to create debate: ' . $debate, null, [$debate], 500);
        }

        Log::debug('Debate created successfully', ['debate_id' => $debate->id]);
        return $this->successResponse('Debate created successfully', new DebateResource($debate));
    }
    public function updateStatus(Debate $debate)
    {
        $result = $this->debateService->updateStatus($debate);

        if (is_string($result))
            return $this->errorResponse('Failed to update status: ' . $result, '', [], 500);

        return $this->successResponse('Status updated successfully', new DebateResource($result));
    }

    public function cancel(Request $request, Debate $debate)
    {
        $request->validate(['reason' => 'required|string']);
        $result = $this->debateService->cancel($debate, $request->reason);

        if (is_string($result))
            return $this->errorResponse('Failed to cancel debate: ' . $result, '', [], 500);

        return $this->successResponse('Debate cancelled successfully', new DebateResource($result));
    }

    public function markAsBugged(Request $request, Debate $debate)
    {
        $request->validate(['reason' => 'required|string']);
        $result = $this->debateService->markAsBugged($debate, $request->reason);

        if (is_string($result))
            return $this->errorResponse('Failed to mark debate as bugged: ' . $result, '', [], 500);

        return $this->successResponse('Debate marked as bugged successfully', new DebateResource($result));
    }

    public function finish(Request $request, Debate $debate)
    {
        $request->validate([
            'winner' => 'required|string',
            'summary' => 'required|string',
        ]);
        $result = $this->debateService->finish($debate, $request->winner, $request->summary);

        if (is_string($result))
            return $this->errorResponse('Failed to finish debate: ' . $result, '', [], 500);

        return $this->successResponse('Debate finished successfully', new DebateResource($result));
    }


    // public function toDebatePreparationStatus(debatePreparationRequest $request)
    // {

    //     if($debate->status==='playersConfirmed')
    //     {

    //         $debate->update(['status'=>'debatePreperation']);
    //         return $this->successResponse('Debate Status changed successfully!',$debate);
    //     } else {
    //         return $this->errorResponse("Current Debate Status isn't (playersConfirmed)",403);
    //     }
    // }
    // public function index() {
    //             try {
    //         $debates = $this->debateService->index();
    //         return $this->successResponse("Education data:",$debates, 200);
    //     } catch (\Throwable $t) {
    //         return $this->errorResponse("Something went wrong!", $t->getMessage());
    //     }
    // }

    public function result(DebateResultRequest $request, Debate $debate) {}

    public function preparationStatus(DebatePreparationRequest $request, Debate $debate)
    {
        $result = $this->debateService->prepare($request, $debate);

        if ($result['success']) {
            return $this->successResponse($result['message'], null);
        }

        return $this->errorResponse('Failed to prepare debate', null, ['error' => $result['error']], 422);
    }

    /**
     * Assign teams after players are confirmed (Admin only)
     */
    public function assignTeams(Request $request, Debate $debate)
    {
        try {
            $validated = $request->validate([
                'team_assignments' => 'required|array',
                'team_assignments.*' => 'required|array|size:2', // Each team must have exactly 2 debaters
            ]);

            // Ensure we have exactly 4 teams
            if (count($validated['team_assignments']) !== 4) {
                return $this->errorResponse(
                    'Must assign exactly 4 teams',
                    null,
                    ['error' => 'Invalid team count'],
                    422
                );
            }

            $result = $this->debateService->assignTeams($debate, $validated['team_assignments']);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to assign teams',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            return $this->successResponse($result['message'], new DebateResource($debate->fresh()));

        } catch (\Exception $e) {
            Log::error('Error assigning teams', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while assigning teams',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Add panelist judge to debate (Admin only)
     */
    public function addPanelistJudge(Request $request, Debate $debate)
    {
        try {
            $validated = $request->validate([
                'judge_id' => 'required|exists:judges,id'
            ]);

            $result = $this->debateService->addPanelistJudge($debate, $validated['judge_id']);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to add panelist judge',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            return $this->successResponse($result['message'], null);

        } catch (\Exception $e) {
            Log::error('Error adding panelist judge', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while adding panelist judge',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Submit debate results (Chair judge only)
     */
    public function submitResults(Request $request, Debate $debate)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return $this->errorResponse('Unauthorized', null, ['User not authenticated'], 401);
            }

            // Check if user is the chair judge
            if (!$debate->chairJudge || $debate->chairJudge->user_id !== $user->id) {
                return $this->errorResponse(
                    'Only the chair judge can submit results',
                    null,
                    ['error' => 'Access denied'],
                    403
                );
            }

            $validated = $request->validate([
                'winner' => 'required|string|max:255',
                'summary' => 'required|string|max:2000',
                'ranks' => 'required|array|size:4', // Rankings for 4 teams
                'ranks.*' => 'required|integer|between:1,4'
            ]);

            // Validate ranks are unique
            if (count(array_unique($validated['ranks'])) !== 4) {
                return $this->errorResponse(
                    'Team ranks must be unique',
                    null,
                    ['error' => 'Invalid rankings'],
                    422
                );
            }

            $result = $this->debateService->submitResults($debate, $validated);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to submit results',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            return $this->successResponse($result['message'], new DebateResource($debate->fresh()));

        } catch (\Exception $e) {
            Log::error('Error submitting results', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return $this->errorResponse(
                'An error occurred while submitting results',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get debate participants with their teams and speaker assignments
     */
    public function getParticipants(Debate $debate)
    {
        try {
            $participants = $debate->participantsDebaters()
                                  ->with([
                                      'debaterUser:id,name',
                                      'speaker:id,position,team_id'
                                  ])
                                  ->get()
                                  ->groupBy('team_number');

            return $this->successResponse(
                'Participants retrieved successfully',
                $participants
            );

        } catch (\Exception $e) {
            Log::error('Error getting participants', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving participants',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get debate judges (chair and panelist)
     */
    public function getJudges(Debate $debate)
    {
        try {
            $judges = [];

            // Chair judge
            if ($debate->chairJudge) {
                $judges['chair'] = [
                    'judge_id' => $debate->chairJudge->id,
                    'user_id' => $debate->chairJudge->user_id,
                    'name' => $debate->chairJudge->user->name ?? null,
                    'type' => 'chair'
                ];
            }

            // Panelist judges
            $judges['panelists'] = $debate->panelistJudges()
                                         ->with('judge.user:id,name')
                                         ->get()
                                         ->map(function($panelist) {
                                             return [
                                                 'judge_id' => $panelist->judge_id,
                                                 'user_id' => $panelist->judge->user_id ?? null,
                                                 'name' => $panelist->judge->user->name ?? null,
                                                 'type' => 'panelist'
                                             ];
                                         });

            return $this->successResponse(
                'Judges retrieved successfully',
                $judges
            );

        } catch (\Exception $e) {
            Log::error('Error getting judges', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving judges',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Check debates for preparation phase (Cron job endpoint)
     */
    public function checkPreparationPhase()
    {
        try {
            $results = $this->debateService->checkDebatesForPreparation();

            return $this->successResponse(
                'Preparation phase check completed',
                $results
            );

        } catch (\Exception $e) {
            Log::error('Error checking preparation phase', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while checking preparation phase',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
