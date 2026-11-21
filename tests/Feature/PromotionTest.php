<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private Product $phone;
    private Product $laptop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->staff()->create(), 'sanctum');

        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $ram = Attribute::create(['name' => 'RAM', 'slug' => 'ram', 'type' => 'select']);
        $ram->values()->createMany([['value' => '8GB', 'slug' => '8gb'], ['value' => '16GB', 'slug' => '16gb']]);
        $saver = app(ProductSaver::class);
        $this->phone = $saver->save(['name' => 'Galaxy', 'category_id' => $cat->id, 'price' => 50000, 'stock_qty' => 5, 'status' => 'active']);
        $this->laptop = $saver->save(['name' => 'IdeaPad', 'category_id' => $cat->id, 'price' => 1, 'status' => 'active', 'variants' => [
            ['price' => 70000, 'stock_qty' => 2, 'value_ids' => [$ram->values->firstWhere('value', '8GB')->id]],
            ['price' => 90000, 'stock_qty' => 1, 'value_ids' => [$ram->values->firstWhere('value', '16GB')->id]],
        ]]);
    }

    private function sale(array $extra = [])
    {
        return $this->postJson('/api/admin/flash-sales', array_merge([
            'name' => 'Eid flash sale', 'discount_type' => 'percent', 'discount_value' => 10,
            'starts_at' => now()->subHour()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString(),
            'is_active' => true, 'product_ids' => [$this->phone->id, $this->laptop->id],
        ], $extra));
    }

    public function test_live_flash_sale_discounts_products_and_options(): void
    {
        $id = $this->sale()->assertCreated()->assertJsonPath('state', 'live')->json('id');

        $phone = $this->phone->fresh();
        $this->assertTrue($phone->onSale());
        $this->assertSame(45000.0, $phone->final_price);
        $this->assertSame([63000.0, 81000.0], $this->laptop->variants()->orderBy('price')->pluck('sale_price')->map(fn ($v) => (float) $v)->all());
        $this->assertSame(63000.0, $this->laptop->fresh()->final_price);

        // Shows on the deals page and the "on sale" filter
        $this->getJson('/api/shop?sale=1')->assertOk()->assertJsonCount(2, 'products.data');

        // Deleting the sale puts prices back
        $this->deleteJson("/api/admin/flash-sales/{$id}")->assertNoContent();
        $this->assertFalse($this->phone->fresh()->onSale());
        $this->assertSame(70000.0, $this->laptop->fresh()->final_price);
    }

    public function test_scheduled_sale_waits_for_its_start_time(): void
    {
        $this->sale(['starts_at' => now()->addHours(3)->toDateTimeString()])->assertCreated()->assertJsonPath('state', 'scheduled');

        $this->assertFalse($this->phone->fresh()->onSale());
        $this->assertSame(50000.0, $this->phone->fresh()->final_price);
        $this->getJson('/api/shop?sale=1')->assertOk()->assertJsonCount(0, 'products.data');

        $this->travel(4)->hours();
        $this->assertTrue($this->phone->fresh()->onSale());
        $this->getJson('/api/shop?sale=1')->assertOk()->assertJsonCount(2, 'products.data');
    }

    public function test_a_product_cannot_be_in_two_sales_at_once(): void
    {
        $this->sale()->assertCreated();
        $this->sale(['name' => 'Another', 'product_ids' => [$this->phone->id]])->assertJsonValidationErrors('product_ids');
    }

    public function test_coupons_are_saved_uppercase_with_stats(): void
    {
        $this->postJson('/api/admin/coupons', ['code' => 'eid 10', 'type' => 'percent', 'value' => 120])->assertJsonValidationErrors('value');
        $this->postJson('/api/admin/coupons', ['code' => 'eid 10', 'type' => 'percent', 'value' => 10, 'is_active' => true])->assertCreated()->assertJsonPath('code', 'EID10');
        $this->postJson('/api/admin/coupons', ['code' => 'Eid10', 'type' => 'fixed', 'value' => 10])->assertJsonValidationErrors('code');

        $this->getJson('/api/admin/coupons')->assertOk()->assertJsonPath('items.0.state', 'active')->assertJsonPath('stats.active', 1);
        $this->assertSame(1, Coupon::count());
    }
}
