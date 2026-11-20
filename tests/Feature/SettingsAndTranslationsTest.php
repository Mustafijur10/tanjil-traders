<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Setting;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsAndTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_bootstrap_returns_public_settings_only(): void
    {
        Setting::put(['store.name' => 'My Shop', 'integrations.google_translate_key' => 'secret-key']);

        $settings = $this->getJson('/api/bootstrap')->assertOk()->json('settings');

        $this->assertSame('My Shop', $settings['store.name']);
        $this->assertArrayNotHasKey('integrations.google_translate_key', $settings);
    }

    public function test_unknown_setting_keys_are_ignored_and_secrets_are_masked(): void
    {
        $saved = $this->actingAs($this->admin(), 'sanctum')
            ->putJson('/api/admin/settings', ['values' => [
                'store.name' => 'Tanjil', 'hacker.key' => 'x', 'integrations.pagespeed_key' => 'abc',
            ]])
            ->assertOk()
            ->json();

        $this->assertSame('Tanjil', $saved['store.name']);
        $this->assertSame('__set__', $saved['integrations.pagespeed_key']);
        $this->assertArrayNotHasKey('hacker.key', $saved);

        // Sending the mask back must not overwrite the real secret.
        $this->putJson('/api/admin/settings', ['values' => ['integrations.pagespeed_key' => '__set__']]);
        $this->assertSame('abc', Setting::get('integrations.pagespeed_key'));
    }

    public function test_saving_content_registers_it_for_translation(): void
    {
        Category::create(['name' => 'Gaming Laptops', 'slug' => 'gaming-laptops']);

        $this->assertDatabaseHas('translations', ['source' => 'Gaming Laptops', 'group' => 'content', 'status' => 'missing']);
    }

    public function test_admin_fixes_a_bangla_line_and_it_reaches_the_storefront(): void
    {
        Translation::register('Add to cart');
        $row = Translation::first();

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/admin/translations/{$row->id}", ['bn' => 'কার্টে যোগ করুন'])
            ->assertOk()
            ->assertJsonPath('status', 'reviewed');

        $this->getJson('/api/bootstrap')->assertJsonPath('translations.bn.Add to cart', 'কার্টে যোগ করুন');
    }

    public function test_clearing_a_bangla_line_marks_it_missing(): void
    {
        $row = Translation::create(['hash' => Translation::hashOf('Cart'), 'source' => 'Cart', 'bn' => 'কার্ট', 'status' => 'reviewed']);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/admin/translations/{$row->id}", ['bn' => ''])
            ->assertJsonPath('status', 'missing');
    }

    public function test_auto_translate_marks_results_for_review(): void
    {
        Setting::put(['integrations.google_translate_key' => 'k']);
        Translation::register('Free delivery');
        Http::fake(['translation.googleapis.com/*' => Http::response([
            'data' => ['translations' => [['translatedText' => 'ফ্রি ডেলিভারি']]],
        ])]);

        $this->actingAs($this->admin(), 'sanctum')->postJson('/api/admin/translations/auto')->assertOk();

        $this->assertDatabaseHas('translations', ['source' => 'Free delivery', 'bn' => 'ফ্রি ডেলিভারি', 'status' => 'auto']);
    }

    public function test_auto_translate_without_key_explains_what_to_do(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/translations/auto')
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Add a Google Translate API key in Settings > Integrations first.']);
    }
}
