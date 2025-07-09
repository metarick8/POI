<?php

namespace App\Http\Controllers;

use App\JSONResponseTrait;
use App\Services\Education\UniversityService;
use Illuminate\Http\Request;

class UniversityController extends Controller
{
    use JSONResponseTrait;
    protected $universityService;

    public function __construct(UniversityService $universityService)
    {
        $this->universityService = $universityService;
    }

    public function list()
    {
        try {
            $universities = $this->universityService->index();
            return $this->successResponse("Education data:", new MobileUserResource($user), 201);
        } catch (\Throwable $t) {
            return $this->errorResponse("Something went wrong!", $t->getMessage());
        }
    }
}
