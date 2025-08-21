<?php

namespace App\Http\Controllers;

use App\Services\LiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveController extends Controller
{
    protected $liveService;
    public function __construct(LiveService $liveService) {
        $this->liveService = $liveService;
    }
    public function testing() {
        return $this->liveService->createToken('the room', 'Omar');
    }
    public function webhook(Request $request) {
        Log::channel('mylog')->info('This is a custom log message.',$request->all());
        return $request->all();
    }
}
