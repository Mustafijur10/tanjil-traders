<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->staff()->create(), 'sanctum');
    }

    private function import(string $type, array $rows, bool $dry = false)
    {
        return $this->postJson("/api/admin/import/{$type}", ['rows' => $rows, 'dry_run' => $dry])->assertOk();
    }

    public function test_preview_saves_nothing_but_understands_rows_that_depend_on_each_other(): void
    {
        $rows = [
            ['name' => 'Android Phones', 'parent' => 'Smartphones'], // parent comes later in the file
            ['name' => 'Smartphones', 'show_in_nav' => 'Yes'],
            ['name' => 'Ghost', 'parent' => 'Nowhere'],
        ];

        $preview = $this->import('categories', $rows, true);
        $this->assertSame(['create', 'create', 'error'], array_column($preview->json('results'), 'status'));
        $this->assertSame(2, $preview->json('results.0.row')); // Excel row number (after the header)
        $this->assertDatabaseCount('categories', 0);

        $this->import('categories', $rows)->assertJsonPath('summary.create', 2)->assertJsonPath('summary.error', 1);
        $this->assertSame('Smartphones', Category::where('name', 'Android Phones')->first()->parent->name);

        // Importing again updates instead of duplicating
        $this->import('categories', [['name' => 'Smartphones', 'is_active' => 'No']])->assertJsonPath('summary.update', 1);
        $this->assertFalse(Category::where('name', 'Smartphones')->first()->is_active);
    }

    public function test_products_import_with_brand_specs_and_images(): void
    {
        $this->import('categories', [['name' => 'Phones'], ['name' => 'Android', 'parent' => 'Phones']]);

        $res = $this->import('products', [
            ['name' => 'Galaxy A55', 'sku' => 'A55', 'category' => 'Phones > Android', 'brand' => 'Samsung', 'price' => '52999', 'cost_price' => 46000, 'stock_qty' => 7,
                'specs' => 'RAM: 8GB; Storage: 128GB', 'images' => 'https://cdn.test/a.jpg, https://cdn.test/b.jpg', 'short_description' => "AMOLED\nIP67"],
            ['name' => 'Bad', 'category' => 'Phones', 'price' => 'abc'],
            ['name' => 'Cheap sale', 'category' => 'Android', 'price' => 100, 'sale_price' => 150],
        ]);
        $this->assertSame(['create', 'error', 'error'], array_column($res->json('results'), 'status'));

        $p = Product::where('sku', 'A55')->with(['brand', 'images', 'specs.attributeValue'])->first();
        $this->assertSame('Samsung', $p->brand->name);
        $this->assertSame(7, $p->stock_qty);
        $this->assertCount(2, $p->images);
        $this->assertEqualsCanonicalizing(['8GB', '128GB'], $p->specs->pluck('attributeValue.value')->all());
        $this->assertSame(1, Brand::count());

        // Same SKU: updated; empty images keep the photos
        $this->import('products', [['name' => 'Galaxy A55 5G', 'sku' => 'A55', 'category' => 'Android', 'price' => 49999]])->assertJsonPath('summary.update', 1);
        $p->refresh();
        $this->assertSame('Galaxy A55 5G', $p->name);
        $this->assertCount(2, $p->images);
        $this->assertSame(7, $p->stock_qty);
    }

    public function test_existing_product_without_sku_is_updated_not_duplicated(): void
    {
        $this->import('categories', [['name' => 'Phones']]);
        $this->import('products', [['name' => 'Realme 12 Pro+', 'category' => 'Phones', 'price' => 44999]]);

        $this->import('products', [['name' => 'realme 12 pro+', 'sku' => 'RLM-12', 'category' => 'Phones', 'price' => 42999]])->assertJsonPath('summary.update', 1);
        $this->assertSame(1, Product::count());
        $this->assertSame('RLM-12', Product::first()->sku);

        // Round trip: the download has the same columns the import reads
        $rows = $this->getJson('/api/admin/import/products/rows')->assertOk()->json();
        $this->assertSame(['Phones', 'RLM-12', 'Published'], [$rows[0]['category'], $rows[0]['sku'], $rows[0]['status']]);
    }

    public function test_stock_count_is_logged(): void
    {
        $this->import('categories', [['name' => 'Phones']]);
        $this->import('products', [['name' => 'Galaxy', 'sku' => 'G1', 'category' => 'Phones', 'price' => 10, 'stock_qty' => 5]]);

        $this->import('stock', [['sku' => 'G1', 'stock_qty' => 9, 'note' => 'Count'], ['sku' => 'NOPE', 'stock_qty' => 1]])
            ->assertJsonPath('summary.update', 1)->assertJsonPath('summary.error', 1)->assertJsonPath('results.0.name', 'Galaxy (+4)');
        $this->assertSame(9, Product::where('sku', 'G1')->value('stock_qty'));
        $this->assertSame('Count', StockMovement::latest('id')->value('note'));
    }

    public function test_area_permissions_apply(): void
    {
        $this->actingAs(User::factory()->staff()->create(['permissions' => ['orders']]), 'sanctum');
        $this->postJson('/api/admin/import/brands', ['rows' => [['name' => 'X']]])->assertForbidden();
        $this->getJson('/api/admin/import/types')->assertOk()->assertJsonStructure(['products', 'categories', 'brands', 'attributes', 'stock']);
    }
}
