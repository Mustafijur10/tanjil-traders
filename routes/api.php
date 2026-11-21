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
Route::get('/home', Store\HomeController::class);
Route::get('/pages/{slug}', [Store\PageController::class, 'show']);
Route::post('/contact', [Store\PageController::class, 'contact'])->middleware('throttle:5,10');

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
    Route::get('/dashboard/kpis', [Admin\DashboardController::class, 'kpis']);

    // Catalog
    Route::post('/categories/reorder', [Admin\CategoryController::class, 'reorder']);
    Route::apiResource('categories', Admin\CategoryController::class)->except('show');
    Route::post('/brands/reorder', [Admin\BrandController::class, 'reorder']);
    Route::apiResource('brands', Admin\BrandController::class)->except('show');
    Route::apiResource('attributes', Admin\AttributeController::class)->except('show');

    Route::get('/products/form-data', [Admin\ProductController::class, 'formData']);
    Route::post('/products/bulk', [Admin\ProductController::class, 'bulk']);
    Route::post('/products/{product}/duplicate', [Admin\ProductController::class, 'duplicate']);
    Route::apiResource('products', Admin\ProductController::class);

    Route::apiResource('reviews', Admin\ReviewController::class)->only(['index', 'update', 'destroy']);

    // Website content
    Route::post('/banners/reorder', [Admin\BannerController::class, 'reorder']);
    Route::apiResource('banners', Admin\BannerController::class)->except('show');
    Route::post('/home-sections/reorder', [Admin\HomeSectionController::class, 'reorder']);
    Route::apiResource('home-sections', Admin\HomeSectionController::class)->except('show');
    Route::apiResource('pages', Admin\PageController::class);
    Route::apiResource('messages', Admin\ContactMessageController::class)->only(['index', 'update', 'destroy'])
        ->parameters(['messages' => 'contactMessage']);

    Route::get('/translations', [Admin\TranslationController::class, 'index']);
    Route::put('/translations/{translation}', [Admin\TranslationController::class, 'update']);
    Route::delete('/translations/{translation}', [Admin\TranslationController::class, 'destroy']);
    Route::post('/translations/approve', [Admin\TranslationController::class, 'approve']);
    Route::post('/translations/register', [Admin\TranslationController::class, 'register']);
    Route::post('/translations/scan', [Admin\TranslationController::class, 'scan']);
    Route::post('/translations/auto', [Admin\TranslationController::class, 'autoTranslate']);

    // Store-wide settings: admins only
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [Admin\SettingController::class, 'index']);
        Route::put('/settings', [Admin\SettingController::class, 'update']);
    });
});
