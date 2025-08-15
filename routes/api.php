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
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\LiveController;

Route::controller(AuthController::class)->group(function () {
    Route::post('register/{actor}', 'register')->where('actor', 'user|debater|judge|coach|admin');
    Route::post('login', 'login');
    Route::get('logout', 'logout');
    Route::get('refresh', 'refresh');
    Route::get('profile', 'profile');
    Route::post('upload/image', 'uploadImage');
    Route::delete('destroy/image', 'destroyImage');
});

Route::post('documentation')->withoutMiddleware('');
Route::post('debate/create', [DebateController::class, 'create']);
Route::get('motion/classification', [SubClassificationController::class, 'index']);
Route::get('motion/get', [MotionController::class, 'index']);
Route::post('motion/create', [MotionController::class, 'create']);
Route::patch('motion/update', [MotionController::class, 'update']);
Route::delete('motion/delete/{motionId}', [MotionController::class, 'delete']);
Route::get('debates/index', [DebateController::class, 'index']);
Route::get('data/education', [FacultyController::class, 'index']);
Route::post('register-application/debates/{debate}',[ApplicationController::class,'request']);
//debater
Route::post('/debates/{debate}/participate',[DebaterController::class,'participate']);
//admin
    //this one needs to be changed alot
Route::post('/debates/{debate}/assign-teams', [AdminController::class, 'assignTeams']);

Route::controller(LiveController::class)->group(function () {
    Route::get('live/test','testing');
    Route::post('webhook','webhook');
});


Route::get('this/test', function (){
    return 'this is test';
});
Route::post('test', [AuthController::class, 'test']);

Route::post('this/post',function() {
    return 'this is post';
});
