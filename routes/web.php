<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/sitemap-pages.xml', [PageController::class, 'sitemap']);
Route::get('/{slug}', [PageController::class, 'web'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

Route::get('{any}', function () {
    return view('app');
})->where('any', '.*');