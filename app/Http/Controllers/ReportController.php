<?php

namespace App\Http\Controllers;

use App\JSONResponseTrait;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReportController extends Controller
{
    use JSONResponseTrait;

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Submit a report (for debaters and judges)
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return $this->errorResponse('Unauthorized', null, ['User not authenticated'], 401);
            }

            $validated = $request->validate([
                'debate_id' => 'required|exists:debates,id',
                'reporter_type' => ['required', Rule::in(['debater', 'judge'])],
                'issue_type' => ['required', Rule::in(['technical', 'behavioral', 'procedural', 'other'])],
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
            ]);

            $validated['reporter_id'] = $user->id;

            $result = $this->reportService->createReport($validated);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to submit report',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            Log::info('Report submitted', [
                'report_id' => $result['data']->id,
                'user_id' => $user->id,
                'debate_id' => $validated['debate_id']
            ]);

            return $this->successResponse(
                $result['message'],
                $result['data']
            );

        } catch (\Exception $e) {
            Log::error('Error submitting report', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return $this->errorResponse(
                'An error occurred while submitting the report',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get all reports for admin
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->validate([
                'status' => ['nullable', Rule::in(['pending', 'under_review', 'resolved', 'dismissed'])],
                'issue_type' => ['nullable', Rule::in(['technical', 'behavioral', 'procedural', 'other'])],
                'debate_id' => 'nullable|exists:debates,id',
            ]);

            $reports = $this->reportService->getReportsForAdmin($filters);

            return $this->successResponse(
                'Reports retrieved successfully',
                $reports
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving reports for admin', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving reports',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get user's own reports
     */
    public function myReports(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return $this->errorResponse('Unauthorized', null, ['User not authenticated'], 401);
            }

            $reports = $this->reportService->getReportsByUser($user->id);

            return $this->successResponse(
                'Your reports retrieved successfully',
                $reports
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving user reports', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving your reports',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Handle a report (admin only)
     */
    public function handle(Request $request, int $reportId)
    {
        try {
            $admin = $request->user(); // Assuming admin authentication middleware is applied
            if (!$admin) {
                return $this->errorResponse('Unauthorized', null, ['Admin not authenticated'], 401);
            }

            $validated = $request->validate([
                'resolution' => ['required', Rule::in(['resolve', 'dismiss'])],
                'response' => 'nullable|string|max:1000',
                'action' => ['nullable', Rule::in(['none', 'warning', 'ban', 'comment'])],
                'ban_duration' => 'nullable|integer|min:1|max:365', // days
            ]);

            $validated['admin_id'] = $admin->id;

            $result = $this->reportService->handleReport($reportId, $validated);

            if (!$result['success']) {
                return $this->errorResponse(
                    'Failed to handle report',
                    null,
                    ['error' => $result['error']],
                    422
                );
            }

            Log::info('Report handled', [
                'report_id' => $reportId,
                'admin_id' => $admin->id,
                'resolution' => $validated['resolution']
            ]);

            return $this->successResponse(
                $result['message'],
                $result['data']
            );

        } catch (\Exception $e) {
            Log::error('Error handling report', [
                'error' => $e->getMessage(),
                'report_id' => $reportId
            ]);

            return $this->errorResponse(
                'An error occurred while handling the report',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get report statistics (admin only)
     */
    public function statistics()
    {
        try {
            $stats = $this->reportService->getReportStatistics();

            return $this->successResponse(
                'Report statistics retrieved successfully',
                $stats
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving report statistics', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving statistics',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Show a specific report (admin only)
     */
    public function show(int $reportId)
    {
        try {
            $report = \App\Models\Report::with(['debate.motion', 'reporter:id,name', 'admin:id,name'])
                                      ->findOrFail($reportId);

            return $this->successResponse(
                'Report retrieved successfully',
                $report
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving report', [
                'error' => $e->getMessage(),
                'report_id' => $reportId
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving the report',
                null,
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}