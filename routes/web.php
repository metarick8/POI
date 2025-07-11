<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return "Response test, byeee!";


});
// Route::middleware('api')->group(function () {
//     Route::post('/api/register', function (Request $request) {
//         return response()->json(['message' => 'Test successful']);
//     });
// });
