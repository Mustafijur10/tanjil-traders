<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DemoOrderController;
use App\Http\Controllers\AttributeOptionController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::resource('settings/country', CountryController::class);

Route::get('/nav-categories', [AttributeOptionController::class, 'navCategories']);

Route::middleware('auth:sanctum')->group(function () {
    // Your protected routes here

    Route::post('/logout', [AuthController::class, 'logout']);

   // ── Attribute options (admin) ─────────────────────────────────────────
    Route::get('/attribute-options',       [AttributeOptionController::class, 'index']);
    Route::get('/attribute/makers',        [AttributeOptionController::class, 'makers']);
    Route::get('/attribute/category',      [AttributeOptionController::class, 'categories']);
    Route::post('/save-option',            [AttributeOptionController::class, 'store']);
    Route::put('/attribute-option-update', [AttributeOptionController::class, 'update']);
    Route::post('/remove-option',          [AttributeOptionController::class, 'destroy']);

});
