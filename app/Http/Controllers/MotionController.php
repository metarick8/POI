<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotionRequest;
use App\Http\Resources\MotionResource;
use App\JSONResponseTrait;
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
        $created = $this->motionService->create($request);
        if ($created)
            return $this->successResponse("Motion created successfully!", '');
    }

    public function update(MotionRequest $request)
    {
        $updated = $this->motionService->patch($request);
        if ($updated)
            return $this->successResponse("Motion updated successfully!", '');
    }

    public function delete($motionId)
    {
        return $deleted = $this->motionService->delete($motionId);
        if ($deleted)
            return $this->successResponse("Motion deleted successfully!", '');
    }
}
