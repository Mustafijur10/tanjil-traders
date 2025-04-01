<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DemoOrderController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::resource('demo', DemoOrderController::class);
Route::resource('settings/country', CountryController::class);

Route::middleware('auth:sanctum')->group(function () {
    // Your protected routes here

    Route::post('/logout', [AuthController::class, 'logout']);


    // Route::resource('settings/country', CountryController::class);

    // Route::resource('demo', DemoOrderController::class);
});
