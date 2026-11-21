<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function order(float $total, string $status, $when): Order
    {
        $o = Order::create(['name' => 'A', 'phone' => '017', 'address' => 'X', 'city' => 'Dhaka', 'subtotal' => $total, 'total' => $total, 'status' => $status]);
        $o->forceFill(['created_at' => $when])->saveQuietly();

        return $o;
    }

    public function test_kpis_use_real_orders_and_compare_with_the_previous_period(): void
    {
        $this->actingAs(User::factory()->staff()->create(), 'sanctum');

        $this->order(10000, 'delivered', now()->subDays(2));
        $this->order(5000, 'pending', now()->subDay());
        $this->order(99999, 'cancelled', now()->subDay());        // not a sale
        $this->order(4000, 'delivered', now()->subDays(40));      // previous 30 days

        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        Product::create(['name' => 'Low', 'slug' => 'low', 'category_id' => $cat->id, 'price' => 1, 'stock_qty' => 2, 'status' => 'active']);
        Product::create(['name' => 'Out', 'slug' => 'out', 'category_id' => $cat->id, 'price' => 1, 'stock_qty' => 0, 'status' => 'active']);

        $cards = $this->getJson('/api/admin/dashboard/kpis?range=30')->assertOk()->json('cards');

        $this->assertEquals(15000, $cards['revenue']['value']);
        $this->assertEquals(4000, $cards['revenue']['previous']);
        $this->assertSame(2, $cards['orders']['value']);
        $this->assertEquals(7500, $cards['aov']['value']);
        $this->assertSame(1, $cards['pending']['value']);
        $this->assertSame(1, $cards['low_stock']['value']);
        $this->assertSame(1, $cards['low_stock']['meta']['out']);
        $this->assertCount(30, $cards['revenue']['series']);
        $this->assertMatchesRegularExpression('/^TT-\d{4}-\d{4}$/', Order::first()->order_number);
        $this->assertEquals(15000, array_sum($cards['revenue']['series']));
    }

    public function test_kpis_are_staff_only(): void
    {
        $this->getJson('/api/admin/dashboard/kpis')->assertUnauthorized();
    }
}
