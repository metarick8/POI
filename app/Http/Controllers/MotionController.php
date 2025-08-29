<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotionRequest;
use App\Http\Resources\MotionResource;
use App\JSONResponseTrait;
use App\Models\Motion;
use App\Services\MotionService;
use Illuminate\Support\Facades\Request;

class MotionController extends Controller
{
    use JSONResponseTrait;
    protected $motionService;

    public function __construct(MotionService $motionService)
    {
        $this->motionService = $motionService;
    }

    public function index()
    {
        $motions = $this->motionService->index();

        if (!$motions || $motions->isEmpty()) {
            return $this->successResponse('Motions:', []);
        }

        request()->request->add(['simple_sub_classification' => true]);

        return $this->successResponse('Motions:', [
            MotionResource::collection($motions)
        ]);
    }

    public function create(MotionRequest $request)
    {
        $result = $this->motionService->create($request);
        if ($result instanceof \App\Models\Motion)
            return $this->successResponse('Motion created successfully!', $result, 201);

        return $this->errorResponse('Failed to create motion', null, [$result->getMessage()], 422);
    }

    public function delete(Motion $motion)
    {
        $result = $this->motionService->delete($motion);

        if ($result === true) {
            return $this->successResponse('Motion deleted successfully!', null);
        }

        return $this->errorResponse('Failed to delete motion', null, ['error' => $result], 422);
    }
}
