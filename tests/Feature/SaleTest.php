<?php

namespace Tests\Feature;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Jobs\DeliverWebhook;
use App\Jobs\SendSaleNotification;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_sale_unit_price_field_is_read_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Product::create([
            'name' => 'Kilitli Fiyat Ürünü',
            'code' => 'LOCKED-PRICE-1',
            'purchase_price' => 300,
            'sale_price' => 450,
            'tax_rate' => 20,
            'stock' => 10,
            'min_stock' => 2,
        ]);

        $html = $this->get('/sales/create')->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $priceInput = $xpath->query('//input[@name="unit_price[]"]')->item(0);

        $this->assertNotNull($priceInput);
        $this->assertTrue($priceInput->hasAttribute('readonly'));
    }

    /**
     * TEST: Fatura kesildiğinde;
     * 1. Fatura oluşmalı,
     * 2. Depodaki ürün stoğu eksilmeli,
     * 3. Şirket kasasına nakit girişi işlenmeli,
     * 4. Arka plan kuyruğuna (Job) bildirim görevi atılmalı!
     */
    public function test_sale_creation_updates_stock_cash_and_dispatches_queue_job(): void
    {
        // 0. Kuyruğu sahteleştir (Test esnasında gerçek log/mail çalışmasın, sadece fırlatıldığını denetleyelim)
        Queue::fake();

        // 1. Arrange: Kullanıcı, Müşteri ve 10 adet stoğu olan Ürün hazırla
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Contact::create([
            'name' => 'Albayrak Ltd. Şti.',
            'type' => 'customer',
            'balance' => 0,
        ]);

        $product = Product::create([
            'name' => 'Kablosuz Klavye',
            'code' => 'KLV-99',
            'purchase_price' => 150,
            'sale_price' => 250,
            'tax_rate' => 20,
            'stock' => 10,
            'min_stock' => 2,
        ]);

        $campaign = Campaign::create([
            'name' => '3 Al 2 Öde',
            'type' => CampaignType::BuyXPayY,
            'parameters' => ['buy_quantity' => 3, 'pay_quantity' => 2],
        ]);
        $campaign->targets()->create([
            'target_type' => CampaignTargetType::Product,
            'target_id' => $product->id,
        ]);

        // 2. Act: 3 adet klavyede "3 al 2 öde" uygulanır (brüt 750, net 500 TL).
        $response = $this->actingAs($user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [3],
            'unit_price' => [250],
        ]);

        // 3. Assert (Kritik Kontroller):
        $response->assertRedirect(route('sales.index'));

        // A. Fatura tablosunda kampanya sonrası 500 TL'lik kayıt var mı?
        $this->assertDatabaseHas('sales', [
            'contact_id' => $customer->id,
            'total_amount' => 500,
        ]);

        // B. Ürün stoğu 10'dan 7'ye düştü mü? (10 - 3 = 7)
        $this->assertEquals(7, $product->fresh()->stock);

        // C. Kasaya indirim sonrası 500 TL nakit girişi işlendi mi?
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => $customer->id,
            'type' => 'in',
            'amount' => 500,
        ]);

        // D. Arka plan kuyruğuna SendSaleNotification görevi gönderildi mi?
        Queue::assertPushed(SendSaleNotification::class);
    }

    public function test_web_sale_creation_queues_invoice_created_webhook(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Contact::create([
            'name' => 'Webhook Müşterisi',
            'type' => 'customer',
        ]);

        $product = Product::create([
            'name' => 'Webhook Ürünü',
            'code' => 'WEB-SALE-WEBHOOK-1',
            'purchase_price' => 100,
            'sale_price' => 150,
            'tax_rate' => 20,
            'stock' => 10,
            'min_stock' => 2,
        ]);

        $endpoint = $user->webhookEndpoints()->create([
            'url' => 'https://receiver.example.com/web-sale',
            'secret' => 'whsec_test_secret',
            'events' => ['invoice.created'],
            'active' => true,
        ]);

        $this->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [2],
            'unit_price' => [150],
        ])->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => 'invoice.created',
            'status' => 'pending',
        ]);

        Queue::assertPushedOn('webhooks', DeliverWebhook::class);
    }

    public function test_web_sale_cancellation_queues_invoice_cancelled_webhook(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Contact::create([
            'name' => 'İptal Webhook Müşterisi',
            'type' => 'customer',
        ]);

        $product = Product::create([
            'name' => 'İptal Webhook Ürünü',
            'code' => 'WEB-SALE-CANCEL-1',
            'purchase_price' => 100,
            'sale_price' => 150,
            'tax_rate' => 20,
            'stock' => 10,
            'min_stock' => 2,
        ]);

        $endpoint = $user->webhookEndpoints()->create([
            'url' => 'https://receiver.example.com/web-sale-cancelled',
            'secret' => 'whsec_test_secret',
            'events' => ['invoice.cancelled'],
            'active' => true,
        ]);

        $this->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [2],
            'unit_price' => [150],
        ])->assertRedirect(route('sales.index'));

        $saleId = $user->sales()->sole()->id;

        $this->delete("/sales/{$saleId}")
            ->assertRedirect(route('sales.index'));

        $this->get('/sales')
            ->assertOk()
            ->assertSee(__('Cancelled'));

        $this->get("/sales/{$saleId}")
            ->assertOk()
            ->assertSee(__('This invoice has been cancelled.'))
            ->assertDontSee(__('Cancel Invoice'));

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => 'invoice.cancelled',
            'status' => 'pending',
        ]);

        Queue::assertPushedOn('webhooks', DeliverWebhook::class);
    }

    public function test_web_sale_uses_product_price_when_no_explicit_price_override_is_requested(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Contact::create([
            'name' => 'Fiyat Güvenliği Müşterisi',
            'type' => 'customer',
        ]);

        $product = Product::create([
            'name' => 'Fiyat Güvenliği Ürünü',
            'code' => 'PRICE-GUARD-1',
            'purchase_price' => 300,
            'sale_price' => 450,
            'tax_rate' => 20,
            'stock' => 10,
            'min_stock' => 2,
        ]);

        // Tarayıcıdaki alan değiştirilerek 450 TL'lik ürün 1 TL gönderilmeye çalışılıyor.
        $this->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [1],
            'unit_price' => [1],
        ])->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 450,
            'tax_rate' => 20,
            'tax_amount' => 75,
            'total' => 450,
        ]);

        $this->assertDatabaseHas('sales', [
            'contact_id' => $customer->id,
            'total_amount' => 450,
        ]);
    }
}
