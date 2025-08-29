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
        $feedback = Feedback::with('participant')->get();
        return response()->json([
            'debate_id' => $debateId,
            'feedbacks' => $feedback
        ]);
    }

    public function getFeedbacksByDebater()
    {
        $debater = Auth::guard('debater')->user();
        if (!$debater) {
            return response()->json([
                'error' => 'Unauthenticated or not a debater'
            ], 401);
        }

        $debaterId = $debater->id;

        $participants = ParticipantsDebater::where('debater_id', $debaterId)->get();

        if ($participants->isEmpty()) {
            return response()->json([
                'message' => 'No participants found for the current debater',
                'debater_id' => $debaterId,
                'feedbacks' => []
            ], 404);
        }

        $feedbacks = Feedback::with('participant')
            ->whereIn('participant_debater_id', $participants->pluck('id'))
            ->get();

        return response()->json([
            'debater_id' => $debaterId,
            'feedbacks' => $feedbacks
        ]);
    }
}
