<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateJudgeRequest;
use App\Services\RateService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class RateController extends Controller
{
    protected $rateService;

    public function __construct(RateService $rateService)
    {
        $this->rateService = $rateService;
    }

    public function rateJudge(RateJudgeRequest $request)
    {
        try {

             $debater = Auth::guard('debater')->user();

            if (!$debater) {
                return response()->json([
                    'error' => 'Current user is not registered as a debater'
                ], 403);
            }
            return $this->rateService->rateJudge(
                $debater->id,
                $request->judge_id,
                $request->debate_id,
                $request->rate,
                $request->opinion
            );

            return response()->json(['message' => 'Judge rated successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
