<?php

namespace App\Http\Controllers;

use App\JSONResponseTrait;
use App\Models\Debate;
use App\Services\CloudinaryService;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class RecordingController extends Controller
{
    use JSONResponseTrait;

    protected $cloudinaryService;
    protected $zoomService;

    public function __construct(CloudinaryService $cloudinaryService, ZoomService $zoomService)
    {
        $this->cloudinaryService = $cloudinaryService;
        $this->zoomService = $zoomService;
    }

    /**
     * Upload recording file to Cloudinary (chair judge only)
     */
    public function uploadRecording(Request $request, int $debateId)
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
                    'Only the chair judge can upload recordings',
                    null,
                    ['error' => 'Access denied'],
                    403
                );
            }

            // Validate debate is finished
            if ($debate->status !== 'finished') {
                return $this->errorResponse(
                    'Can only upload recordings for finished debates',
                    null,
                    ['error' => 'Invalid debate status'],
                    422
                );
            }

            // Validate file
            $validated = $request->validate([
                'recording' => 'required|file|mimes:mp4,mov,avi,wmv,flv,webm|max:1048576', // Max 1GB
            ]);

            $file = $validated['recording'];

            // Upload to Cloudinary
            $result = $this->cloudinaryService->uploadRecording($file, [
                'public_id' => "debate_{$debateId}_" . time(),
                'tags' => ['debate_recording', "debate_$debateId"],
            ]);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to upload recording',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            // Store reference in database
            $storeResult = $this->cloudinaryService->storeRecordingReference(
                $debateId,
                $result['data']
            );

            if (!$storeResult['success']) {
                // Try to delete the uploaded file since database storage failed
                $this->cloudinaryService->deleteRecording($result['data']['public_id']);
                
                return $this->errorResponse(
                    'Failed to store recording reference',
                    null,
                    ['error' => $storeResult['error']],
                    500
                );
            }

            Log::info('Recording uploaded successfully', [
                'debate_id' => $debateId,
                'user_id' => $user->id,
                'public_id' => $result['data']['public_id']
            ]);

            return $this->successResponse(
                'Recording uploaded successfully',
                [
                    'public_id' => $result['data']['public_id'],
                    'url' => $result['data']['secure_url'],
                    'duration' => $result['data']['duration'],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error uploading recording', [
                'debate_id' => $debateId,
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return $this->errorResponse(
                'An error occurred while uploading recording',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Store Zoom recording link (chair judge only)
     */
    public function storeZoomLink(Request $request, int $debateId)
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
                    'Only the chair judge can store recording links',
                    null,
                    ['error' => 'Access denied'],
                    403
                );
            }

            // Validate debate is finished
            if ($debate->status !== 'finished') {
                return $this->errorResponse(
                    'Can only store recording links for finished debates',
                    null,
                    ['error' => 'Invalid debate status'],
                    422
                );
            }

            $validated = $request->validate([
                'zoom_recording_url' => 'required|url',
            ]);

            // Update debate with Zoom recording URL
            $debate->update([
                'recording_type' => 'zoom_link',
                'zoom_recording_url' => $validated['zoom_recording_url'],
                'recording_uploaded_at' => now(),
            ]);

            Log::info('Zoom recording link stored', [
                'debate_id' => $debateId,
                'user_id' => $user->id
            ]);

            return $this->successResponse(
                'Zoom recording link stored successfully',
                null
            );

        } catch (\Exception $e) {
            Log::error('Error storing Zoom recording link', [
                'debate_id' => $debateId,
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return $this->errorResponse(
                'An error occurred while storing recording link',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get recording for admin access
     */
    public function getRecording(int $debateId)
    {
        try {
            // This endpoint should be protected by admin middleware
            $debate = Debate::findOrFail($debateId);

            if (!$debate->hasRecording()) {
                return $this->errorResponse(
                    'No recording available for this debate',
                    null,
                    ['error' => 'Recording not found'],
                    404
                );
            }

            $recordingData = [];

            if ($debate->recording_type === 'zoom_link' && $debate->zoom_recording_url) {
                $recordingData = [
                    'type' => 'zoom_link',
                    'url' => $debate->zoom_recording_url,
                    'uploaded_at' => $debate->recording_uploaded_at,
                ];
            } elseif ($debate->recording_type === 'cloudinary_upload' && $debate->cloudinary_recording_id) {
                // Generate signed URL for admin access
                $signedUrlResult = $this->cloudinaryService->generateSignedUrl(
                    $debate->cloudinary_recording_id,
                    3600 // 1 hour access
                );

                if (!$signedUrlResult['success']) {
                    return $this->errorResponse(
                        'Failed to generate access URL',
                        null,
                        ['error' => $signedUrlResult['error']],
                        500
                    );
                }

                $recordingData = [
                    'type' => 'cloudinary_upload',
                    'url' => $signedUrlResult['data']['url'],
                    'expires_at' => $signedUrlResult['data']['expires_at'],
                    'uploaded_at' => $debate->recording_uploaded_at,
                ];
            }

            return $this->successResponse(
                'Recording retrieved successfully',
                $recordingData
            );

        } catch (\Exception $e) {
            Log::error('Error getting recording', [
                'debate_id' => $debateId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving recording',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Delete recording (admin only)
     */
    public function deleteRecording(int $debateId)
    {
        try {
            $debate = Debate::findOrFail($debateId);

            if (!$debate->hasRecording()) {
                return $this->errorResponse(
                    'No recording to delete',
                    null,
                    ['error' => 'Recording not found'],
                    404
                );
            }

            // Delete from Cloudinary if it's a Cloudinary upload
            if ($debate->recording_type === 'cloudinary_upload' && $debate->cloudinary_recording_id) {
                $deleteResult = $this->cloudinaryService->deleteRecording($debate->cloudinary_recording_id);
                
                if (!$deleteResult['success']) {
                    Log::warning('Failed to delete from Cloudinary', [
                        'debate_id' => $debateId,
                        'public_id' => $debate->cloudinary_recording_id,
                        'error' => $deleteResult['error']
                    ]);
                    // Continue with database cleanup even if Cloudinary deletion fails
                }
            }

            // Clear recording data from database
            $debate->update([
                'recording_type' => null,
                'zoom_recording_url' => null,
                'cloudinary_recording_id' => null,
                'cloudinary_recording_url' => null,
                'recording_uploaded_at' => null,
            ]);

            Log::info('Recording deleted', ['debate_id' => $debateId]);

            return $this->successResponse(
                'Recording deleted successfully',
                null
            );

        } catch (\Exception $e) {
            Log::error('Error deleting recording', [
                'debate_id' => $debateId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while deleting recording',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}