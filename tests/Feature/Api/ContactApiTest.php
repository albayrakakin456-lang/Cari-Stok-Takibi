<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_token_can_create_a_contact_for_its_owner(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $response = $this->postJson('/api/v1/contacts', [
            'name' => 'ABC Teknoloji',
            'type' => 'customer',
            'phone' => '05321234567',
            'email' => 'info@abc.test',
            'address' => 'İstanbul',
            'note' => 'Postman entegrasyon müşterisi',
            'user_id' => 999999,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'ABC Teknoloji')
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('contacts', [
            'user_id' => $user->id,
            'name' => 'ABC Teknoloji',
            'type' => 'customer',
        ]);

        $this->assertDatabaseMissing('contacts', [
            'user_id' => 999999,
            'name' => 'ABC Teknoloji',
        ]);
    }

    public function test_read_only_token_gets_403_when_creating_a_contact(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read']);

        $this->postJson('/api/v1/contacts', [
            'name' => 'Yetkisiz Cari',
            'type' => 'customer',
        ])->assertForbidden();

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_contact_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:write']);

        $this->postJson('/api/v1/contacts', [
            'name' => '',
            'type' => 'invalid-type',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type']);
    }
}
