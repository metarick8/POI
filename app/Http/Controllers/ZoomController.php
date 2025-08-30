<?php

namespace App\Http\Controllers;

use App\JSONResponseTrait;
use App\Models\Debate;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class ZoomController extends Controller
{
    use JSONResponseTrait;

    protected $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * Link a judge to Zoom account
     */
    public function linkJudgeToZoom(Request $request)
    {
        try {
            $validated = $request->validate([
                'judge_id' => 'required|exists:judges,id',
                'email' => 'required|email'
            ]);

            $result = $this->zoomService->linkJudgeToZoom(
                $validated['judge_id'],
                $validated['email']
            );

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to link judge to Zoom',
                    null,
                    ['error' => $result['error'] ?? 'Unknown error'],
                    422
                );
            }

            return $this->successResponse($result['message'], null);
        } catch (\Exception $e) {
            Log::error('Error linking judge to Zoom', [
                'error' => $e->getMessage(),
                'judge_id' => $request->judge_id ?? null
            ]);

            return $this->errorResponse(
                'An error occurred while linking judge to Zoom',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Create Zoom meeting for a debate
     */
    public function createMeeting(Request $request, int $debateId)
    {
        try {
            $debate = Debate::findOrFail($debateId);

             return $result = $this->zoomService->createDebateMeeting($debate);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to create Zoom meeting',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            return $this->successResponse(
                $result['message'],
                $result['data']
            );
        } catch (\Exception $e) {
            Log::error('Error creating Zoom meeting', [
                'error' => $e->getMessage(),
                'debate_id' => $debateId
            ]);

            return $this->errorResponse(
                'An error occurred while creating Zoom meeting',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get start URL for chair judge
     */
    public function getStartUrl(int $debateId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return $this->errorResponse('Unauthorized', null, ['User not authenticated'], 401);
            }

            $debate = Debate::findOrFail($debateId);

            if (!$debate->start_url) {
                return $this->errorResponse(
                    'No Zoom meeting created yet',
                    null,
                    ['error' => 'Meeting not found'],
                    404
                );
            }

            // Check if user is the chair judge
            if (!$debate->chairJudge || $debate->chairJudge->user_id !== $user->id) {
                return $this->errorResponse(
                    'Only the chair judge can start the meeting',
                    null,
                    ['error' => 'Access denied'],
                    403
                );
            }

            return $this->successResponse(
                'Start URL retrieved successfully',
                ['start_url' => $debate->start_url]
            );
        } catch (\Exception $e) {
            Log::error('Error getting start URL', [
                'error' => $e->getMessage(),
                'debate_id' => $debateId
            ]);

            return $this->errorResponse(
                'An error occurred while getting start URL',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get join URL for participants
     */
    public function getJoinUrl(int $debateId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return $this->errorResponse('Unauthorized', null, ['User not authenticated'], 401);
            }

            $debate = Debate::findOrFail($debateId);

            if (!$debate->join_url) {
                return $this->errorResponse(
                    'No Zoom meeting created yet',
                    null,
                    ['error' => 'Meeting not found'],
                    404
                );
            }

            // TODO: Add validation that user is a participant in the debate

            return $this->successResponse(
                'Join URL retrieved successfully',
                [
                    'join_url' => $debate->join_url,
                    'password' => $debate->password
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error getting join URL', [
                'error' => $e->getMessage(),
                'debate_id' => $debateId
            ]);

            return $this->errorResponse(
                'An error occurred while getting join URL',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Start debate (update status to ongoing)
     */
    public function startDebate(int $debateId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return $this->errorResponse('Unauthorized', null, ['User not authenticated'], 401);
            }

            $debate = Debate::findOrFail($debateId);

            // Check if user is the chair judge
            if (!$debate->chairJudge || $debate->chairJudge->user_id !== $user->id) {
                return $this->errorResponse(
                    'Only the chair judge can start the debate',
                    null,
                    ['error' => 'Access denied'],
                    403
                );
            }

            $result = $this->zoomService->startDebate($debate);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to start debate',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            return $this->successResponse($result['message'], null);
        } catch (\Exception $e) {
            Log::error('Error starting debate', [
                'error' => $e->getMessage(),
                'debate_id' => $debateId
            ]);

            return $this->errorResponse(
                'An error occurred while starting debate',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get meeting recordings
     */
    public function getRecordings(int $debateId)
    {
        try {
            $debate = Debate::findOrFail($debateId);

            if (!$debate->meeting_id) {
                return $this->errorResponse(
                    'No meeting found for this debate',
                    null,
                    ['error' => 'Meeting not found'],
                    404
                );
            }

            $result = $this->zoomService->getMeetingRecording($debate->meeting_id);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to get recordings',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            return $this->successResponse(
                'Recordings retrieved successfully',
                $result['data']
            );
        } catch (\Exception $e) {
            Log::error('Error getting recordings', [
                'error' => $e->getMessage(),
                'debate_id' => $debateId
            ]);

            return $this->errorResponse(
                'An error occurred while getting recordings',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Check and create meetings for upcoming debates
     */
    public function checkUpcomingMeetings()
    {
        try {
            $results = $this->zoomService->checkAndCreateUpcomingMeetings();

            return $this->successResponse(
                'Checked upcoming meetings',
                $results
            );
        } catch (\Exception $e) {
            Log::error('Error checking upcoming meetings', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while checking upcoming meetings',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
