<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookEndpointApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_own_webhook_endpoint(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $response = $this->postJson('/api/v1/webhook-endpoints', [
            'url' => 'https://receiver.example.com/webhooks/cari-takip',
            'events' => ['invoice.created', 'invoice.cancelled'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.url', 'https://receiver.example.com/webhooks/cari-takip')
            ->assertJsonPath('data.active', true);

        $this->assertStringStartsWith('whsec_', $response->json('data.secret'));

        $endpoint = WebhookEndpoint::query()->firstOrFail();
        $this->assertNotSame($response->json('data.secret'), $endpoint->getRawOriginal('secret'));

        $this->getJson('/api/v1/webhook-endpoints')
            ->assertOk()
            ->assertJsonPath('data.0.id', $endpoint->id)
            ->assertJsonMissingPath('data.0.secret');
    }

    public function test_user_cannot_update_or_delete_another_users_endpoint(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $endpoint = $owner->webhookEndpoints()->create([
            'url' => 'https://receiver.example.com/hook',
            'secret' => 'whsec_secret',
            'events' => ['invoice.created'],
            'active' => true,
        ]);

        Sanctum::actingAs($attacker, ['api:read', 'api:write']);

        $this->patchJson("/api/v1/webhook-endpoints/{$endpoint->id}", [
            'active' => false,
        ])->assertNotFound();

        $this->deleteJson("/api/v1/webhook-endpoints/{$endpoint->id}")
            ->assertNotFound();
    }

    public function test_read_only_token_cannot_manage_webhook_endpoints(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read']);

        $this->getJson('/api/v1/webhook-endpoints')->assertOk();
        $this->postJson('/api/v1/webhook-endpoints', [])->assertForbidden();
    }

    public function test_endpoint_validates_supported_events_and_unique_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:write']);

        $payload = [
            'url' => 'https://receiver.example.com/hook',
            'events' => ['invoice.created'],
        ];

        $this->postJson('/api/v1/webhook-endpoints', $payload)->assertCreated();
        $this->postJson('/api/v1/webhook-endpoints', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('url');

        $this->postJson('/api/v1/webhook-endpoints', [
            'url' => 'https://receiver.example.com/other',
            'events' => ['unsupported.event'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('events.0');
    }

    public function test_deleted_endpoint_can_be_recreated_with_a_new_secret(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $payload = [
            'url' => 'https://receiver.example.com/reusable-hook',
            'events' => ['invoice.created'],
        ];

        $first = $this->postJson('/api/v1/webhook-endpoints', $payload)
            ->assertCreated();

        $endpointId = $first->json('data.id');
        $firstSecret = $first->json('data.secret');

        $this->deleteJson("/api/v1/webhook-endpoints/{$endpointId}")
            ->assertOk();

        $second = $this->postJson('/api/v1/webhook-endpoints', $payload)
            ->assertCreated()
            ->assertJsonPath('data.id', $endpointId)
            ->assertJsonPath('data.active', true);

        $this->assertNotSame($firstSecret, $second->json('data.secret'));
        $this->assertDatabaseCount('webhook_endpoints', 1);
        $this->assertNull(WebhookEndpoint::query()->findOrFail($endpointId)->deleted_at);
    }
}
