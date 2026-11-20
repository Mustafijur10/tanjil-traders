<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter catalog structure: categories with subcategories, brands, and the specs each category uses.
 * Everything here can be edited or deleted from the admin panel.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $specs = $this->specs();

        $tree = [
            'Laptops'      => ['icon' => 'mdi-laptop', 'children' => ['Gaming Laptops', 'Business Laptops', 'Ultrabooks', 'MacBooks'],
                               'specs' => ['processor', 'ram', 'storage', 'display-size', 'graphics', 'refresh-rate', 'color', 'operating-system']],
            'Smartphones'  => ['icon' => 'mdi-cellphone', 'children' => ['Android Phones', 'iPhones', 'Feature Phones'],
                               'specs' => ['ram', 'storage', 'display-size', 'refresh-rate', 'battery', 'network', 'color', 'operating-system']],
            'Tablets'      => ['icon' => 'mdi-tablet', 'children' => ['Android Tablets', 'iPads'],
                               'specs' => ['ram', 'storage', 'display-size', 'battery', 'network', 'color']],
            'Audio'        => ['icon' => 'mdi-headphones', 'children' => ['Headphones', 'Earbuds', 'Bluetooth Speakers', 'Soundbars'],
                               'specs' => ['connectivity', 'noise-cancellation', 'battery-life', 'color']],
            'Wearables'    => ['icon' => 'mdi-watch', 'children' => ['Smart Watches', 'Fitness Bands', 'Smart Rings'],
                               'specs' => ['display-size', 'battery-life', 'connectivity', 'water-resistance', 'color']],
            'Monitors'     => ['icon' => 'mdi-monitor', 'children' => ['Gaming Monitors', 'Office Monitors'],
                               'specs' => ['display-size', 'resolution', 'refresh-rate', 'panel-type']],
            'Accessories'  => ['icon' => 'mdi-power-plug', 'children' => ['Chargers & Adapters', 'Power Banks', 'Cables', 'Keyboards & Mice', 'Cases & Covers'],
                               'specs' => ['connectivity', 'wattage', 'capacity', 'color']],
        ];

        $order = 0;
        foreach ($tree as $name => $info) {
            $parent = Category::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'icon' => $info['icon'], 'sort_order' => ++$order, 'is_featured' => true,
            ]);

            $parent->attributes()->sync(collect($info['specs'])->mapWithKeys(
                fn ($slug, $i) => [$specs[$slug]->id => ['sort_order' => $i]]
            ));

            foreach ($info['children'] as $i => $child) {
                // A child with the same name as another category ("Smart Watches") gets a prefixed slug.
                $slug = Str::slug($child);
                $taken = Category::where('slug', $slug)->first();
                if ($taken && $taken->parent_id !== $parent->id) {
                    $slug = Str::slug("$name $child");
                }
                Category::updateOrCreate(['slug' => $slug], ['parent_id' => $parent->id, 'name' => $child, 'sort_order' => $i + 1]);
            }
        }

        $brands = ['Apple', 'Samsung', 'Xiaomi', 'Realme', 'OnePlus', 'ASUS', 'Lenovo', 'HP', 'Dell', 'Acer', 'MSI',
                   'Sony', 'JBL', 'Anker', 'Baseus', 'Logitech', 'Amazfit', 'LG'];
        foreach ($brands as $i => $brand) {
            Brand::updateOrCreate(['slug' => Str::slug($brand)], [
                'name' => $brand, 'sort_order' => $i + 1, 'is_featured' => $i < 10,
            ]);
        }
    }

    /** @return array<string, Attribute> keyed by slug */
    private function specs(): array
    {
        $list = [
            // slug => [name, group, unit, is_variant, values]
            'processor'          => ['Processor', 'Performance', null, false, ['Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9', 'Apple M3', 'Apple M4', 'Snapdragon', 'MediaTek Dimensity', 'Apple A-series']],
            'ram'                => ['RAM', 'Memory', 'GB', true, ['2GB', '3GB', '4GB', '6GB', '8GB', '12GB', '16GB', '24GB', '32GB', '64GB']],
            'storage'            => ['Storage', 'Memory', 'GB', true, ['32GB', '64GB', '128GB', '256GB', '512GB', '1TB', '2TB']],
            'graphics'           => ['Graphics', 'Performance', null, false, ['Integrated', 'NVIDIA RTX 3050', 'NVIDIA RTX 4050', 'NVIDIA RTX 4060', 'NVIDIA RTX 4070', 'AMD Radeon']],
            'display-size'       => ['Display Size', 'Display', 'inch', false, ['Under 6"', '6" - 6.5"', '6.5" - 7"', '10" - 11"', '13" - 14"', '15" - 16"', '17" and above', '24"', '27"', '32"']],
            'resolution'         => ['Resolution', 'Display', null, false, ['HD', 'Full HD', '2K QHD', '4K UHD']],
            'refresh-rate'       => ['Refresh Rate', 'Display', 'Hz', false, ['60Hz', '90Hz', '120Hz', '144Hz', '165Hz', '240Hz']],
            'panel-type'         => ['Panel Type', 'Display', null, false, ['IPS', 'VA', 'OLED', 'TN']],
            'battery'            => ['Battery', 'Battery', 'mAh', false, ['Under 4000mAh', '4000 - 5000mAh', '5000mAh and above']],
            'battery-life'       => ['Battery Life', 'Battery', null, false, ['Up to 10 hours', '10 - 24 hours', '24 - 48 hours', 'Over 48 hours']],
            'network'            => ['Network', 'Connectivity', null, false, ['4G', '5G', 'Wi-Fi only']],
            'connectivity'       => ['Connectivity', 'Connectivity', null, false, ['Bluetooth', 'Wired', 'Wi-Fi', 'USB-C', 'Lightning']],
            'noise-cancellation' => ['Noise Cancellation', 'Audio', null, false, ['Active (ANC)', 'Passive', 'None']],
            'water-resistance'   => ['Water Resistance', 'Build', null, false, ['IP67', 'IP68', '5 ATM', 'None']],
            'wattage'            => ['Output Power', 'Power', 'W', false, ['20W', '30W', '45W', '65W', '100W', '140W']],
            'capacity'           => ['Capacity', 'Power', 'mAh', false, ['10000mAh', '20000mAh', '30000mAh']],
            'operating-system'   => ['Operating System', 'Software', null, false, ['Windows 11', 'macOS', 'Android', 'iOS', 'iPadOS', 'FreeDOS']],
            'color'              => ['Color', 'Build', null, true, ['Black' => '#1f2328', 'White' => '#f4f4f2', 'Silver' => '#c4c7cc', 'Gray' => '#6b7076', 'Blue' => '#2f5be7', 'Green' => '#0f9d6b', 'Gold' => '#c9a34e', 'Pink' => '#e8a5b8']],
        ];

        $out = [];
        $order = 0;
        foreach ($list as $slug => [$name, $group, $unit, $isVariant, $values]) {
            $attr = Attribute::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'group' => $group, 'unit' => $unit, 'type' => 'select',
                'is_variant' => $isVariant, 'sort_order' => ++$order,
            ]);

            $i = 0;
            foreach ($values as $key => $value) {
                [$label, $swatch] = is_string($key) ? [$key, $value] : [$value, null];
                $attr->values()->updateOrCreate(['slug' => Str::slug($label) ?: 'v' . $i], [
                    'value' => $label, 'swatch' => $swatch, 'sort_order' => ++$i,
                ]);
            }
            $out[$slug] = $attr;
        }

        return $out;
    }
}
