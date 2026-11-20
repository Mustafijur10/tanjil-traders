<?php

use Illuminate\Support\Facades\Route;

// The Vue app handles every page (storefront and /admin).
Route::view('/{any?}', 'app')->where('any', '^(?!api|storage).*$');
