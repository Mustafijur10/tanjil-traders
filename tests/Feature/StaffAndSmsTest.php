<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\ProductSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StaffAndSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_open_their_areas(): void
    {
        $staff = User::factory()->staff()->create(['permissions' => ['orders']]);
        $this->actingAs($staff, 'sanctum');

        $this->getJson('/api/admin/orders')->assertOk();
        $this->getJson('/api/admin/products')->assertForbidden();
        $this->getJson('/api/admin/analytics/sales')->assertForbidden();
        $this->getJson('/api/admin/settings')->assertForbidden();
        $this->getJson('/api/admin/shipping-methods')->assertOk(); // needed to create orders

        // No list = every area except settings
        $this->actingAs(User::factory()->staff()->create(['permissions' => null]), 'sanctum');
        $this->getJson('/api/admin/products')->assertOk();
        $this->getJson('/api/admin/staff')->assertForbidden();
    }

    public function test_admin_manages_staff_but_keeps_one_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum');

        $id = $this->postJson('/api/admin/staff', [
            'name' => 'Karim', 'email' => 'karim@shop.test', 'role' => 'staff', 'permissions' => ['orders', 'catalog', 'nope'], 'password' => 'secret123',
        ])->assertJsonValidationErrors('permissions.2')->assertStatus(422)->json('id');
        $id = $this->postJson('/api/admin/staff', [
            'name' => 'Karim', 'email' => 'karim@shop.test', 'role' => 'staff', 'permissions' => ['orders', 'catalog'], 'password' => 'secret123',
        ])->assertCreated()->json('id');
        $this->assertSame(['orders', 'catalog'], User::find($id)->permissions);

        // Cannot demote yourself, or remove the last admin
        $this->putJson("/api/admin/staff/{$admin->id}", ['name' => 'A', 'email' => $admin->email, 'role' => 'staff'])->assertJsonValidationErrors('role');
        $this->deleteJson("/api/admin/staff/{$admin->id}")->assertJsonValidationErrors('user');

        $this->getJson('/api/admin/staff')->assertOk()->assertJsonCount(2, 'items');
        $this->deleteJson("/api/admin/staff/{$id}")->assertNoContent();
    }

    public function test_order_sms_are_sent_and_logged(): void
    {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202, 'success_message' => 'SMS Submitted Successfully'])]);
        Setting::put([
            'sms.enabled' => true, 'sms.api_key' => 'k', 'sms.sender_id' => '8809', 'store.name' => 'Tanjil',
            'sms.on_placed' => true, 'sms.on_shipped' => true, 'notify.order_sms' => '01811111111',
        ]);

        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = app(ProductSaver::class)->save(['name' => 'Galaxy', 'category_id' => $cat->id, 'price' => 1000, 'stock_qty' => 5, 'status' => 'active']);
        $ship = ShippingMethod::create(['name' => 'Dhaka', 'charge' => 0]);

        $this->postJson('/api/checkout', [
            'items' => [['product_id' => $product->id, 'qty' => 1]], 'name' => 'Rahim Uddin', 'phone' => '01712345678',
            'address' => 'Road 1', 'city' => 'Dhaka', 'shipping_method_id' => $ship->id, 'payment_method' => 'cod',
        ])->assertCreated();
        $order = Order::first();

        $this->assertDatabaseHas('sms_logs', ['phone' => '8801712345678', 'event' => 'order_placed', 'status' => 'sent']);
        $this->assertDatabaseHas('sms_logs', ['phone' => '8801811111111', 'event' => 'shop_new_order']);
        $this->assertStringContainsString('Dear Rahim', \DB::table('sms_logs')->where('event', 'order_placed')->value('message'));

        $this->actingAs(User::factory()->staff()->create(), 'sanctum');
        $courier = Courier::create(['name' => 'Steadfast']);
        $this->putJson("/api/admin/orders/{$order->id}", ['status' => 'confirmed'])->assertOk();
        $this->putJson("/api/admin/orders/{$order->id}", ['status' => 'shipped', 'courier_id' => $courier->id, 'tracking_code' => 'SF9'])->assertOk();

        $shipped = \DB::table('sms_logs')->where('event', 'order_shipped')->value('message');
        $this->assertStringContainsString('Steadfast', $shipped);
        $this->assertStringContainsString('SF9', $shipped);
        Http::assertSentCount(4); // placed, shop alert, confirmed, shipped
    }

    public function test_sms_test_reports_gateway_errors(): void
    {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 1002, 'error_message' => 'Sender Id/Masking Not Found'])]);
        Setting::put(['sms.enabled' => true, 'sms.api_key' => 'k']);
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum');

        $this->postJson('/api/admin/sms/test', ['phone' => '01712345678'])->assertStatus(422)->assertJsonPath('sent', false);
        $this->postJson('/api/admin/sms/test', ['phone' => '12345'])->assertStatus(422);
        $this->getJson('/api/admin/sms/logs')->assertOk()->assertJsonPath('counts.failed', 2);
    }
}
