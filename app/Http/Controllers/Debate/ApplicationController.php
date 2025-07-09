<?php

namespace App\Http\Controllers\Debate;

use App\Http\Controllers\Controller;
use App\JSONResponseTrait;
use App\Models\Debate;
use App\Services\Debate\ApplicationService;

class ApplicationController extends Controller
{
    use JSONResponseTrait;
    protected $applicationService;
    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }
    public function request(Debate $debate)
    {
        $this->applicationService->request($debate);
        return $this->successResponse('Player applied!', '');
    }
}
