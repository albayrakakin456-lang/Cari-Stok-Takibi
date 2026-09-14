<?php

namespace Tests\Feature;

use App\Jobs\SendSaleNotification;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

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

        // 2. Act: Müşteriye 3 adet klavye satışı yap (Toplam: 3 x 250 = 750 TL)
        $response = $this->actingAs($user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [3],
            'unit_price' => [250],
        ]);

        // 3. Assert (Kritik Kontroller):
        $response->assertRedirect(route('sales.index'));

        // A. Fatura tablosunda 750 TL'lik kayıt var mı?
        $this->assertDatabaseHas('sales', [
            'contact_id' => $customer->id,
            'total_amount' => 750,
        ]);

        // B. Ürün stoğu 10'dan 7'ye düştü mü? (10 - 3 = 7)
        $this->assertEquals(7, $product->fresh()->stock);

        // C. Kasaya 750 TL nakit girişi işlendi mi?
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => $customer->id,
            'type' => 'in',
            'amount' => 750,
        ]);

        // D. Arka plan kuyruğuna SendSaleNotification görevi gönderildi mi?
        Queue::assertPushed(SendSaleNotification::class);
    }
}

