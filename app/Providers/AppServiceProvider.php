<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Password reset emails link to the Vue page, not a Laravel route.
        ResetPassword::createUrlUsing(fn ($user, string $token) => url('/reset-password?token=' . $token . '&email=' . urlencode($user->email)));
    }
}
