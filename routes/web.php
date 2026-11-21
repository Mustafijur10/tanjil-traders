<?php

use App\Http\Controllers\SeoPageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
 * The Vue app handles every page. Product, category, brand and information pages are served
 * with their real title, description, image and structured data, so search engines and
 * link previews (Facebook, WhatsApp) see them without running JavaScript.
 */
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);
Route::get('/product/{slug}', [SeoPageController::class, 'product']);
Route::get('/category/{slug}', [SeoPageController::class, 'category']);
Route::get('/brand/{slug}', [SeoPageController::class, 'brand']);
Route::get('/search', [SeoPageController::class, 'noindex']);
Route::get('/compare', [SeoPageController::class, 'noindex']);
// Information pages (/about-us ...). Addresses used by the shop itself are excluded.
Route::get('/{slug}', [SeoPageController::class, 'page'])
    ->where('slug', '(?!(?:admin|shop|deals|brands|contact|login|register|cart|checkout|account|wishlist|track-order|order-placed|forgot-password|reset-password|up)$)[a-z0-9][a-z0-9-]*');

Route::view('/{any?}', 'app')->where('any', '^(?!api|storage|build).*$');
