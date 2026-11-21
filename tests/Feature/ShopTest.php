<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Services\ProductSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private Category $phones;
    private Category $android;
    private Attribute $ram;

    protected function setUp(): void
    {
        parent::setUp();

        $this->phones = Category::create(['name' => 'Smartphones', 'slug' => 'smartphones']);
        $this->android = Category::create(['name' => 'Android', 'slug' => 'android', 'parent_id' => $this->phones->id]);
        $this->ram = Attribute::create(['name' => 'RAM', 'slug' => 'ram', 'type' => 'select', 'is_filterable' => true]);
        $this->ram->values()->createMany([
            ['value' => '8GB', 'slug' => '8gb', 'sort_order' => 1],
            ['value' => '12GB', 'slug' => '12gb', 'sort_order' => 2],
        ]);
        $this->phones->attributes()->attach($this->ram->id);

        $samsung = Brand::create(['name' => 'Samsung', 'slug' => 'samsung']);
        $xiaomi = Brand::create(['name' => 'Xiaomi', 'slug' => 'xiaomi']);

        $this->make('Galaxy A55', $samsung, 52000, null, 5, '8GB');
        $this->make('Redmi Note 13', $xiaomi, 36000, 33000, 0, '8GB');
        $this->make('Xiaomi 14', $xiaomi, 90000, null, 3, '12GB');
        Product::create(['name' => 'Hidden draft', 'slug' => 'hidden', 'category_id' => $this->android->id, 'price' => 1, 'status' => 'draft']);
    }

    private function make(string $name, Brand $brand, int $price, ?int $sale, int $stock, string $ram): void
    {
        app(ProductSaver::class)->save([
            'name' => $name, 'category_id' => $this->android->id, 'brand_id' => $brand->id,
            'price' => $price, 'sale_price' => $sale, 'stock_qty' => $stock, 'status' => 'active',
            'specs' => [['attribute_id' => $this->ram->id, 'value_ids' => [$this->ram->values->firstWhere('value', $ram)->id]]],
        ]);
    }

    private function names(string $query): array
    {
        return collect($this->getJson('/api/shop?' . $query)->assertOk()->json('products.data'))->pluck('name')->sort()->values()->all();
    }

    public function test_category_page_includes_subcategories_and_hides_drafts(): void
    {
        $this->assertSame(['Galaxy A55', 'Redmi Note 13', 'Xiaomi 14'], $this->names('category=smartphones'));
        $this->getJson('/api/shop?category=nope')->assertNotFound();
    }

    public function test_filters_combine(): void
    {
        $this->assertSame(['Redmi Note 13', 'Xiaomi 14'], $this->names('brand=xiaomi'));
        $this->assertSame(['Galaxy A55', 'Xiaomi 14'], $this->names('stock=1'));
        $this->assertSame(['Redmi Note 13'], $this->names('sale=1'));
        $this->assertSame(['Xiaomi 14'], $this->names('f[ram]=12gb'));
        $this->assertSame(['Redmi Note 13'], $this->names('brand=xiaomi&f[ram]=8gb'));
    }

    public function test_price_filter_uses_the_sale_price(): void
    {
        // Redmi costs 36,000 but sells at 33,000, so it is inside a 30–35k range.
        $this->assertSame(['Redmi Note 13'], $this->names('min=30000&max=35000'));
    }

    public function test_facet_counts_ignore_their_own_selection(): void
    {
        $facets = $this->getJson('/api/shop?category=smartphones&f[ram]=8gb')->json('facets');

        $ram = collect($facets['specs'])->firstWhere('slug', 'ram');
        $this->assertSame(2, collect($ram['values'])->firstWhere('slug', '8gb')['count']);
        $this->assertSame(1, collect($ram['values'])->firstWhere('slug', '12gb')['count']); // still offered
        $this->assertSame(['samsung' => 1, 'xiaomi' => 1], collect($facets['brands'])->pluck('count', 'slug')->sortKeys()->all());
        // The price range follows the other filters (8GB phones only), not the price filter itself.
        $this->assertSame(['min' => 33000, 'max' => 52000], $facets['price']);
    }

    public function test_search_and_sorting(): void
    {
        $this->assertSame(['Redmi Note 13', 'Xiaomi 14'], $this->names('q=xiaomi'));      // matches the brand name
        $this->assertSame(['Galaxy A55'], $this->names('q=galaxy a55'));
        $this->assertDatabaseHas('search_logs', ['query' => 'galaxy a55', 'results_count' => 1]);

        $sorted = collect($this->getJson('/api/shop?sort=price_asc')->json('products.data'))->pluck('name')->all();
        $this->assertSame(['Redmi Note 13', 'Galaxy A55', 'Xiaomi 14'], $sorted);
    }

    public function test_product_page_and_reviews(): void
    {
        $this->getJson('/api/products/hidden')->assertNotFound();

        $res = $this->getJson('/api/products/galaxy-a55')->assertOk();
        $res->assertJsonPath('product.name', 'Galaxy A55')
            ->assertJsonPath('product.specs.0.rows.0.value', '8GB');
        $this->assertNotEmpty($res->json('related'));

        $this->postJson('/api/products/galaxy-a55/reviews', ['name' => 'Rahim', 'rating' => 5, 'comment' => 'Great'])->assertCreated();
        $this->assertFalse(Review::first()->is_approved);
        $this->assertSame(0, $this->getJson('/api/products/galaxy-a55')->json('product.reviews'));
    }

    public function test_compare_returns_specs_side_by_side(): void
    {
        $ids = Product::active()->orderBy('id')->pluck('id')->take(2)->implode(',');
        $res = $this->getJson('/api/compare?ids=' . $ids)->assertOk();

        $this->assertCount(2, $res->json('products'));
        $this->assertSame(['8GB', '8GB'], $res->json('rows.0.values'));
    }

    public function test_search_suggestions(): void
    {
        $this->getJson('/api/search/suggest?q=red')->assertOk()->assertJsonPath('products.0.name', 'Redmi Note 13');
    }
}
