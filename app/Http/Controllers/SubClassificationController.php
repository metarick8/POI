<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubClassificationResource;
use App\JSONResponseTrait;
use App\Services\SubClassificationService;
use App\Services\SubClassService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubClassificationController extends Controller
{
    use JSONResponseTrait;
    protected $subClassificationService;

    public function __construct(SubClassificationService $subClassificationService)
    {
        $this->subClassificationService = $subClassificationService;
    }

    public function index()
    {
        $subclassifications = $this->subClassificationService->index();
        return $this->successResponse('Classifications:', [
            SubClassificationResource::collection($subclassifications)
        ]);
    }
}
