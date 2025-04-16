<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');


});
// Route::middleware('api')->group(function () {
//     Route::post('/api/register', function (Request $request) {
//         return response()->json(['message' => 'Test successful']);
//     });
// });
