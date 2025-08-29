<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\ParticipantsDebater;
use App\Models\Participants_panelist_judge;
use App\Models\Rate;
use App\Models\Judge;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RateService
{
    /**
     * Rate a judge (chair or panelist) after debate is finished
     */
    public function rateJudge(int $debaterId, int $judgeId, int $debateId, int $rate, ?string $opinion = null): array
    {
        DB::beginTransaction();
        
        try {
            Log::info('Attempting to rate judge', [
                'debater_id' => $debaterId,
                'judge_id' => $judgeId,
                'debate_id' => $debateId,
                'rate' => $rate
            ]);

            // Validate debate exists and is finished
            $debate = Debate::find($debateId);
            if (!$debate) {
                throw new Exception("Debate not found");
            }

            if ($debate->status !== 'finished') {
                throw new Exception("You cannot rate until the debate is finished");
            }

            // Validate debater participated in this debate
            $participantDebater = ParticipantsDebater::where('debate_id', $debateId)
                                                   ->where('debater_id', $debaterId)
                                                   ->first();

            if (!$participantDebater) {
                throw new Exception("You are not authorized to rate this debate");
            }

            // Validate judge was involved in this debate (chair or panelist)
            if (!$this->wasJudgeInDebate($debate, $judgeId)) {
                throw new Exception("Judge was not involved in this debate");
            }

            // Check if rating already exists
            if (Rate::where('participant_debater_id', $participantDebater->id)
                   ->where('judge_id', $judgeId)
                   ->exists()) {
                throw new Exception("You have already rated this judge for this debate");
            }

            // Validate rating value
            if ($rate < 1 || $rate > 5) {
                throw new Exception("Rating must be between 1 and 5");
            }

            $rating = Rate::create([
                'participant_debater_id' => $participantDebater->id,
                'judge_id' => $judgeId,
                'rate' => $rate,
                'opinion' => $opinion,
            ]);

            Log::info('Judge rated successfully', [
                'rating_id' => $rating->id,
                'debater_id' => $debaterId,
                'judge_id' => $judgeId,
                'rate' => $rate
            ]);

            DB::commit();
            
            return [
                'success' => true,
                'data' => $rating,
                'message' => 'Judge rated successfully'
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to rate judge', [
                'error' => $e->getMessage(),
                'debater_id' => $debaterId,
                'judge_id' => $judgeId,
                'debate_id' => $debateId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all ratings for a specific judge (admin only)
     */
    public function getJudgeRatings(int $judgeId): array
    {
        try {
            $ratings = Rate::with([
                'participantDebater.debaterUser:id,name',
                'participantDebater.debate:id,start_date,winner'
            ])
            ->where('judge_id', $judgeId)
            ->orderBy('created_at', 'desc')
            ->get();

            $averageRating = $ratings->avg('rate');
            $totalRatings = $ratings->count();

            $ratingDistribution = $ratings->groupBy('rate')->map->count();

            return [
                'success' => true,
                'data' => [
                    'judge_id' => $judgeId,
                    'ratings' => $ratings,
                    'statistics' => [
                        'average_rating' => round($averageRating, 2),
                        'total_ratings' => $totalRatings,
                        'distribution' => $ratingDistribution
                    ]
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to get judge ratings', [
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
     * Get ratings for a specific debate (admin only)
     */
    public function getDebateRatings(int $debateId): array
    {
        try {
            $debate = Debate::with(['chairJudge.user:id,name'])->findOrFail($debateId);
            
            // Get ratings for all judges in this debate
            $judgeIds = [$debate->chair_judge_id];
            
            // Add panelist judges
            $panelistJudges = Participants_panelist_judge::where('debate_id', $debateId)->pluck('judge_id');
            $judgeIds = array_merge($judgeIds, $panelistJudges->toArray());

            $ratings = Rate::with([
                'participantDebater.debaterUser:id,name'
            ])
            ->whereHas('participantDebater', function($query) use ($debateId) {
                $query->where('debate_id', $debateId);
            })
            ->whereIn('judge_id', array_filter($judgeIds))
            ->get()
            ->groupBy('judge_id');

            $judgeRatings = [];
            foreach ($judgeIds as $judgeId) {
                if (!$judgeId) continue;
                
                $judgeRatings[$judgeId] = [
                    'judge_id' => $judgeId,
                    'ratings' => $ratings->get($judgeId, collect()),
                    'average_rating' => $ratings->has($judgeId) ?
                        round($ratings->get($judgeId)->avg('rate'), 2) : null,
                    'total_ratings' => $ratings->has($judgeId) ?
                        $ratings->get($judgeId)->count() : 0
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'debate_id' => $debateId,
                    'judge_ratings' => $judgeRatings
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to get debate ratings', [
                'debate_id' => $debateId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get rating statistics for admin dashboard
     */
    public function getRatingStatistics(): array
    {
        try {
            $totalRatings = Rate::count();
            $averageRating = Rate::avg('rate');
            
            $ratingDistribution = Rate::selectRaw('rate, COUNT(*) as count')
                                     ->groupBy('rate')
                                     ->orderBy('rate')
                                     ->pluck('count', 'rate');

            $topRatedJudges = Rate::selectRaw('judge_id, AVG(rate) as average_rating, COUNT(*) as total_ratings')
                                 ->groupBy('judge_id')
                                 ->having('total_ratings', '>=', 5) // At least 5 ratings
                                 ->orderBy('average_rating', 'desc')
                                 ->limit(10)
                                 ->with('judge.user:id,name')
                                 ->get();

            return [
                'success' => true,
                'data' => [
                    'total_ratings' => $totalRatings,
                    'average_rating' => round($averageRating, 2),
                    'rating_distribution' => $ratingDistribution,
                    'top_rated_judges' => $topRatedJudges
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to get rating statistics', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if judge was involved in the debate (chair or panelist)
     */
    private function wasJudgeInDebate(Debate $debate, int $judgeId): bool
    {
        // Check if judge is chair judge
        if ($debate->chair_judge_id === $judgeId) {
            return true;
        }

        // Check if judge is panelist judge
        return Participants_panelist_judge::where('debate_id', $debate->id)
                                         ->where('judge_id', $judgeId)
                                         ->exists();
    }

    /**
     * Check if debater can rate specific judge
     */
    public function canDebaterRateJudge(int $debaterId, int $judgeId, int $debateId): array
    {
        try {
            $debate = Debate::findOrFail($debateId);
            
            // Check if debate is finished
            if ($debate->status !== 'finished') {
                return [
                    'success' => false,
                    'error' => 'Debate must be finished to rate judges'
                ];
            }

            // Check if debater participated
            $participantDebater = ParticipantsDebater::where('debate_id', $debateId)
                                                   ->where('debater_id', $debaterId)
                                                   ->first();

            if (!$participantDebater) {
                return [
                    'success' => false,
                    'error' => 'Debater did not participate in this debate'
                ];
            }

            // Check if judge was involved
            if (!$this->wasJudgeInDebate($debate, $judgeId)) {
                return [
                    'success' => false,
                    'error' => 'Judge was not involved in this debate'
                ];
            }

            // Check if already rated
            if (Rate::where('participant_debater_id', $participantDebater->id)
                   ->where('judge_id', $judgeId)
                   ->exists()) {
                return [
                    'success' => false,
                    'error' => 'Already rated this judge'
                ];
            }

            return [
                'success' => true,
                'message' => 'Can rate this judge'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
