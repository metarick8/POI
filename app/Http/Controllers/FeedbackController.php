<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\ParticipantsDebater;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class FeedbackController extends Controller
{
    public function addFeedback(Request $request)
    {
        $request->validate([
            'participant_debater_id' => 'required|exists:participants_debaters,id',
            'note' => 'required|string|max:1000',
        ]);

        try {
            $judge = Auth::guard('judge')->user();

            if (!$judge) {
                return response()->json([
                    'error' => 'Current user is not registered as a judge'
                ], 403);
            }

            $participant = ParticipantsDebater::find($request->participant_debater_id);
            if (!$participant) {
                return response()->json([
                    'error' => 'Participant not found'
                ], 404);
            }

            $feedback = Feedback::create([
                'participant_debater_id' => $participant->id,
                'note' => $request->note
            ]);

            return response()->json([
                'message' => 'Feedback added successfully',
                'feedback' => $feedback
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getFeedbacks($debateId)
    {
        // $feedbacks = Feedback::with('participant.user')
        //     ->whereHas('participant', function ($q) use ($debateId) {
        //         $q->where('debate_id', $debateId);
        //     })->get();

        $feedback = Feedback::with('participant')->get();
        return response()->json([
            'debate_id' => $debateId,
            'feedbacks' => $feedback
        ]);
    }
}
