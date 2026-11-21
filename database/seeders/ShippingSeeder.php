<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

/** Optional: two common delivery options. Editable later in Settings > Shipping. */
class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        ShippingMethod::updateOrCreate(['name' => 'Inside Dhaka'], [
            'delivery_time' => '1-2 days', 'charge' => 70, 'free_over' => null, 'sort_order' => 1,
        ]);
        ShippingMethod::updateOrCreate(['name' => 'Outside Dhaka'], [
            'delivery_time' => '2-4 days', 'charge' => 130, 'free_over' => null, 'sort_order' => 2,
        ]);
    }
}
