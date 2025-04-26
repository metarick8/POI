<?php

namespace App\Http\Controllers;

use App\Http\Requests\DebateInitializeRequest;
use App\Services\DebateService;
use Illuminate\Http\Request;

class DebateController extends Controller
{
    protected $debabteService;
    public function __construct(DebateService $debateService)
    {
        $this->debabteService = $debateService;
    }

    public function create(DebateInitializeRequest $request)
    {
        $this->debabteService->create($request);
    }
}
