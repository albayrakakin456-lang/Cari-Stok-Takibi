<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_token_management_page(): void
    {
        $this->get('/settings/api-tokens')->assertRedirect('/login');
    }

    public function test_normal_user_cannot_open_or_use_token_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/settings/api-tokens')->assertForbidden();
        $this->actingAs($user)->post('/settings/api-tokens', [
            'user_id' => $user->id,
            'name' => 'Yetkisiz token',
            'abilities' => ['api:read'],
        ])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_create_a_token_from_panel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post('/settings/api-tokens', [
            'user_id' => $user->id,
            'name' => 'ERP entegrasyonu',
            'abilities' => ['api:read', 'api:write'],
        ]);

        $response
            ->assertRedirect('/settings/api-tokens')
            ->assertSessionHas('created_api_token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'ERP entegrasyonu',
            'abilities' => '["api:read","api:write"]',
        ]);
    }

    public function test_write_permission_also_adds_read_permission(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)->post('/settings/api-tokens', [
            'user_id' => $user->id,
            'name' => 'Webhook sistemi',
            'abilities' => ['api:write'],
        ])->assertRedirect('/settings/api-tokens');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'abilities' => '["api:write","api:read"]',
        ]);
    }

    public function test_admin_can_revoke_any_users_token(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $tokenOwner = User::factory()->create();
        $token = $tokenOwner->createToken('ERP tokenı')->accessToken;

        $this->actingAs($admin)
            ->delete("/settings/api-tokens/{$token->id}")
            ->assertRedirect('/settings/api-tokens');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }
}
