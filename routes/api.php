<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\Debate\ApplicationController;
use App\Http\Controllers\Debate\TeamController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\DebaterController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\MotionController;
use App\Http\Controllers\SubClassificationController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\UniversityController;
use App\Http\Middleware\Auth\AuthenticateAdmin;
use App\Http\Middleware\Auth\AuthenticateDebater;
use App\Http\Middleware\Auth\AuthenticateJudge;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('register/{actor}', 'register')->where('actor', 'user|debater|judge|coach')->middleware(AuthenticateAdmin::class);
    Route::post('login', 'login');
    Route::get('logout', 'logout');
    Route::get('refresh', 'refresh');
    Route::get('profile', 'profile')->middleware(JwtMiddleware::class);
    Route::post('upload/image', 'uploadImage');
    Route::delete('destroy/image', 'destroyImage');
    Route::get('user/ban/{userId}', 'ban'); // Consider moving to admin middleware
    Route::get('user/index', 'index'); // Consider moving to admin middleware
    Route::get('user/{userId}', 'show')->middleware(AuthenticateAdmin::class);
});

Route::get('universities/list', [UniversityController::class, 'list']);
Route::post('documentation')->withoutMiddleware(''); // Verify this route's purpose
Route::get('motion/classification', [SubClassificationController::class, 'index']);
Route::get('motion/get', [MotionController::class, 'index']);
Route::post('motion/create', [MotionController::class, 'create']);
Route::patch('motion/update', [MotionController::class, 'update']);
Route::delete('motion/delete/{motionId}', [MotionController::class, 'delete']);
Route::get('data/education', [FacultyController::class, 'index']);
Route::post('register-application/debates/{debate}', [ApplicationController::class, 'request']);
Route::get('coach/index', [CoachController::class, 'index']);
Route::post('debates/{debate}/assign-teams', [AdminController::class, 'assignTeams']);
Route::get('debate/apply/{debateId}', [ApplicationController::class, 'apply']);
Route::post('test', [AuthController::class, 'test']);

// Admin routes
Route::middleware([AuthenticateAdmin::class])->group(function () {
    Route::get('debates/applications', [ApplicationController::class, 'index']); // Changed to GET and moved here
    Route::post('debates/applications/respond', [ApplicationController::class, 'respond']);
    Route::post('debates/applications/teams', [TeamController::class, 'selectTeams']);
    Route::get('debates/{debate}/teams/index', [TeamController::class, 'listTeams']);
});

// Debate-related routes
Route::prefix('debates')->group(function () {
    Route::get('/', [DebateController::class, 'indexForAdmin'])->middleware(AuthenticateAdmin::class);
    Route::post('/', [DebateController::class, 'create'])->middleware(AuthenticateAdmin::class);
    Route::get('{debate}', [DebateController::class, 'show']);
    Route::post('{debate}/applications/apply-judge', [ApplicationController::class, 'applyJudge'])->middleware(AuthenticateJudge::class);
    Route::post('{debate}/applications/apply-debater', [ApplicationController::class, 'applyDebater'])->middleware(AuthenticateDebater::class);
    Route::post('{debate}/result', [DebateController::class, 'result']); // Fixed from previous issue
    // Uncomment and adjust these routes as needed
    // Route::patch('{debate}/status', [DebateController::class, 'updateStatus'])->middleware('auth.admin');
    // Route::patch('{debate}/cancel', [DebateController::class, 'cancel'])->middleware('auth.admin');
    // Route::patch('{debate}/bugged', [DebateController::class, 'markAsBugged'])->middleware('auth.admin');
    // Route::patch('{debate}/finish', [DebateController::class, 'finish'])->middleware('auth.admin');
});

Route::controller(LiveController::class)->group(function () {
    Route::get('live/test', 'testing');
    Route::post('webhook', 'webhook');
});

Route::get('this/test', function () {
    return 'this is test';
});
