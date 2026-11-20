<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_log_in_to_admin_panel_with_email(): void
    {
        User::factory()->admin()->create(['email' => 'boss@shop.test']);

        $this->postJson('/api/login', ['login' => 'boss@shop.test', 'password' => 'password', 'admin' => true])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'role'], 'token'])
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonMissingPath('user.password');
    }

    public function test_customer_can_log_in_with_phone(): void
    {
        User::factory()->create(['phone' => '01711000000']);

        $this->postJson('/api/login', ['login' => '01711000000', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.role', 'customer');
    }

    public function test_customer_cannot_log_in_to_admin_panel(): void
    {
        User::factory()->create(['email' => 'buyer@shop.test']);

        $this->postJson('/api/login', ['login' => 'buyer@shop.test', 'password' => 'password', 'admin' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login');
    }

    public function test_wrong_password_and_disabled_accounts_are_rejected(): void
    {
        User::factory()->create(['email' => 'a@shop.test']);
        User::factory()->create(['email' => 'off@shop.test', 'is_active' => false]);

        $this->postJson('/api/login', ['login' => 'a@shop.test', 'password' => 'wrong'])->assertUnprocessable();
        $this->postJson('/api/login', ['login' => 'off@shop.test', 'password' => 'password'])->assertUnprocessable();
    }

    public function test_registration_always_creates_a_customer(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Rahim', 'phone' => '01811000000', 'password' => 'secret123',
            'password_confirmation' => 'secret123', 'role' => 'admin',
        ])->assertCreated()->assertJsonPath('user.role', 'customer');
    }

    public function test_customer_cannot_reach_admin_api(): void
    {
        $this->getJson('/api/admin/translations')->assertUnauthorized();

        $customer = User::factory()->create();
        $this->actingAs($customer, 'sanctum')->getJson('/api/admin/translations')->assertForbidden();
    }

    public function test_staff_cannot_change_store_settings(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff, 'sanctum')->getJson('/api/admin/translations')->assertOk();
        $this->actingAs($staff, 'sanctum')->putJson('/api/admin/settings', ['values' => ['store.name' => 'X']])->assertForbidden();
    }

    public function test_preferences_are_merged_not_replaced(): void
    {
        $user = User::factory()->staff()->create(['preferences' => ['theme' => 'dark']]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/me/preferences', ['preferences' => ['locale' => 'bn']])
            ->assertOk()
            ->assertJsonPath('preferences.theme', 'dark')
            ->assertJsonPath('preferences.locale', 'bn');
    }
}
