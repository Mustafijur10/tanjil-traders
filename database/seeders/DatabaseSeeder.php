<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Change this password after the first login (Admin > My Profile).
        User::updateOrCreate(
            ['email' => 'admin@tanjiltraders.test'],
            ['name' => 'Store Admin', 'password' => 'password', 'role' => 'admin'],
        );

        ShippingMethod::updateOrCreate(['name' => 'Inside Dhaka'], [
            'delivery_time' => '1-2 days', 'charge' => 70, 'free_over' => 20000, 'sort_order' => 1,
        ]);
        ShippingMethod::updateOrCreate(['name' => 'Outside Dhaka'], [
            'delivery_time' => '2-4 days', 'charge' => 130, 'free_over' => null, 'sort_order' => 2,
        ]);

        $this->call(CatalogSeeder::class);
    }
}
