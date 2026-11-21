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
Route::get('/shop', [Store\ShopController::class, 'index']);
Route::get('/search/suggest', [Store\ShopController::class, 'suggest'])->middleware('throttle:60,1');
Route::get('/brands', [Store\ShopController::class, 'brands']);
Route::get('/products/{slug}', [Store\ShopController::class, 'product']);
Route::post('/products/{slug}/reviews', [Store\ShopController::class, 'review'])->middleware('throttle:5,10');
Route::get('/compare', [Store\ShopController::class, 'compare']);
Route::get('/product-cards', [Store\ShopController::class, 'cards']);
Route::get('/pages/{slug}', [Store\PageController::class, 'show']);
Route::post('/contact', [Store\PageController::class, 'contact'])->middleware('throttle:5,10');

// Cart and checkout (guests allowed; a sign-in token is used when present)
Route::post('/cart', [Store\CheckoutController::class, 'cart'])->middleware('throttle:60,1');
Route::get('/checkout', [Store\CheckoutController::class, 'options']);
Route::post('/checkout/quote', [Store\CheckoutController::class, 'quote'])->middleware('throttle:60,1');
Route::post('/checkout', [Store\CheckoutController::class, 'place'])->middleware('throttle:10,1');
Route::post('/orders/track', [Store\CheckoutController::class, 'track'])->middleware('throttle:20,1');

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

    // Customer account
    Route::get('/me/summary', [Store\AccountController::class, 'summary']);
    Route::get('/me/orders', [Store\AccountController::class, 'orders']);
    Route::get('/me/orders/{number}', [Store\AccountController::class, 'order']);
    Route::post('/me/orders/{number}/cancel', [Store\AccountController::class, 'cancel']);
    Route::post('/me/orders/{number}/return', [Store\AccountController::class, 'requestReturn']);
    Route::get('/me/wishlist', [Store\AccountController::class, 'wishlist']);
    Route::post('/me/wishlist/sync', [Store\AccountController::class, 'syncWishlist']);
    Route::post('/me/wishlist/{product}', [Store\AccountController::class, 'toggleWishlist']);
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

    // Orders and after-sales
    Route::get('/orders/products', [Admin\OrderController::class, 'products']);
    Route::post('/orders/bulk', [Admin\OrderController::class, 'bulk']);
    Route::apiResource('orders', Admin\OrderController::class)->except('destroy');
    Route::post('/orders/{order}/returns', [Admin\ReturnController::class, 'store']);
    Route::get('/returns', [Admin\ReturnController::class, 'index']);
    Route::put('/returns/{return}', [Admin\ReturnController::class, 'update']);

    // Delivery
    Route::get('/couriers', [Admin\DeliveryController::class, 'couriers']);
    Route::post('/couriers', [Admin\DeliveryController::class, 'storeCourier']);
    Route::put('/couriers/{courier}', [Admin\DeliveryController::class, 'updateCourier']);
    Route::delete('/couriers/{courier}', [Admin\DeliveryController::class, 'destroyCourier']);
    Route::post('/delivery/reorder', [Admin\DeliveryController::class, 'reorder']);
    Route::get('/shipping-methods', [Admin\DeliveryController::class, 'shipping']); // editing is admin-only, below

    // Promotions
    Route::get('/coupons', [Admin\PromotionController::class, 'coupons']);
    Route::post('/coupons', [Admin\PromotionController::class, 'storeCoupon']);
    Route::put('/coupons/{coupon}', [Admin\PromotionController::class, 'updateCoupon']);
    Route::delete('/coupons/{coupon}', [Admin\PromotionController::class, 'destroyCoupon']);
    Route::get('/flash-sales', [Admin\PromotionController::class, 'flashSales']);
    Route::post('/flash-sales', [Admin\PromotionController::class, 'storeFlashSale']);
    Route::get('/flash-sales/{flashSale}', [Admin\PromotionController::class, 'showFlashSale']);
    Route::put('/flash-sales/{flashSale}', [Admin\PromotionController::class, 'updateFlashSale']);
    Route::delete('/flash-sales/{flashSale}', [Admin\PromotionController::class, 'destroyFlashSale']);

    // Customers and stock
    Route::apiResource('customers', Admin\CustomerController::class)->only(['index', 'show', 'update']);
    Route::get('/inventory', [Admin\InventoryController::class, 'index']);
    Route::post('/inventory/adjust', [Admin\InventoryController::class, 'adjust']);
    Route::get('/inventory/movements', [Admin\InventoryController::class, 'movements']);

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
        Route::post('/shipping-methods', [Admin\DeliveryController::class, 'storeShipping']);
        Route::put('/shipping-methods/{method}', [Admin\DeliveryController::class, 'updateShipping']);
        Route::delete('/shipping-methods/{method}', [Admin\DeliveryController::class, 'destroyShipping']);
    });
});
