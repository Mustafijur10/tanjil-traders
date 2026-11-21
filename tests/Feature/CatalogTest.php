<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->staff()->create(), 'sanctum');
    }

    private function ram(): Attribute
    {
        $ram = Attribute::create(['name' => 'RAM', 'slug' => 'ram', 'type' => 'select', 'is_variant' => true]);
        $ram->values()->createMany([
            ['value' => '8GB', 'slug' => '8gb', 'sort_order' => 1],
            ['value' => '16GB', 'slug' => '16gb', 'sort_order' => 2],
        ]);

        return $ram->load('values');
    }

    private function laptops(): Category
    {
        return Category::firstOrCreate(['slug' => 'laptops'], ['name' => 'Laptops']);
    }

    private function productPayload(array $extra = []): array
    {
        return array_merge([
            'name' => 'ASUS Vivobook 15', 'category_id' => $this->laptops()->id,
            'price' => 65000, 'status' => 'active', 'stock_qty' => 4,
        ], $extra);
    }

    // ── Categories ──

    public function test_category_gets_a_unique_slug_and_lists_as_a_tree(): void
    {
        $parent = $this->postJson('/api/admin/categories', ['name' => 'Audio'])->assertCreated()->json();
        $this->postJson('/api/admin/categories', ['name' => 'Audio', 'parent_id' => $parent['id']])
            ->assertCreated()->assertJsonPath('slug', 'audio-2');

        $this->getJson('/api/admin/categories')->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.children.0.name', 'Audio');
    }

    public function test_categories_cannot_go_three_levels_deep(): void
    {
        $parent = $this->laptops();
        $child = Category::create(['name' => 'Gaming', 'slug' => 'gaming', 'parent_id' => $parent->id]);

        $this->postJson('/api/admin/categories', ['name' => 'Too deep', 'parent_id' => $child->id])
            ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $this->postJson('/api/admin/products', $this->productPayload())->assertCreated();
        $category = Category::first();

        $this->deleteJson("/api/admin/categories/{$category->id}")->assertUnprocessable();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    // ── Specs ──

    // Editing keeps existing values (same id), adds new ones, removes dropped ones,
    // and numbers values that would share a slug ("1TB" / "1 TB").
    public function test_editing_spec_values_keeps_ids_and_numbers_clashing_slugs(): void
    {
        $ram = $this->ram();
        $eight = $ram->values->firstWhere('value', '8GB');

        $this->putJson("/api/admin/attributes/{$ram->id}", [
            'name' => 'RAM', 'type' => 'select',
            'values' => [['id' => $eight->id, 'value' => '8GB'], ['value' => '1TB'], ['value' => '1 TB']],
        ])->assertOk();

        $this->assertDatabaseHas('attribute_values', ['id' => $eight->id, 'value' => '8GB']);
        $this->assertDatabaseHas('attribute_values', ['value' => '1 TB', 'slug' => '1-tb']);
        $this->assertDatabaseMissing('attribute_values', ['value' => '16GB']);
    }

    // ── Products ──

    public function test_simple_product_saves_specs_images_and_logs_opening_stock(): void
    {
        $ram = $this->ram();
        $brand = Brand::create(['name' => 'ASUS', 'slug' => 'asus']);

        $id = $this->postJson('/api/admin/products', $this->productPayload([
            'brand_id' => $brand->id,
            'images'   => [['path' => 'products/a.jpg'], ['path' => 'products/b.jpg']],
            'specs'    => [['attribute_id' => $ram->id, 'value_ids' => [$ram->values[0]->id]]],
        ]))->assertCreated()->assertJsonPath('slug', 'asus-vivobook-15')->json('id');

        $this->assertDatabaseCount('product_images', 2);
        $this->assertDatabaseHas('product_specs', ['product_id' => $id, 'attribute_value_id' => $ram->values[0]->id]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $id, 'change' => 4, 'balance' => 4, 'reason' => 'restock']);

        // Changing stock in the form is logged as an adjustment.
        $this->putJson("/api/admin/products/{$id}", $this->productPayload(['name' => 'ASUS Vivobook 15', 'stock_qty' => 1, 'category_id' => Category::first()->id]))->assertOk();
        $this->assertDatabaseHas('stock_movements', ['product_id' => $id, 'change' => -3, 'balance' => 1, 'reason' => 'adjustment']);
    }

    public function test_variants_set_lowest_price_and_total_stock(): void
    {
        $ram = $this->ram();
        [$eight, $sixteen] = [$ram->values[0]->id, $ram->values[1]->id];

        $id = $this->postJson('/api/admin/products', $this->productPayload([
            'variants' => [
                ['price' => 70000, 'stock_qty' => 2, 'value_ids' => [$sixteen]],
                ['price' => 62000, 'sale_price' => 60000, 'stock_qty' => 3, 'value_ids' => [$eight]],
            ],
        ]))->assertCreated()->json('id');

        // Variant options also become specs, so a "16GB" filter finds this product.
        $this->assertDatabaseHas('product_specs', ['product_id' => $id, 'attribute_value_id' => $sixteen]);
        $this->assertDatabaseHas('product_specs', ['product_id' => $id, 'attribute_value_id' => $eight]);

        $product = Product::find($id);
        $this->assertSame(62000.0, $product->price);
        $this->assertSame(60000.0, $product->sale_price);
        $this->assertSame(5, $product->stock_qty);
        $this->assertSame(2, StockMovement::where('product_id', $id)->count());
    }

    public function test_product_validation_rules(): void
    {
        $ram = $this->ram();
        $other = Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'select']);

        // Sale price must be lower than price
        $this->postJson('/api/admin/products', $this->productPayload(['sale_price' => 70000]))
            ->assertUnprocessable()->assertJsonValidationErrors('sale_price');

        // A value must belong to its spec
        $this->postJson('/api/admin/products', $this->productPayload([
            'specs' => [['attribute_id' => $other->id, 'value_ids' => [$ram->values[0]->id]]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('specs');

        // Two variants with the same options
        $this->postJson('/api/admin/products', $this->productPayload([
            'variants' => [
                ['price' => 1, 'value_ids' => [$ram->values[0]->id]],
                ['price' => 2, 'value_ids' => [$ram->values[0]->id]],
            ],
        ]))->assertUnprocessable()->assertJsonValidationErrors('variants');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_list_filters_by_stock_and_returns_counts(): void
    {
        $cat = $this->laptops();
        Product::create(['name' => 'A', 'slug' => 'a', 'category_id' => $cat->id, 'price' => 1, 'stock_qty' => 0, 'status' => 'active']);
        Product::create(['name' => 'B', 'slug' => 'b', 'category_id' => $cat->id, 'price' => 1, 'stock_qty' => 3, 'status' => 'active']);
        Product::create(['name' => 'C', 'slug' => 'c', 'category_id' => $cat->id, 'price' => 1, 'stock_qty' => 50, 'status' => 'draft']);

        $res = $this->getJson('/api/admin/products?stock=low')->assertOk();
        $this->assertSame(['B'], array_column($res->json('items.data'), 'name'));
        $res->assertJsonPath('counts.out', 1)->assertJsonPath('counts.low', 1)->assertJsonPath('counts.draft', 1);
    }

    public function test_duplicate_and_bulk_actions(): void
    {
        $id = $this->postJson('/api/admin/products', $this->productPayload(['sku' => 'VB15']))->json('id');

        $copy = $this->postJson("/api/admin/products/{$id}/duplicate")->assertCreated()
            ->assertJsonPath('status', 'draft')->assertJsonPath('sku', null)->json('id');

        $this->postJson('/api/admin/products/bulk', ['ids' => [$id, $copy], 'action' => 'feature'])->assertOk();
        $this->assertSame(2, Product::where('is_featured', true)->count());
    }

    // ── Reviews ──

    public function test_approving_a_review_updates_the_product_rating(): void
    {
        $id = $this->postJson('/api/admin/products', $this->productPayload())->json('id');
        $review = Review::create(['product_id' => $id, 'name' => 'Rahim', 'rating' => 4]);

        $this->putJson("/api/admin/reviews/{$review->id}", ['is_approved' => true])->assertOk();

        $this->assertSame(4.0, Product::find($id)->rating_avg);
        $this->assertSame(1, Product::find($id)->review_count);
    }
}
