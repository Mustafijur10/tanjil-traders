<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\SearchLog;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\Visit;
use App\Services\ProductSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const UA = 'Mozilla/5.0 (Linux; Android 14; SM-A556E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';
    private const VID = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6';

    private function pageView(array $data)
    {
        return $this->withHeaders(['User-Agent' => self::UA])->postJson('/api/track', $data + [
            'visitor_id' => self::VID, 'session_id' => 'f1f2f3f4f5f6f7f8f9f0f1f2f3f4f5f6',
        ]);
    }

    public function test_page_views_record_source_device_and_vitals(): void
    {
        $id = $this->pageView(['path' => '/', 'page_type' => 'home', 'referrer' => 'https://www.google.com/'])->assertOk()->json('id');
        $visit = Visit::find($id);
        $this->assertSame(['search', 'google', 'mobile', 'Chrome', 'Android'], [$visit->source, $visit->source_name, $visit->device, $visit->browser, $visit->os]);

        // A later page in the same visit keeps the source
        $next = $this->pageView(['path' => '/deals', 'page_type' => 'deals'])->json('id');
        $this->assertSame('search', Visit::find($next)->source);

        // Vitals only with the matching visitor id
        $this->postJson("/api/track/{$id}", ['visitor_id' => 'zzzzzzzzzzzzzzzzzzzz', 'lcp' => 1800])->assertNoContent();
        $this->assertNull($visit->fresh()->lcp);
        $this->postJson("/api/track/{$id}", ['visitor_id' => self::VID, 'lcp' => 1834.6, 'cls' => 0.05, 'inp' => 120])->assertNoContent();
        $this->assertSame([1835, 0.05, 120], [$visit->fresh()->lcp, $visit->fresh()->cls, $visit->fresh()->inp]);
    }

    public function test_bots_and_staff_are_not_counted(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->postJson('/api/track', ['visitor_id' => self::VID, 'session_id' => self::VID, 'path' => '/'])->assertJsonPath('id', null);
        $this->actingAs(User::factory()->staff()->create(), 'sanctum');
        $this->pageView(['path' => '/'])->assertJsonPath('id', null);
        $this->assertDatabaseCount('visits', 0);
    }

    public function test_orders_are_credited_to_the_traffic_source(): void
    {
        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = app(ProductSaver::class)->save(['name' => 'Galaxy', 'category_id' => $cat->id, 'price' => 1000, 'cost_price' => 700, 'stock_qty' => 9, 'status' => 'active']);
        $ship = ShippingMethod::create(['name' => 'Dhaka', 'charge' => 0]);

        $this->pageView(['path' => '/', 'referrer' => 'https://facebook.com/']);
        $this->pageView(['path' => '/product/' . $product->slug, 'page_type' => 'product']);
        $this->withHeaders(['X-Visitor' => self::VID])->postJson('/api/checkout', [
            'items' => [['product_id' => $product->id, 'qty' => 2]], 'name' => 'A', 'phone' => '01712345678',
            'address' => 'Road 1', 'city' => 'Dhaka', 'shipping_method_id' => $ship->id, 'payment_method' => 'cod',
        ])->assertCreated();

        $this->assertSame('social', Order::first()->traffic_source);
        $this->assertSame($product->id, Visit::where('page_type', 'product')->value('product_id'));

        SearchLog::create(['query' => 'iphone 16', 'results_count' => 0]);
        $this->actingAs(User::factory()->staff()->create(), 'sanctum');

        $sales = $this->getJson('/api/admin/analytics/sales?days=7')->assertOk();
        $this->assertSame(2000.0, (float) $sales->json('totals.revenue.value'));
        $this->assertSame(600.0, (float) $sales->json('totals.profit.value'));
        $this->assertSame('social', $sales->json('by_traffic.0.name'));
        $this->assertCount(7, $sales->json('series'));

        $this->getJson('/api/admin/analytics/traffic?days=30')->assertOk()->assertJsonPath('sources.0.name', 'social')->assertJsonPath('sources.0.orders', 1);
        $this->getJson('/api/admin/analytics/products?days=30')->assertOk()->assertJsonPath('top.0.units', 2)->assertJsonPath('viewed.0.orders', 1);
        $this->getJson('/api/admin/analytics/customers?days=30')->assertOk()->assertJsonPath('totals.new_buyers', 1);
        $this->getJson('/api/admin/analytics/inventory?days=30')->assertOk()->assertJsonPath('running_out.0.stock', 7);
        $this->getJson('/api/admin/analytics/search?days=30')->assertOk()->assertJsonPath('zero.0.query', 'iphone 16');
        $this->getJson('/api/admin/analytics/seo?days=30')->assertOk()->assertJsonPath('console.configured', false);
        $this->getJson('/api/admin/analytics/live')->assertOk()->assertJsonPath('now', 1);
        $this->getJson('/api/admin/analytics/sales?days=365')->assertOk()->assertJsonPath('period.bucket', 'month');
    }

    public function test_sitemap_lists_published_pages(): void
    {
        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        app(ProductSaver::class)->save(['name' => 'Galaxy', 'category_id' => $cat->id, 'price' => 1, 'status' => 'active']);
        app(ProductSaver::class)->save(['name' => 'Draft', 'category_id' => $cat->id, 'price' => 1, 'status' => 'draft']);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('/product/galaxy', $xml);
        $this->assertStringNotContainsString('/product/draft', $xml);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: ');
    }
}
