<?php

namespace Tests\Feature\Api;

use App\Jobs\DeliverWebhook;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\WebhookUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_creation_and_cancellation_queue_one_webhook_each(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = Contact::query()->create([
            'user_id' => $user->id,
            'name' => 'Webhook Müşterisi',
            'type' => 'customer',
        ]);
        $product = Product::query()->create([
            'user_id' => $user->id,
            'name' => 'Webhook Ürünü',
            'code' => 'WEBHOOK-1',
            'purchase_price' => 10,
            'sale_price' => 100,
            'tax_rate' => 20,
            'min_stock' => 1,
            'stock' => 10,
        ]);
        $user->webhookEndpoints()->create([
            'url' => 'https://receiver.example.com/hook',
            'secret' => 'whsec_test_secret',
            'events' => ['invoice.created', 'invoice.cancelled'],
            'active' => true,
        ]);
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $payload = [
            'contact_id' => $contact->id,
            'invoice_number' => 'WEBHOOK-FAT-1',
            'external_reference' => 'WEBHOOK-REF-1',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $invoiceId = $this->postJson('/api/v1/invoices', $payload)
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseCount('webhook_deliveries', 1);
        $this->assertDatabaseHas('webhook_deliveries', [
            'event_type' => 'invoice.created',
            'status' => 'pending',
        ]);
        Queue::assertPushedOn('webhooks', DeliverWebhook::class);

        // İdempotent fatura tekrarı ikinci bir event oluşturmamalı.
        $this->postJson('/api/v1/invoices', $payload)->assertOk();
        $this->assertDatabaseCount('webhook_deliveries', 1);

        $this->deleteJson("/api/v1/invoices/{$invoiceId}")->assertOk();
        $this->assertDatabaseCount('webhook_deliveries', 2);
        $this->assertDatabaseHas('webhook_deliveries', [
            'event_type' => 'invoice.cancelled',
            'status' => 'pending',
        ]);

        // İkinci iptal yeni event veya ters hareket üretmemeli.
        $this->deleteJson("/api/v1/invoices/{$invoiceId}")->assertOk();
        $this->assertDatabaseCount('webhook_deliveries', 2);
        Queue::assertPushed(DeliverWebhook::class, 2);
    }

    public function test_job_sends_a_signed_webhook_and_records_success(): void
    {
        $user = User::factory()->create();
        $endpoint = $user->webhookEndpoints()->create([
            'url' => 'https://receiver.example.com/hook',
            'secret' => 'whsec_test_secret',
            'events' => ['invoice.created'],
            'active' => true,
        ]);
        $delivery = $endpoint->deliveries()->create([
            'event_id' => '8e3f2650-c412-4fcc-9d69-63fbe8239467',
            'event_type' => 'invoice.created',
            'payload' => [
                'id' => '8e3f2650-c412-4fcc-9d69-63fbe8239467',
                'type' => 'invoice.created',
                'api_version' => 'v1',
                'occurred_at' => now()->toISOString(),
                'data' => ['invoice' => ['id' => 15]],
            ],
            'status' => 'pending',
        ]);

        Http::fake([
            'https://receiver.example.com/hook' => Http::response('', 204),
        ]);

        (new DeliverWebhook($delivery))->handle(app(WebhookUrlGuard::class));

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertSame(204, $delivery->response_status);
        $this->assertNotNull($delivery->delivered_at);

        Http::assertSent(function (Request $request): bool {
            $timestamp = $request->header('X-Webhook-Timestamp')[0] ?? '';
            $signature = $request->header('X-Webhook-Signature')[0] ?? '';
            $expected = 'v1='.hash_hmac(
                'sha256',
                $timestamp.'.'.$request->body(),
                'whsec_test_secret',
            );

            return $request->url() === 'https://receiver.example.com/hook'
                && $request->header('X-Webhook-Id')[0] === '8e3f2650-c412-4fcc-9d69-63fbe8239467'
                && hash_equals($expected, $signature);
        });
    }

    public function test_test_endpoint_creates_a_queued_delivery(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $endpoint = $user->webhookEndpoints()->create([
            'url' => 'https://receiver.example.com/hook',
            'secret' => 'whsec_test_secret',
            'events' => ['invoice.created'],
            'active' => true,
        ]);
        Sanctum::actingAs($user, ['api:write']);

        $this->postJson("/api/v1/webhook-endpoints/{$endpoint->id}/test")
            ->assertAccepted()
            ->assertJsonPath('data.event_type', 'webhook.test');

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => 'webhook.test',
            'status' => 'pending',
        ]);
        Queue::assertPushedOn('webhooks', DeliverWebhook::class);
    }

    public function test_permanent_4xx_response_is_recorded_without_retry(): void
    {
        $delivery = $this->createPendingDelivery();
        Http::fake(['*' => Http::response('bad request', 400)]);

        (new DeliverWebhook($delivery))->handle(app(WebhookUrlGuard::class));

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame(400, $delivery->response_status);
        $this->assertSame(1, $delivery->attempt_count);
    }

    public function test_server_error_is_left_pending_and_thrown_for_queue_retry(): void
    {
        $delivery = $this->createPendingDelivery();
        Http::fake(['*' => Http::response('temporary failure', 503)]);

        try {
            (new DeliverWebhook($delivery))->handle(app(WebhookUrlGuard::class));
            $this->fail('503 cevabında Job yeniden denenmek üzere exception fırlatmalıydı.');
        } catch (RuntimeException) {
            // Beklenen davranış: Laravel queue worker Job'ı backoff planıyla tekrar dener.
        }

        $delivery->refresh();
        $this->assertSame('pending', $delivery->status);
        $this->assertSame(503, $delivery->response_status);
        $this->assertSame(1, $delivery->attempt_count);
    }

    private function createPendingDelivery(): WebhookDelivery
    {
        $user = User::factory()->create();
        $endpoint = $user->webhookEndpoints()->create([
            'url' => 'https://receiver.example.com/hook',
            'secret' => 'whsec_test_secret',
            'events' => ['invoice.created'],
            'active' => true,
        ]);

        return $endpoint->deliveries()->create([
            'event_id' => (string) \Illuminate\Support\Str::uuid(),
            'event_type' => 'invoice.created',
            'payload' => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'invoice.created',
                'api_version' => 'v1',
                'occurred_at' => now()->toISOString(),
                'data' => ['invoice' => ['id' => 15]],
            ],
            'status' => 'pending',
        ]);
    }
}
