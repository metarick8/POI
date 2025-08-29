<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\Judge;
use App\Traits\UseZoom;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZoomService
{
    use UseZoom;

    /**
     * Create a Zoom meeting for a debate
     */
    public function createDebateMeeting(Debate $debate): array
    {
        DB::beginTransaction();

        try {
            Log::info('Creating Zoom meeting for debate', ['debate_id' => $debate->id]);

            // Validate debate is ready for Zoom meeting
            if ($debate->type !== 'online') {
                throw new Exception('Debate must be online type to create Zoom meeting');
            }

            if (!$debate->chairJudge) {
                throw new Exception('No chair judge assigned to debate');
            }

            if (!$debate->chairJudge->zoom_id) {
                throw new Exception('Chair judge does not have Zoom account linked');
            }

            // Check if meeting already exists
            if ($debate->meeting_id) {
                Log::warning('Zoom meeting already exists for debate', [
                    'debate_id' => $debate->id,
                    'meeting_id' => $debate->meeting_id
                ]);
                return ['success' => false, 'error' => 'Meeting already exists'];
            }

            $motionTitle = $debate->motion ? $debate->motion->sentence : 'Untitled Debate';
            $meetingData = [
                'topic' => "Debate: {$motionTitle}",
                'start_date' => $debate->start_date,
                'start_time' => $debate->start_time,
                'duration' => 120, // 2 hours for debate
                'agenda' => $debate->summary ?? 'Formal Debate Session',
                'timezone' => config('app.timezone', 'UTC'),
            ];

            $result = $this->createMeeting($debate->chairJudge->zoom_id, $meetingData);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to create Zoom meeting');
            }

            $meetingInfo = $result['data'];

            // Update debate with Zoom meeting info
            $debate->update([
                'meeting_id' => $meetingInfo['id'],
                'start_url' => $meetingInfo['start_url'],
                'join_url' => $meetingInfo['join_url'],
                'password' => $meetingInfo['password'],
            ]);

            Log::info('Zoom meeting created successfully', [
                'debate_id' => $debate->id,
                'meeting_id' => $meetingInfo['id']
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $meetingInfo,
                'message' => 'Zoom meeting created successfully'
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create Zoom meeting', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check and create meetings for debates starting soon
     */
    public function checkAndCreateUpcomingMeetings(): array
    {
        $now = Carbon::now();
        $results = [];

        // Get debates that should start in 15 minutes and need Zoom meetings
        $eligibleDebates = Debate::where('type', 'online')
            ->where('status', 'teamsConfirmed')
            ->whereNull('meeting_id')
            ->whereDate('start_date', $now->toDateString())
            ->get()
            ->filter(function ($debate) use ($now) {
                $startDateTime = Carbon::parse($debate->start_date . ' ' . $debate->start_time);
                $minutesUntilStart = $now->diffInMinutes($startDateTime, false);
                return $minutesUntilStart <= 15 && $minutesUntilStart > 0;
            });

        foreach ($eligibleDebates as $debate) {
            $result = $this->createDebateMeeting($debate);

            if ($result['success']) {
                // Update debate status to preparation
                $debate->update(['status' => 'debatePreparation']);
                Log::info('Debate moved to preparation phase', ['debate_id' => $debate->id]);
            }

            $results[] = [
                'debate_id' => $debate->id,
                'success' => $result['success'],
                'message' => $result['success'] ? 'Meeting created' : $result['error']
            ];
        }

        return $results;
    }

    /**
     * Link a judge to Zoom
     */
    public function linkJudgeToZoom(int $judgeId, string $email)
    {
        try {
            $judge = Judge::findOrFail($judgeId);

            if ($judge->zoom_id) {
                return [
                    'success' => false,
                    'message' => 'Judge already linked to Zoom'
                ];
            }
            return $result = $this->linkZoomToJudge($judgeId, $email);

            Log::info('Judge linked to Zoom', [
                'judge_id' => $judgeId,
                'email' => $email,
                'success' => isset($result['message'])
            ]);

            return [
                'success' => true,
                'message' => $result['message']
            ];

        } catch (Exception $e) {
            Log::error('Failed to link judge to Zoom', [
                'judge_id' => $judgeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Zoom meeting recording
     */
    public function getMeetingRecording(string $meetingId): array
    {
        try {
            $accessToken = $this->generateZoomAccessToken();

            if (!$accessToken) {
                throw new Exception('Failed to generate access token');
            }

            $url = env('ZOOM_BASE_URL') . "/meetings/$meetingId/recordings";

            $response = Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                throw new Exception('Failed to get meeting recordings: ' . $response->body());
            }

            $recordings = $response->json();

            Log::info('Retrieved Zoom meeting recordings', [
                'meeting_id' => $meetingId,
                'recording_count' => count($recordings['recording_files'] ?? [])
            ]);

            return [
                'success' => true,
                'data' => $recordings
            ];

        } catch (Exception $e) {
            Log::error('Failed to get meeting recording', [
                'meeting_id' => $meetingId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update debate status to ongoing when meeting starts
     */
    public function startDebate(Debate $debate): array
    {
        try {
            if ($debate->status !== 'debatePreparation') {
                throw new Exception('Debate must be in preparation status to start');
            }

            $debate->update(['status' => 'ongoing']);

            Log::info('Debate started', ['debate_id' => $debate->id]);

            return [
                'success' => true,
                'message' => 'Debate started successfully'
            ];

        } catch (Exception $e) {
            Log::error('Failed to start debate', [
                'debate_id' => $debate->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
