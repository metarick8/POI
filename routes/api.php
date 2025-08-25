<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\Debate\ApplicationController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\DebaterController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\MotionController;
use App\Http\Controllers\SubClassificationController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\UniversityController;
use App\Http\Middleware\Auth\AuthenticateAdmin;
use App\Http\Middleware\Auth\AuthenticateDebater;
use App\Http\Middleware\Auth\AuthenticateJudge;

Route::controller(AuthController::class)->group(function () {
    Route::post('register/{actor}', 'register')->where('actor', 'user|debater|judge|coach|admin');
    Route::post('login', 'login');
    Route::get('logout', 'logout');
    Route::get('refresh', 'refresh');
    Route::get('profile', 'profile')->middleware(JwtMiddleware::class);
    Route::post('upload/image', 'uploadImage');
    Route::delete('destroy/image', 'destroyImage');
    Route::get('user/ban/{userId}', 'ban');
    Route::get('user/index', 'index');
});

Route::get('universities/list', [UniversityController::class, 'list']);
Route::post('documentation')->withoutMiddleware('');

Route::get('motion/classification', [SubClassificationController::class, 'index']);
Route::get('motion/get', [MotionController::class, 'index']);
Route::post('motion/create', [MotionController::class, 'create']);
Route::patch('motion/update', [MotionController::class, 'update']);
Route::delete('motion/delete/{motionId}', [MotionController::class, 'delete']);
Route::get('data/education', [FacultyController::class, 'index']);
Route::post('register-application/debates/{debate}',[ApplicationController::class,'request']);

Route::post('/debates/{debate}/participate',[DebaterController::class,'participate']);
Route::get('/coach/index',[CoachController::class,'index']);


Route::post('/debates/{debate}/assign-teams', [AdminController::class, 'assignTeams']);

Route::controller(LiveController::class)->group(function () {
    Route::get('live/test','testing');
    Route::post('webhook','webhook');
});


Route::get('this/test', function (){
    return 'this is test';
});
Route::get('debate/apply/{debateId}', [ApplicationController::class, 'apply']);
Route::post('test', [AuthController::class, 'test']);

Route::prefix('debates')->middleware(JwtMiddleware::class)->group(function () {
    Route::get('/', [DebateController::class, 'index']);
    Route::get('{debate}', [DebateController::class, 'show']);
    Route::post('/', [DebateController::class, 'create'])->middleware('auth.admin');
    Route::patch('{debate}/status', [DebateController::class, 'updateStatus'])->middleware('role:admin');
    Route::patch('{debate}/cancel', [DebateController::class, 'cancel'])->middleware('role:admin');
    Route::patch('{debate}/bugged', [DebateController::class, 'markAsBugged'])->middleware('role:admin');
    Route::patch('{debate}/finish', [DebateController::class, 'finish'])->middleware('role:admin');
});

Route::post('applications/respond', [ApplicationController::class, 'respond'])->middleware(AuthenticateAdmin::class);
Route::post('debates/{debate}/apply-judge', [ApplicationController::class, 'applyJudge'])->middleware(AuthenticateJudge::class);
Route::post('debates/{debate}/apply-debater', [ApplicationController::class, 'applyDebater'])->middleware(AuthenticateDebater::class);
Route::get('user/{userId}', [AuthController::class, 'show'])->middleware(AuthenticateAdmin::class);

