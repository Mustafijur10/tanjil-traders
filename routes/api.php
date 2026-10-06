<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DemoOrderController;
use App\Http\Controllers\AttributeOptionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::resource('settings/country', CountryController::class);

Route::get('/nav-categories', [AttributeOptionController::class, 'navCategories']);

// ── Categories (Public & Storefront) ───────────────────────────────
Route::get('/nav-categories',   [CategoryController::class, 'navCategories']);
Route::get('/categories',       [CategoryController::class, 'index']);
Route::get('/categories/tree',  [CategoryController::class, 'tree']);
Route::get('/categories/{id}',  [CategoryController::class, 'show']);

// ── Brands (Public & Storefront) ──────────────────────────────────
Route::get('/brands',           [BrandController::class, 'index']);
Route::get('/brands/{id}',      [BrandController::class, 'show']);
Route::get('/attribute/brand',  [BrandController::class, 'index']); // backward compatibility

Route::middleware('auth:sanctum')->group(function () {
    // Your protected routes here

    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Categories (Admin mutations) ──────────────────────────────────
    Route::post('/categories',          [CategoryController::class, 'store']);
    Route::put('/categories/{id}',      [CategoryController::class, 'update']);
    Route::delete('/categories/{id}',   [CategoryController::class, 'destroy']);
    Route::post('/categories/bulk',     [CategoryController::class, 'bulkStore']);

    // ── Brands (Admin mutations) ──────────────────────────────────────
    Route::post('/brands',              [BrandController::class, 'store']);
    Route::post('/brands/{id}',         [BrandController::class, 'update']); // for FormData method spoofing
    Route::put('/brands/{id}',          [BrandController::class, 'update']);
    Route::delete('/brands/{id}',       [BrandController::class, 'destroy']);

   // ── Attribute options (admin) ─────────────────────────────────────────
    Route::get('/attribute-options',       [AttributeOptionController::class, 'index']);
    Route::get('/attribute/makers',        [AttributeOptionController::class, 'makers']);
    Route::get('/attribute/category',      [AttributeOptionController::class, 'categories']);
    Route::post('/save-option',            [AttributeOptionController::class, 'store']);
    Route::put('/attribute-option-update', [AttributeOptionController::class, 'update']);
    Route::post('/remove-option',          [AttributeOptionController::class, 'destroy']);

});
