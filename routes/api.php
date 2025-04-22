<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoachController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::controller(AuthController::class)->group(function () {
    Route::post('register/{actor}', 'register')->where('actor', 'user|debater|judge|coach|admin');
    Route::post('login', 'login');
    Route::get('logout', 'logout');
    Route::get('refresh', 'refresh');
    Route::get('profile', 'profile');
});
    Route::post('/documentation')->withoutMiddleware('');
    // Route::post('/register', function (Request $request) {
    //     return response()->json(['message' => 'Test successful']);
    // });
