<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\MotionController;
use App\Http\Controllers\SubClassificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


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
Route::get('debate/index', [DebateController::class, 'index']);
Route::get('data/education', [FacultyController::class, 'index']);
Route::get('this/test', function (){
    return 'this is test';
});

