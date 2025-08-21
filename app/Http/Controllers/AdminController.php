<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTeamsRequest;
use App\Services\AdminService;
use App\Models\Debate;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    public function assignTeams(AssignTeamsRequest $request, Debate $debate): JsonResponse
    {
        $assignments = $request->validated()['assignments'];

        $this->adminService->assignTeams($debate, $assignments);

        return response()->json([
            'status' => 'success',
            'message' => 'Teams assigned successfully.',
            'data' => $assignments,
        ]);
    }
}
