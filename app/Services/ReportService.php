<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Debate;
use App\Models\User;
use App\Models\ParticipantsDebater;
use App\Models\Participants_panelist_judge;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReportService
{
    /**
     * Create a new report
     */
    public function createReport(array $data): array
    {
        DB::beginTransaction();
        
        try {
            Log::info('Creating new report', [
                'debate_id' => $data['debate_id'],
                'reporter_id' => $data['reporter_id'],
                'issue_type' => $data['issue_type']
            ]);

            // Validate that the reporter was actually in the debate
            if (!$this->validateReporterParticipation($data['debate_id'], $data['reporter_id'], $data['reporter_type'])) {
                throw new Exception('Reporter was not a participant in this debate');
            }

            $report = Report::create([
                'debate_id' => $data['debate_id'],
                'reporter_id' => $data['reporter_id'],
                'reporter_type' => $data['reporter_type'],
                'issue_type' => $data['issue_type'],
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'pending',
            ]);

            Log::info('Report created successfully', [
                'report_id' => $report->id,
                'debate_id' => $data['debate_id']
            ]);

            DB::commit();
            
            return [
                'success' => true,
                'data' => $report,
                'message' => 'Report submitted successfully'
            ];
            
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create report', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all reports for admin
     */
    public function getReportsForAdmin(array $filters = []): Collection
    {
        $query = Report::with(['debate.motion', 'reporter:id,name', 'admin:id,name'])
                      ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['issue_type'])) {
            $query->where('issue_type', $filters['issue_type']);
        }

        if (isset($filters['debate_id'])) {
            $query->where('debate_id', $filters['debate_id']);
        }

        return $query->get();
    }

    /**
     * Get reports by user
     */
    public function getReportsByUser(int $userId): Collection
    {
        return Report::with(['debate.motion', 'admin:id,name'])
                    ->where('reporter_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Handle report by admin
     */
    public function handleReport(int $reportId, array $data): array
    {
        DB::beginTransaction();
        
        try {
            $report = Report::findOrFail($reportId);
            
            if ($report->status === 'resolved' || $report->status === 'dismissed') {
                throw new Exception('Report has already been handled');
            }

            // Mark as under review first
            if ($report->status === 'pending') {
                $report->markAsUnderReview($data['admin_id']);
            }

            $action = $data['action'] ?? 'none';
            $response = $data['response'] ?? null;

            // Handle different actions
            if ($action === 'ban') {
                $this->handleBanAction($report->reporter_id, $data['ban_duration'] ?? null);
                Log::info('User banned as result of report', [
                    'report_id' => $reportId,
                    'user_id' => $report->reporter_id
                ]);
            }

            // Resolve or dismiss the report
            if ($data['resolution'] === 'resolve') {
                $report->resolve($data['admin_id'], $response, $action);
                $message = 'Report resolved successfully';
            } else {
                $report->dismiss($data['admin_id'], $response);
                $message = 'Report dismissed successfully';
            }

            Log::info('Report handled', [
                'report_id' => $reportId,
                'action' => $action,
                'resolution' => $data['resolution']
            ]);

            DB::commit();
            
            return [
                'success' => true,
                'data' => $report->fresh(),
                'message' => $message
            ];
            
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to handle report', [
                'report_id' => $reportId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate that the reporter was actually in the debate
     */
    private function validateReporterParticipation(int $debateId, int $reporterId, string $reporterType): bool
    {
        if ($reporterType === 'debater') {
            return ParticipantsDebater::where('debate_id', $debateId)
                                    ->where('debater_id', $reporterId)
                                    ->exists();
        } 
        
        if ($reporterType === 'judge') {
            // Check if chair judge
            $debate = Debate::find($debateId);
            if ($debate && $debate->chairJudge && $debate->chairJudge->user_id === $reporterId) {
                return true;
            }

            // Check if panelist judge
            return Participants_panelist_judge::where('debate_id', $debateId)
                                             ->whereHas('judge', function($query) use ($reporterId) {
                                                 $query->where('user_id', $reporterId);
                                             })
                                             ->exists();
        }

        return false;
    }

    /**
     * Handle banning a user
     */
    private function handleBanAction(int $userId, ?int $duration = null): void
    {
        $user = User::findOrFail($userId);
        
        // Set ban_status and ban expiry if provided
        $banData = ['ban_status' => true];
        
        if ($duration) {
            $banData['ban_expires_at'] = now()->addDays($duration);
        }
        
        $user->update($banData);
        
        Log::info('User banned', [
            'user_id' => $userId,
            'duration' => $duration,
            'expires_at' => $banData['ban_expires_at'] ?? 'permanent'
        ]);
    }

    /**
     * Get report statistics for admin dashboard
     */
    public function getReportStatistics(): array
    {
        return [
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'under_review' => Report::where('status', 'under_review')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
            'by_type' => [
                'technical' => Report::where('issue_type', 'technical')->count(),
                'behavioral' => Report::where('issue_type', 'behavioral')->count(),
                'procedural' => Report::where('issue_type', 'procedural')->count(),
                'other' => Report::where('issue_type', 'other')->count(),
            ]
        ];
    }
}