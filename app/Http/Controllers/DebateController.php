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
        if ($result === true) {
            return $this->successResponse('Debate preparation completed successfully!', null);
        }

        return $this->errorResponse('Failed to prepare debate', null, ['error' => $result], 422);
    }
}
