<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Store;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (public)
|--------------------------------------------------------------------------
*/
Route::get('/bootstrap', Store\BootstrapController::class);

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Any signed-in user
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/preferences', [AuthController::class, 'updatePreferences']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Admin panel (admin and staff)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'role:staff'])->group(function () {
    Route::post('/uploads', [Admin\UploadController::class, 'store']);

    Route::get('/translations', [Admin\TranslationController::class, 'index']);
    Route::put('/translations/{translation}', [Admin\TranslationController::class, 'update']);
    Route::delete('/translations/{translation}', [Admin\TranslationController::class, 'destroy']);
    Route::post('/translations/approve', [Admin\TranslationController::class, 'approve']);
    Route::post('/translations/scan', [Admin\TranslationController::class, 'scan']);
    Route::post('/translations/auto', [Admin\TranslationController::class, 'autoTranslate']);

    // Store-wide settings: admins only
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [Admin\SettingController::class, 'index']);
        Route::put('/settings', [Admin\SettingController::class, 'update']);
    });
});
