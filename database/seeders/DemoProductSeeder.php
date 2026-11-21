<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Review;
use App\Services\ProductSaver;
use Illuminate\Database\Seeder;

/**
 * Optional sample products for trying the admin and the shop filters.
 * Not run by default:  php artisan db:seed --class=DemoProductSeeder
 * Delete them any time from Catalog > All Products.
 */
class DemoProductSeeder extends Seeder
{
    public function run(ProductSaver $saver): void
    {
        $attrs = Attribute::with('values')->get()->keyBy('slug');
        $v = fn (string $attr, string ...$values) => [
            'attribute_id' => $attrs[$attr]->id,
            'value_ids'    => $attrs[$attr]->values->whereIn('value', $values)->pluck('id')->values()->all(),
        ];
        $cat = fn (string $slug) => Category::where('slug', $slug)->value('id');
        $brand = fn (string $name) => Brand::where('name', $name)->value('id');

        $products = [
            ['Xiaomi Redmi Note 13 Pro 5G', 'android-phones', 'Xiaomi', 38999, 35999, 14, "200MP main camera\n6.67\" 120Hz AMOLED display\n5100mAh battery with 67W charging",
                [$v('ram', '8GB'), $v('storage', '256GB'), $v('display-size', '6.5" - 7"'), $v('refresh-rate', '120Hz'), $v('battery', '5000mAh and above'), $v('network', '5G'), $v('operating-system', 'Android')]],
            ['Samsung Galaxy A55 5G', 'android-phones', 'Samsung', 52999, null, 9, "Super AMOLED 120Hz display\nIP67 water resistance\n4 years of Android updates",
                [$v('ram', '8GB'), $v('display-size', '6.5" - 7"'), $v('refresh-rate', '120Hz'), $v('battery', '5000mAh and above'), $v('network', '5G'), $v('operating-system', 'Android')],
                ['storage' => [['128GB', 52999, 6], ['256GB', 58999, 3]]]],
            ['Realme 12 Pro+', 'android-phones', 'Realme', 44999, 41999, 0, "Periscope telephoto camera\nCurved 120Hz display",
                [$v('ram', '12GB'), $v('storage', '256GB'), $v('refresh-rate', '120Hz'), $v('network', '5G'), $v('operating-system', 'Android')]],
            ['Apple iPhone 15', 'iphones', 'Apple', 124999, null, 5, "A16 Bionic chip\nDynamic Island\nUSB-C",
                [$v('display-size', '6" - 6.5"'), $v('network', '5G'), $v('operating-system', 'iOS')],
                ['storage' => [['128GB', 124999, 3], ['256GB', 139999, 2]]]],
            ['ASUS Vivobook 15 X1504VA', 'business-laptops', 'ASUS', 68500, 64900, 7, "13th Gen Intel Core i5\n15.6\" Full HD display\nFingerprint sensor",
                [$v('processor', 'Intel Core i5'), $v('ram', '8GB'), $v('storage', '512GB'), $v('display-size', '15" - 16"'), $v('graphics', 'Integrated'), $v('operating-system', 'Windows 11')]],
            ['Lenovo LOQ 15IRX9', 'gaming-laptops', 'Lenovo', 139000, null, 3, "Intel Core i7 13650HX\nNVIDIA RTX 4060 8GB\n144Hz display",
                [$v('processor', 'Intel Core i7'), $v('ram', '16GB'), $v('storage', '512GB'), $v('display-size', '15" - 16"'), $v('graphics', 'NVIDIA RTX 4060'), $v('refresh-rate', '144Hz'), $v('operating-system', 'Windows 11')]],
            ['HP Victus 15 fa1000', 'gaming-laptops', 'HP', 112000, 105000, 2, "Intel Core i5 13420H\nRTX 3050 6GB\n144Hz IPS",
                [$v('processor', 'Intel Core i5'), $v('ram', '16GB'), $v('storage', '512GB'), $v('display-size', '15" - 16"'), $v('graphics', 'NVIDIA RTX 3050'), $v('refresh-rate', '144Hz'), $v('operating-system', 'Windows 11')]],
            ['Apple MacBook Air M3 13"', 'macbooks', 'Apple', 154999, null, 4, "Apple M3 chip\n18-hour battery life\nFanless design",
                [$v('processor', 'Apple M3'), $v('display-size', '13" - 14"'), $v('operating-system', 'macOS')],
                ['ram' => [['8GB', 154999, 3], ['16GB', 184999, 1]]]],
            ['Dell Inspiron 14 5440', 'ultrabooks', 'Dell', 89000, null, 6, "Intel Core 7 150U\n14\" FHD+ display\nBacklit keyboard",
                [$v('processor', 'Intel Core i7'), $v('ram', '16GB'), $v('storage', '512GB'), $v('display-size', '13" - 14"'), $v('operating-system', 'Windows 11')]],
            ['Sony WH-1000XM5', 'headphones', 'Sony', 42999, 39999, 8, "Industry-leading noise cancelling\n30-hour battery",
                [$v('connectivity', 'Bluetooth'), $v('noise-cancellation', 'Active (ANC)'), $v('battery-life', '24 - 48 hours')],
                ['color' => [['Black', 39999, 5], ['Silver', 39999, 3]]]],
            ['JBL Tune 520BT', 'headphones', 'JBL', 5499, 4799, 25, "JBL Pure Bass sound\n57-hour battery\nMulti-point connection",
                [$v('connectivity', 'Bluetooth'), $v('noise-cancellation', 'None'), $v('battery-life', 'Over 48 hours'), $v('color', 'Black', 'Blue', 'White')]],
            ['Anker Soundcore Liberty 4 NC', 'earbuds', 'Anker', 9500, 8490, 18, "Adaptive noise cancelling\n50-hour total playtime",
                [$v('connectivity', 'Bluetooth'), $v('noise-cancellation', 'Active (ANC)'), $v('battery-life', 'Over 48 hours'), $v('color', 'Black', 'White')]],
            ['JBL Flip 6', 'bluetooth-speakers', 'JBL', 13999, null, 4, "IP67 waterproof\n12 hours of playtime",
                [$v('connectivity', 'Bluetooth'), $v('battery-life', '10 - 24 hours'), $v('color', 'Black', 'Blue')]],
            ['Amazfit GTR 4', 'smart-watches', 'Amazfit', 21999, 18999, 6, "1.43\" AMOLED display\nDual-band GPS\n14-day battery",
                [$v('battery-life', 'Over 48 hours'), $v('water-resistance', '5 ATM'), $v('connectivity', 'Bluetooth')]],
            ['Anker 735 Charger (GaNPrime 65W)', 'chargers-adapters', 'Anker', 5990, 5490, 30, "65W from 3 ports\nCharges a laptop and a phone together",
                [$v('wattage', '65W'), $v('connectivity', 'USB-C'), $v('color', 'Black')]],
            ['Baseus Blade 20000mAh 100W', 'power-banks', 'Baseus', 7990, null, 2, "100W laptop charging\nSlim 1.8 cm body",
                [$v('capacity', '20000mAh'), $v('wattage', '100W'), $v('connectivity', 'USB-C')]],
            ['Logitech MX Master 3S', 'keyboards-mice', 'Logitech', 12500, 11500, 10, "8K DPI sensor\nQuiet clicks\nUSB-C fast charging",
                [$v('connectivity', 'Bluetooth', 'USB-C'), $v('color', 'Black', 'Gray')]],
            ['LG 27GP850-B UltraGear 27"', 'gaming-monitors', 'LG', 52000, 48500, 3, "27\" QHD Nano IPS\n165Hz, 1ms response",
                [$v('display-size', '27"'), $v('resolution', '2K QHD'), $v('refresh-rate', '165Hz'), $v('panel-type', 'IPS')]],
        ];

        foreach ($products as $i => $row) {
            [$name, $catSlug, $brandName, $price, $sale, $stock, $features, $specs] = $row;
            $options = $row[8] ?? [];
            if (! $cat($catSlug)) {
                $this->command?->warn("Skipped {$name}: category {$catSlug} not found.");
                continue;
            }

            $variants = [];
            foreach ($options as $attrSlug => $list) {
                foreach ($list as [$value, $vPrice, $vStock]) {
                    $variants[] = [
                        'price'     => $vPrice,
                        'stock_qty' => $vStock,
                        'is_active' => true,
                        'value_ids' => [$attrs[$attrSlug]->values->firstWhere('value', $value)->id],
                    ];
                }
            }

            $product = $saver->save([
                'name'              => $name,
                'category_id'       => $cat($catSlug),
                'brand_id'          => $brand($brandName),
                'price'             => $price,
                'sale_price'        => $sale,
                'cost_price'        => round($price * 0.82),
                'stock_qty'         => $stock,
                'short_description' => $features,
                'warranty'          => $i % 3 === 0 ? '1 year official warranty' : '1 year brand warranty',
                'is_featured'       => $i % 4 === 0,
                'status'            => 'active',
                'specs'             => array_values(array_filter($specs, fn ($s) => $s['value_ids'])),
                'variants'          => $variants,
            ]);

            if ($i % 2 === 0) {
                Review::create(['product_id' => $product->id, 'name' => 'Sabbir H.', 'rating' => 5, 'comment' => 'Original product, delivered the next day in Dhaka.', 'is_approved' => true]);
                Review::create(['product_id' => $product->id, 'name' => 'Nusrat J.', 'rating' => 4, 'comment' => 'Good value. Packaging could be better.', 'is_approved' => $i % 4 === 0]);
            }
        }
    }
}
