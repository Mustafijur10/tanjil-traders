<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Only what the shop cannot start without: the first admin account.
 * Everything else (categories, brands, specs, products, banners ...) is entered in the admin panel.
 *
 * Optional starter data, run only if you want it:
 *   php artisan db:seed --class=CatalogSeeder         categories, brands and specs for an electronics shop
 *   php artisan db:seed --class=ShippingSeeder        Inside Dhaka / Outside Dhaka delivery charges
 *   php artisan db:seed --class=DemoProductSeeder     18 sample products (needs CatalogSeeder first)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Change this password after the first login.
        User::firstOrCreate(
            ['email' => 'admin@tanjiltraders.test'],
            ['name' => 'Store Admin', 'password' => 'password', 'role' => 'admin'],
        );
    }
}
