<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_token_can_create_a_product_with_opening_stock(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Kablosuz Mouse',
            'code' => 'MOUSE-001',
            'barcode' => '869000000001',
            'purchase_price' => 300,
            'sale_price' => 450,
            'tax_rate' => 20,
            'min_stock' => 5,
            'opening_stock' => 50,
            'user_id' => 999999,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'MOUSE-001')
            ->assertJsonPath('data.stock', 50)
            ->assertJsonPath('data.user_id', $user->id);

        $productId = $response->json('data.id');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'user_id' => $user->id,
            'code' => 'MOUSE-001',
            'stock' => 50,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'user_id' => $user->id,
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => 50,
            'description' => 'API açılış stoğu girişi',
        ]);
    }

    public function test_read_only_token_gets_403_when_creating_a_product(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read']);

        $this->postJson('/api/v1/products', [])->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_code_is_unique_per_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        Product::query()->create([
            'user_id' => $firstUser->id,
            'name' => 'Birinci Ürün',
            'code' => 'ORTAK-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 20,
            'min_stock' => 1,
            'stock' => 1,
        ]);

        Sanctum::actingAs($secondUser, ['api:write']);

        $payload = [
            'name' => 'İkinci Kullanıcının Ürünü',
            'code' => 'ORTAK-001',
            'purchase_price' => 15,
            'sale_price' => 25,
            'tax_rate' => 20,
            'min_stock' => 1,
            'opening_stock' => 2,
        ];

        $this->postJson('/api/v1/products', $payload)->assertCreated();

        $this->postJson('/api/v1/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }
}
