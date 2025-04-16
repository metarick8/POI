<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


    Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::get('logout', 'logout');
    Route::get('refresh', 'refresh');
    Route::get('user', 'getUser');
    });

    //Route::post('/register', [AuthController::class, 'register']);
    // Route::post('/register', function (Request $request) {
    //     return response()->json(['message' => 'Test successful']);
    // });
