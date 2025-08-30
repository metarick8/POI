<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait UseZoom
{
    const MEETING_TYPE_SCHEDULE = 2;

    protected function generateZoomAccessToken()
    {
        $clientId = env('ZOOM_CLIENT_ID');
        $clientSecret = env('ZOOM_CLIENT_SECRET');
        $accountId = env('ZOOM_ACCOUNT_ID');

        $base64Credentials = base64_encode("$clientId:$clientSecret");

        return $response = Http::withHeaders([
            'Authorization' => "Basic $base64Credentials",
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->post('https://zoom.us/oauth/token', [
            'grant_type' => 'account_credentials',
            'account_id' => $accountId,
        ]);

        if ($response->failed()) {
            Log::error('Zoom OAuth Token Error: ' . $response->body());
            return null;
        }

        return $response->json()['access_token'];
    }

    protected function toZoomTimeFormat($date, $time, $timezone = null)
    {
        try {
            $dateTime = new \DateTime("$date $time", new \DateTimeZone($timezone ?? env('ZOOM_TIMEZONE')));
            $dateTime->setTimezone(new \DateTimeZone('UTC')); // Zoom expects UTC
            return $dateTime->format('Y-m-d\TH:i:s\Z');
        } catch (\Exception $e) {
            Log::error('Zoom Time Format Error: ' . $e->getMessage());
            return null;
        }
    }

    public function createMeeting($zoomUserId, $data)
    {
        return $accessToken = $this->generateZoomAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }

        $url = env('ZOOM_BASE_URL') . "/users/$zoomUserId/meetings";

        $meetingData = [
            'topic' => $data['topic'] ?? 'Online Debate',
            'type' => self::MEETING_TYPE_SCHEDULE,
            'start_time' => $this->toZoomTimeFormat($data['start_date'], $data['start_time'], $data['timezone'] ?? env('ZOOM_TIMEZONE')),
            'duration' => $data['duration'] ?? 90, // Default 90 minutes
            'timezone' => $data['timezone'] ?? env('ZOOM_TIMEZONE'),
            'password' => $data['password'] ?? \Illuminate\Support\Str::random(8),
            'agenda' => $data['agenda'] ?? 'Debate Session',
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => true,
                'waiting_room' => true,
                'approval_type' => 2, // No registration
                'audio' => 'both',
                'auto_recording' => 'cloud', // Enable cloud recording for debates
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Content-Type' => 'application/json',
        ])->post($url, $meetingData);

        if ($response->failed()) {
            Log::error('Zoom Create Meeting Error: ' . $response->body());
            return ['success' => false, 'error' => 'Failed to create meeting', 'details' => $response->json()];
        }

        return ['success' => true, 'data' => $response->json()];
    }

    public function linkZoomToJudge($judgeId, $email)
    {
        $judge = \App\Models\Judge::findOrFail($judgeId);

        if ($judge->zoom_id) {
            return ['message' => 'Judge already linked to Zoom.'];
        }

        $accessToken = $this->generateZoomAccessToken();
        if (!$accessToken) {
            return ['message' => 'Failed to get access token'];
        }

        $url = env('ZOOM_BASE_URL') . '/users';

        $body = [
            'action' => 'create',
            'user_info' => [
                'email' => $email,
                'first_name' => $judge->name ?? 'Judge',
                'type' => 1, // Basic user
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Content-Type' => 'application/json',
        ])->post($url, $body);
        if ($response->failed()) {
            Log::error('Zoom Create User Error: ' . $response->body());
            return ['message' => $response->json()['message'] ?? 'Failed to link (email may already exist).'];
        }

        $data = $response->json();
        $judge->zoom_id = $data['id'];
        $judge->zoom_email = $email;
        $judge->save();

        return ['message' => 'Judge linked to Zoom. Check email for invitation.'];
    }
}
