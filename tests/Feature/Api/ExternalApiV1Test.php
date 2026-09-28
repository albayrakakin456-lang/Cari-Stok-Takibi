<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExternalApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_support_lists_only_return_the_token_owners_records(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownContact = $this->createContact($user, 'Kendi Müşterim');
        $this->createContact($otherUser, 'Başka Müşteri');
        $ownProduct = $this->createProduct($user, 'Kendi Ürünüm', 'KENDI-1', 10);
        $this->createProduct($otherUser, 'Başka Ürün', 'BASKA-1', 10);

        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $this->getJson('/api/v1/contacts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ownContact->id)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ownProduct->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_invoice_creation_and_cancellation_keep_stock_and_cash_consistent(): void
    {
        $user = User::factory()->create();
        $contact = $this->createContact($user, 'API Müşterisi');
        $product = $this->createProduct($user, 'API Ürünü', 'API-1', 10, 25);

        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $createResponse = $this->postJson('/api/v1/invoices', [
            'contact_id' => $contact->id,
            'invoice_number' => 'API-FAT-1',
            'external_reference' => 'EXT-API-FAT-1',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.total_amount', '50.00')
            ->assertJsonPath('data.items.0.quantity', 2);

        $invoiceId = $createResponse->json('data.id');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
        $this->assertDatabaseHas('stock_movements', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('cash_transactions', [
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'type' => 'in',
            'amount' => 50,
        ]);

        $this->deleteJson("/api/v1/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('sales', [
            'id' => $invoiceId,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 10]);
        $this->assertDatabaseHas('stock_movements', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('cash_transactions', [
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'type' => 'out',
            'amount' => 50,
        ]);
    }

    public function test_user_cannot_view_or_cancel_another_users_invoice(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $contact = $this->createContact($owner, 'Fatura Sahibi');

        $invoice = Sale::query()->create([
            'user_id' => $owner->id,
            'contact_id' => $contact->id,
            'invoice_number' => 'GIZLI-FATURA',
            'total_amount' => 100,
        ]);

        Sanctum::actingAs($attacker, ['api:read', 'api:write']);

        $this->getJson("/api/v1/invoices/{$invoice->id}")->assertNotFound();
        $this->deleteJson("/api/v1/invoices/{$invoice->id}")->assertNotFound();
        $this->assertDatabaseHas('sales', ['id' => $invoice->id]);
    }

    public function test_insufficient_stock_rolls_back_every_invoice_side_effect(): void
    {
        $user = User::factory()->create();
        $contact = $this->createContact($user, 'Stok Test Müşterisi');
        $product = $this->createProduct($user, 'Az Stoklu Ürün', 'AZ-1', 1, 25);

        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $this->postJson('/api/v1/invoices', [
            'contact_id' => $contact->id,
            'invoice_number' => 'STOK-YETERSIZ-1',
            'external_reference' => 'EXT-STOK-YETERSIZ-1',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseMissing('sales', ['invoice_number' => 'STOK-YETERSIZ-1']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_transactions', 0);
    }

    public function test_repeated_external_reference_returns_the_original_invoice_without_double_writes(): void
    {
        $user = User::factory()->create();
        $contact = $this->createContact($user, 'Tekrar Test Müşterisi');
        $product = $this->createProduct($user, 'Tekrar Test Ürünü', 'TEKRAR-1', 10, 20);
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $payload = [
            'contact_id' => $contact->id,
            'invoice_number' => 'TEKRAR-FAT-1',
            'external_reference' => 'ERP-TEKRAR-1',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $firstResponse = $this->postJson('/api/v1/invoices', $payload)->assertCreated();
        $secondResponse = $this->postJson('/api/v1/invoices', $payload)
            ->assertOk()
            ->assertJsonPath('meta.idempotent_replay', true);

        $this->assertSame($firstResponse->json('data.id'), $secondResponse->json('data.id'));
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('cash_transactions', 1);

        $changedPayload = $payload;
        $changedPayload['items'][0]['quantity'] = 3;
        $this->postJson('/api/v1/invoices', $changedPayload)->assertConflict();
    }

    public function test_invalid_bearer_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer gecersiz-token')
            ->getJson('/api/v1/invoices')
            ->assertUnauthorized();
    }

    public function test_read_only_token_cannot_create_an_invoice(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:read']);

        $this->getJson('/api/v1/invoices')->assertOk();
        $this->postJson('/api/v1/invoices', [])->assertForbidden();
    }

    public function test_api_rate_limit_is_enforced(): void
    {
        $user = User::factory()->create(['id' => 987654]);
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->getJson('/api/v1/products')->assertOk();
        }

        $this->getJson('/api/v1/products')->assertTooManyRequests();
    }

    private function createContact(User $user, string $name): Contact
    {
        return Contact::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'customer',
        ]);
    }

    private function createProduct(
        User $user,
        string $name,
        string $code,
        int $stock,
        float $salePrice = 10,
    ): Product {
        return Product::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'code' => $code,
            'sale_price' => $salePrice,
            'purchase_price' => 1,
            'stock' => $stock,
        ]);
    }
}
