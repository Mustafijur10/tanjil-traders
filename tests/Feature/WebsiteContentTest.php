<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): void
    {
        $this->actingAs(User::factory()->staff()->create(), 'sanctum');
    }

    private function product(array $extra = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'phones'], ['name' => 'Phones']);

        return Product::create(array_merge([
            'name' => 'Phone ' . uniqid(), 'slug' => uniqid('p-'), 'category_id' => $cat->id, 'price' => 1000, 'stock_qty' => 5, 'status' => 'active',
        ], $extra));
    }

    public function test_banner_rules_depend_on_type(): void
    {
        $this->staff();

        $this->postJson('/api/admin/banners', ['type' => 'hero'])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson('/api/admin/banners', ['type' => 'marquee'])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->postJson('/api/admin/banners', ['type' => 'hero', 'image' => 'banners/a.jpg', 'link' => 'javascript:alert(1)'])
            ->assertUnprocessable()->assertJsonValidationErrors('link');

        $this->postJson('/api/admin/banners', ['type' => 'marquee', 'title' => 'Free delivery over ৳20,000'])->assertCreated();
    }

    public function test_only_live_banners_reach_the_storefront(): void
    {
        Banner::create(['type' => 'marquee', 'title' => 'Live now', 'is_active' => true]);
        Banner::create(['type' => 'marquee', 'title' => 'Switched off', 'is_active' => false]);
        Banner::create(['type' => 'marquee', 'title' => 'Ended', 'ends_at' => now()->subDay()]);
        Banner::create(['type' => 'marquee', 'title' => 'Not started', 'starts_at' => now()->addDay()]);

        $titles = collect($this->getJson('/api/bootstrap')->assertOk()->json('announcements'))->pluck('title')->all();
        $this->assertSame(['Live now'], $titles);
    }

    public function test_home_returns_sections_in_order_and_skips_empty_ones(): void
    {
        $this->product(['is_featured' => true, 'name' => 'Featured phone']);
        $this->product(['sale_price' => 800, 'name' => 'Deal phone']);
        $brand = Brand::create(['name' => 'Empty brand', 'slug' => 'empty']);

        HomeSection::create(['type' => 'deals', 'title' => 'Deals', 'sort_order' => 1]);
        HomeSection::create(['type' => 'featured', 'title' => 'Featured', 'sort_order' => 2]);
        HomeSection::create(['type' => 'brand', 'title' => 'Nothing here', 'brand_id' => $brand->id, 'sort_order' => 3]);
        HomeSection::create(['type' => 'new', 'title' => 'Hidden', 'is_active' => false, 'sort_order' => 4]);

        $sections = $this->getJson('/api/home')->assertOk()->json('sections');

        $this->assertSame(['Deals', 'Featured'], array_column($sections, 'title'));
        $this->assertSame('Deal phone', $sections[0]['items'][0]['name']);
        $this->assertSame(20, $sections[0]['items'][0]['discount_pct']);
    }

    public function test_draft_products_never_appear_on_the_home_page(): void
    {
        $this->product(['is_featured' => true, 'status' => 'draft']);
        HomeSection::create(['type' => 'featured', 'title' => 'Featured']);

        $this->assertSame([], $this->getJson('/api/home')->json('sections'));
    }

    public function test_page_content_is_cleaned_and_reserved_addresses_are_refused(): void
    {
        $this->staff();

        $this->postJson('/api/admin/pages', ['title' => 'Cart', 'slug' => 'cart'])->assertUnprocessable()->assertJsonValidationErrors('slug');

        $page = $this->postJson('/api/admin/pages', [
            'title' => 'Warranty policy', 'is_published' => true,
            'content' => '<h2>Warranty</h2><p onclick="steal()">One year.</p><script>alert(1)</script>',
        ])->assertCreated()->json();

        $this->assertSame('warranty-policy', $page['slug']);
        $this->assertStringNotContainsString('script', $page['content']);
        $this->assertStringNotContainsString('onclick', $page['content']);

        $this->getJson('/api/pages/warranty-policy')->assertOk()->assertJsonPath('title', 'Warranty policy');
    }

    public function test_unpublished_pages_are_not_public(): void
    {
        \App\Models\Page::create(['title' => 'Draft', 'slug' => 'draft', 'is_published' => false]);

        $this->getJson('/api/pages/draft')->assertNotFound();
    }

    public function test_contact_form_needs_a_way_to_reply_and_blocks_bots(): void
    {
        $this->postJson('/api/contact', ['name' => 'Rahim', 'message' => 'Is the A55 in stock?'])
            ->assertUnprocessable()->assertJsonValidationErrors(['phone', 'email']);

        $this->postJson('/api/contact', ['name' => 'Bot', 'phone' => '1', 'message' => 'spam spam', 'website' => 'http://x'])
            ->assertUnprocessable();

        $this->postJson('/api/contact', ['name' => 'Rahim', 'phone' => '01711000000', 'message' => 'Is the A55 in stock?'])->assertCreated();
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_product_description_html_is_cleaned(): void
    {
        $this->staff();
        $cat = Category::create(['name' => 'Laptops', 'slug' => 'laptops']);

        $id = $this->postJson('/api/admin/products', [
            'name' => 'Test laptop', 'category_id' => $cat->id, 'price' => 1000, 'status' => 'active',
            'description' => '<p>Fast<img src="x" onerror="hack()"></p><iframe src="evil"></iframe>',
        ])->assertCreated()->json('id');

        $html = Product::find($id)->description;
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('iframe', $html);
        $this->assertStringContainsString('Fast', $html);
    }
}
