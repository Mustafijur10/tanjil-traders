<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\ProductSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $phone;
    private Product $laptop;   // has two options
    private ShippingMethod $dhaka;

    protected function setUp(): void
    {
        parent::setUp();

        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $ram = Attribute::create(['name' => 'RAM', 'slug' => 'ram', 'type' => 'select']);
        $ram->values()->createMany([['value' => '8GB', 'slug' => '8gb'], ['value' => '16GB', 'slug' => '16gb']]);

        $saver = app(ProductSaver::class);
        $this->phone = $saver->save(['name' => 'Galaxy A55', 'category_id' => $cat->id, 'price' => 50000, 'sale_price' => 45000, 'cost_price' => 40000, 'stock_qty' => 5, 'status' => 'active']);
        $this->laptop = $saver->save(['name' => 'IdeaPad', 'category_id' => $cat->id, 'price' => 1, 'status' => 'active', 'variants' => [
            ['price' => 70000, 'stock_qty' => 2, 'value_ids' => [$ram->values->firstWhere('value', '8GB')->id]],
            ['price' => 90000, 'stock_qty' => 1, 'value_ids' => [$ram->values->firstWhere('value', '16GB')->id]],
        ]]);
        $this->dhaka = ShippingMethod::create(['name' => 'Inside Dhaka', 'charge' => 70, 'free_over' => 100000]);
    }

    private function checkout(array $items, array $extra = [])
    {
        return $this->postJson('/api/checkout', array_merge([
            'items' => $items, 'name' => 'Rahim', 'phone' => '01712345678', 'address' => 'House 1, Road 2',
            'city' => 'Dhaka', 'shipping_method_id' => $this->dhaka->id, 'payment_method' => 'cod',
        ], $extra));
    }

    private function staff(): User
    {
        $user = User::factory()->staff()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_cart_prices_come_from_the_server_and_flag_stock_problems(): void
    {
        $variant = $this->laptop->variants()->orderBy('price')->first();
        $res = $this->postJson('/api/cart', ['items' => [
            ['product_id' => $this->phone->id, 'qty' => 9],
            ['product_id' => $this->laptop->id, 'variant_id' => $variant->id, 'qty' => 1],
            ['product_id' => $this->laptop->id, 'qty' => 1],    // option not chosen
        ]])->assertOk();

        $this->assertSame(45000, (int) $res->json('lines.0.price'));
        $this->assertSame(5, $res->json('lines.0.qty'));                    // cut to the stock
        $this->assertSame('8GB', $res->json('lines.1.variant_label'));
        $this->assertFalse($res->json('lines.2.available'));
        $this->assertSame(['qty_reduced', 'unavailable'], collect($res->json('issues'))->pluck('type')->all());
        $this->assertArrayNotHasKey('cost_price', $res->json('lines.0'));
    }

    public function test_guest_checkout_places_the_order_and_takes_stock(): void
    {
        $variant = $this->laptop->variants()->orderBy('price')->first();
        $res = $this->checkout([
            ['product_id' => $this->phone->id, 'qty' => 2],
            ['product_id' => $this->laptop->id, 'variant_id' => $variant->id, 'qty' => 1],
        ])->assertCreated();

        $order = Order::where('order_number', $res->json('number'))->firstOrFail();
        $this->assertSame(160000.0, $order->subtotal);
        $this->assertSame(0.0, $order->shipping_cost);                       // free over 100,000
        $this->assertSame(160000.0, $order->total);
        $this->assertSame(3, $this->phone->fresh()->stock_qty);
        $this->assertSame(1, $variant->fresh()->stock_qty);
        $this->assertSame(2, $this->laptop->fresh()->stock_qty);             // product total follows its options
        $this->assertSame(40000.0, (float) $order->items()->first()->cost_price);
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'reason' => 'sale', 'change' => -2]);
    }

    public function test_checkout_refuses_more_than_in_stock_and_bad_phone(): void
    {
        $this->checkout([['product_id' => $this->phone->id, 'qty' => 6]])->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->checkout([['product_id' => $this->phone->id, 'qty' => 1]], ['phone' => '12345'])->assertJsonValidationErrors('phone');
        $this->checkout([['product_id' => $this->phone->id, 'qty' => 1]], ['payment_method' => 'bkash'])->assertJsonValidationErrors('payment_method');
        $this->assertSame(5, $this->phone->fresh()->stock_qty);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_coupon_and_bkash_payment(): void
    {
        Setting::put(['checkout.bkash_enabled' => true, 'checkout.bkash_number' => '01800000000']);
        Coupon::create(['code' => 'EID10', 'type' => 'percent', 'value' => 10, 'max_discount' => 3000]);

        $quote = $this->postJson('/api/checkout/quote', ['items' => [['product_id' => $this->phone->id, 'qty' => 1]], 'coupon_code' => 'eid10', 'shipping_method_id' => $this->dhaka->id])->assertOk();
        $this->assertSame(3000, (int) $quote->json('discount'));
        $this->assertSame(42070, (int) $quote->json('total'));

        $this->checkout([['product_id' => $this->phone->id, 'qty' => 1]], ['coupon_code' => 'EID10', 'payment_method' => 'bkash'])
            ->assertJsonValidationErrors('transaction_id');
        $number = $this->checkout([['product_id' => $this->phone->id, 'qty' => 1]], ['coupon_code' => 'EID10', 'payment_method' => 'bkash', 'transaction_id' => 'TX123'])
            ->assertCreated()->json('number');

        $this->assertSame(42070.0, Order::where('order_number', $number)->value('total') + 0.0);
        $this->assertSame(1, Coupon::first()->used_count);
    }

    public function test_tracking_needs_the_matching_phone(): void
    {
        $number = $this->checkout([['product_id' => $this->phone->id, 'qty' => 1]])->json('number');

        $this->postJson('/api/orders/track', ['number' => $number, 'phone' => '01799999999'])->assertNotFound();
        $res = $this->postJson('/api/orders/track', ['number' => strtolower($number), 'phone' => '+8801712345678'])->assertOk();
        $this->assertSame('pending', $res->json('status'));
        $this->assertArrayNotHasKey('cost_price', $res->json('items.0'));
    }

    public function test_customer_sees_and_cancels_own_pending_order_and_stock_returns(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer, 'sanctum');
        $number = $this->checkout([['product_id' => $this->phone->id, 'qty' => 2]])->json('number');
        $this->assertSame(3, $this->phone->fresh()->stock_qty);

        $this->getJson('/api/me/orders')->assertOk()->assertJsonPath('data.0.number', $number);
        $this->postJson("/api/me/orders/{$number}/cancel")->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertSame(5, $this->phone->fresh()->stock_qty);

        // Someone else's order is not visible
        $this->actingAs(User::factory()->create(['role' => 'customer']), 'sanctum');
        $this->getJson("/api/me/orders/{$number}")->assertNotFound();
    }

    public function test_staff_moves_order_through_statuses(): void
    {
        $number = $this->checkout([['product_id' => $this->phone->id, 'qty' => 1]])->json('number');
        $order = Order::where('order_number', $number)->first();
        $this->staff();
        $courier = Courier::create(['name' => 'Steadfast', 'tracking_url' => 'https://steadfast.com.bd/t/{code}']);

        $this->putJson("/api/admin/orders/{$order->id}", ['status' => 'delivered'])->assertJsonValidationErrors('status');
        $this->putJson("/api/admin/orders/{$order->id}", ['status' => 'confirmed', 'note' => 'Called the customer'])->assertOk();
        $this->putJson("/api/admin/orders/{$order->id}", ['status' => 'shipped', 'courier_id' => $courier->id, 'tracking_code' => 'SF 99'])
            ->assertOk()->assertJsonPath('order.tracking_link', 'https://steadfast.com.bd/t/SF%2099');
        $this->putJson("/api/admin/orders/{$order->id}", ['status' => 'delivered'])->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);                 // cash collected on delivery
        $this->assertNotNull($order->shipped_at);
        $this->assertSame(['pending', 'confirmed', 'shipped', 'delivered'], $order->history()->get()->sortBy('id')->pluck('status')->all());
        $this->getJson("/api/admin/orders/{$order->id}")->assertOk()->assertJsonPath('profit', 5000);
    }

    public function test_staff_creates_an_order_for_a_phone_customer(): void
    {
        $this->staff();
        $this->postJson('/api/admin/orders', [
            'items' => [['product_id' => $this->phone->id, 'qty' => 1]],
            'name' => 'Karim', 'phone' => '01812345678', 'address' => 'Mirpur', 'city' => 'Dhaka',
            'shipping_cost' => 100, 'discount' => 500, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'confirm' => true,
        ])->assertCreated();

        $order = Order::first();
        $this->assertSame('admin', $order->source);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(44600.0, $order->total);
        $this->assertSame(4, $this->phone->fresh()->stock_qty);
    }

    public function test_return_flow_restocks_and_refunds(): void
    {
        $number = $this->checkout([['product_id' => $this->phone->id, 'qty' => 2]])->json('number');
        $order = Order::where('order_number', $number)->first();
        $this->staff();
        foreach (['confirmed', 'shipped', 'delivered'] as $s) {
            $this->putJson("/api/admin/orders/{$order->id}", ['status' => $s])->assertOk();
        }
        $item = $order->items()->first();

        // Customer asks to return one of the two
        $customer = User::factory()->create(['role' => 'customer']);
        $order->update(['user_id' => $customer->id]);
        $this->actingAs($customer, 'sanctum');
        $this->postJson("/api/me/orders/{$number}/return", ['reason' => 'damaged', 'items' => [['order_item_id' => $item->id, 'qty' => 3]]])
            ->assertJsonValidationErrors('items');
        $this->postJson("/api/me/orders/{$number}/return", ['reason' => 'damaged', 'items' => [['order_item_id' => $item->id, 'qty' => 1]]])
            ->assertOk()->assertJsonPath('can_return', false);

        $this->staff();
        $return = $order->returns()->first();
        $this->putJson("/api/admin/returns/{$return->id}", ['status' => 'refunded', 'refund_amount' => 1, 'refund_method' => 'cash'])->assertJsonValidationErrors('status');
        $this->putJson("/api/admin/returns/{$return->id}", ['status' => 'approved'])->assertOk();
        $this->putJson("/api/admin/returns/{$return->id}", ['status' => 'received'])->assertOk();
        $this->assertSame(4, $this->phone->fresh()->stock_qty);
        $this->putJson("/api/admin/returns/{$return->id}", ['status' => 'refunded', 'refund_amount' => 45000, 'refund_method' => 'bkash'])->assertOk();

        $order->refresh();
        $this->assertSame('delivered', $order->status);                    // one item is still with the customer
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_inventory_adjustments_are_logged(): void
    {
        $this->staff();
        $this->postJson('/api/admin/inventory/adjust', ['product_id' => $this->phone->id, 'mode' => 'add', 'qty' => 10, 'reason' => 'restock'])
            ->assertOk()->assertJsonPath('stock_qty', 15);
        $this->postJson('/api/admin/inventory/adjust', ['product_id' => $this->phone->id, 'mode' => 'set', 'qty' => 12, 'reason' => 'adjustment', 'note' => 'Counted'])
            ->assertOk()->assertJsonPath('stock_qty', 12);
        $this->postJson('/api/admin/inventory/adjust', ['product_id' => $this->phone->id, 'mode' => 'remove', 'qty' => 13, 'reason' => 'adjustment'])
            ->assertJsonValidationErrors('qty');
        $this->postJson('/api/admin/inventory/adjust', ['product_id' => $this->laptop->id, 'mode' => 'add', 'qty' => 1, 'reason' => 'restock'])
            ->assertJsonValidationErrors('variant_id');

        $this->getJson('/api/admin/inventory/movements')->assertOk()->assertJsonPath('items.data.0.change', -3)->assertJsonPath('items.data.0.note', 'Counted')->assertJsonPath('items.data.0.reason', 'adjustment');
        $this->getJson('/api/admin/inventory?status=low')->assertOk()->assertJsonPath('items.data.0.name', 'IdeaPad');
    }

    public function test_wishlist_toggle_and_sync(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']), 'sanctum');
        $this->postJson("/api/me/wishlist/{$this->phone->id}")->assertOk()->assertJsonPath('saved', true);
        $this->postJson('/api/me/wishlist/sync', ['ids' => [$this->laptop->id, 999]])->assertOk()->assertJsonCount(2, 'ids');
        $this->postJson("/api/me/wishlist/{$this->phone->id}")->assertJsonPath('saved', false);
        $this->getJson('/api/me/wishlist')->assertOk()->assertJsonPath('products.0.name', 'IdeaPad');
    }

    public function test_admin_lists_load_with_counts(): void
    {
        $this->checkout([['product_id' => $this->phone->id, 'qty' => 1]])->assertCreated();
        Courier::create(['name' => 'RedX']);
        $customer = User::factory()->create(['role' => 'customer']);
        $this->staff();

        $this->getJson('/api/admin/orders?status=pending&search=0171')->assertOk()
            ->assertJsonPath('counts.pending', 1)->assertJsonPath('counts.all', 1)->assertJsonPath('items.total', 1);
        $this->getJson('/api/admin/orders?status=to_ship')->assertOk()->assertJsonPath('items.total', 0);
        $this->getJson('/api/admin/returns')->assertOk()->assertJsonPath('counts.open', 0);
        $this->getJson('/api/admin/couriers')->assertOk()->assertJsonPath('0.name', 'RedX');
        $this->getJson('/api/admin/customers?sort=spent')->assertOk()->assertJsonPath('counts.all', 1);
        $this->getJson("/api/admin/customers/{$customer->id}")->assertOk();
        $this->getJson('/api/admin/shipping-methods')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/admin/orders/products?q=gal')->assertOk()->assertJsonPath('0.name', 'Galaxy A55');
        $this->getJson('/api/admin/inventory?sort=value&dir=desc')->assertOk();
        $this->getJson('/api/product-cards?ids=' . $this->phone->id)->assertOk()->assertJsonPath('0.name', 'Galaxy A55');
    }

    public function test_customers_cannot_open_admin_orders(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']), 'sanctum');
        $this->getJson('/api/admin/orders')->assertForbidden();
    }
}
