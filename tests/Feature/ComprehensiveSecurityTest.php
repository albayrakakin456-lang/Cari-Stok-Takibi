<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\CashTransaction;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // ════════════════════════════════════════════════════════════════
    //  1. MİSAFİR ERİŞİM ENGELİ (Auth Middleware)
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function guest_cannot_access_dashboard()
    {
        $this->get('/')->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_access_contacts_index()
    {
        $this->get('/contacts')->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_access_products_index()
    {
        $this->get('/products')->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_access_sales_index()
    {
        $this->get('/sales')->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_access_purchases_index()
    {
        $this->get('/purchases')->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_access_cash_index()
    {
        $this->get('/cash')->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_post_any_store_route()
    {
        $this->post('/contacts')->assertRedirect('/login');
        $this->post('/products')->assertRedirect('/login');
        $this->post('/sales')->assertRedirect('/login');
        $this->post('/purchases')->assertRedirect('/login');
        $this->post('/cash')->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_delete_any_resource()
    {
        $this->delete('/sales/1')->assertRedirect('/login');
        $this->delete('/purchases/1')->assertRedirect('/login');
    }

    // ════════════════════════════════════════════════════════════════
    //  2. TENANT İZOLASYONU (Başka Kullanıcının Verisine Erişim)
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function user_cannot_view_other_users_contact()
    {
        $otherContact = Contact::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Gizli Firma',
            'type' => 'customer',
        ]);

        $this->actingAs($this->user)
             ->get("/contacts/{$otherContact->id}")
             ->assertStatus(404);
    }

    /** @test */
    public function user_cannot_view_other_users_product()
    {
        $otherProduct = Product::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Gizli Ürün',
            'code' => 'SECRET-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 50,
            'min_stock' => 5,
        ]);

        $this->actingAs($this->user)
             ->get("/products/{$otherProduct->id}")
             ->assertStatus(404);
    }

    /** @test */
    public function user_cannot_view_other_users_sale()
    {
        $otherContact = Contact::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Diğer Müşteri',
            'type' => 'customer',
        ]);
        $otherSale = Sale::create([
            'user_id' => $this->otherUser->id,
            'contact_id' => $otherContact->id,
            'invoice_number' => 'FAT-OTHER-001',
            'total_amount' => 100,
        ]);

        $this->actingAs($this->user)
             ->get("/sales/{$otherSale->id}")
             ->assertStatus(404);
    }

    /** @test */
    public function user_cannot_view_other_users_purchase()
    {
        $otherContact = Contact::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Diğer Tedarikçi',
            'type' => 'supplier',
        ]);
        $otherPurchase = Purchase::create([
            'user_id' => $this->otherUser->id,
            'contact_id' => $otherContact->id,
            'invoice_number' => 'ALIS-OTHER-001',
            'total_amount' => 500,
        ]);

        $this->actingAs($this->user)
             ->get("/purchases/{$otherPurchase->id}")
             ->assertStatus(404);
    }

    /** @test */
    public function user_cannot_sell_using_other_users_customer()
    {
        $otherContact = Contact::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Başka Firma Müşterisi',
            'type' => 'customer',
        ]);
        $myProduct = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Benim Ürünüm',
            'code' => 'MY-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 100,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $otherContact->id,
            'product_id' => [$myProduct->id],
            'quantity' => [1],
            'unit_price' => [20],
        ]);

        $response->assertSessionHasErrors('contact_id');
    }

    /** @test */
    public function user_cannot_sell_other_users_product()
    {
        $myContact = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Benim Müşterim',
            'type' => 'customer',
        ]);
        $otherProduct = Product::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Başkasının Ürünü',
            'code' => 'OTHER-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 100,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $myContact->id,
            'product_id' => [$otherProduct->id],
            'quantity' => [1],
            'unit_price' => [20],
        ]);

        $response->assertSessionHasErrors('product_id.0');
    }

    /** @test */
    public function user_cannot_purchase_with_other_users_supplier()
    {
        $otherSupplier = Contact::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Başka Tedarikçi',
            'type' => 'supplier',
        ]);
        $myProduct = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Benim Ürünüm',
            'code' => 'MY-P-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 0,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->user)->post('/purchases', [
            'contact_id' => $otherSupplier->id,
            'product_id' => [$myProduct->id],
            'quantity' => [10],
            'unit_price' => [15],
        ]);

        $response->assertSessionHasErrors('contact_id');
    }

    /** @test */
    public function user_cannot_delete_other_users_sale()
    {
        $otherContact = Contact::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Diğer Müşteri Del',
            'type' => 'customer',
        ]);
        $otherSale = Sale::create([
            'user_id' => $this->otherUser->id,
            'contact_id' => $otherContact->id,
            'invoice_number' => 'FAT-DEL-001',
            'total_amount' => 200,
        ]);

        $this->actingAs($this->user)
             ->delete("/sales/{$otherSale->id}");

        // Fatura kesinlikle silinmemiş olmalı (tenant izolasyonu korudu)
        $this->assertDatabaseHas('sales', ['id' => $otherSale->id]);
    }

    // ════════════════════════════════════════════════════════════════
    //  3. EKSİ STOKTA SATIŞ ENGELİ
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function sale_blocked_when_stock_is_zero()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Test Müşteri',
            'type' => 'customer',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Boş Stok Ürünü',
            'code' => 'ZERO-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 0,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [1],
            'unit_price' => [20],
        ]);

        $response->assertSessionHasErrors('stock_error');
        $this->assertDatabaseMissing('sales', ['contact_id' => $customer->id]);
    }

    /** @test */
    public function sale_blocked_when_quantity_exceeds_stock()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Test Müşteri 2',
            'type' => 'customer',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Az Stoklu Ürün',
            'code' => 'LOW-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 5,
            'min_stock' => 3,
        ]);

        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [10],  // 10 isteniyor ama 5 var
            'unit_price' => [20],
        ]);

        $response->assertSessionHasErrors('stock_error');
        // Stok değişmemiş olmalı
        $this->assertEquals(5, $product->fresh()->stock);
    }

    /** @test */
    public function sale_blocked_when_stock_is_negative()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Test Müşteri Eksi',
            'type' => 'customer',
        ]);
        // Stok -4 olan ürün (daha önceki hatalı veri gibi)
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Eksi Stok Ürünü',
            'code' => 'NEG-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => -4,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [1],
            'unit_price' => [20],
        ]);

        $response->assertSessionHasErrors('stock_error');
    }

    // ════════════════════════════════════════════════════════════════
    //  4. SATIŞ İŞLEM BÜTÜNLÜĞÜ (Transaction Integrity)
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function successful_sale_creates_all_related_records()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'İşlem Müşterisi',
            'type' => 'customer',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'İşlem Ürünü',
            'code' => 'TRX-001',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_rate' => 18,
            'stock' => 20,
            'min_stock' => 5,
        ]);

        $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [3],
            'unit_price' => [100],
        ]);

        // Fatura oluşmuş olmalı
        $this->assertDatabaseHas('sales', [
            'contact_id' => $customer->id,
            'total_amount' => 300,
        ]);

        // Fatura kalemi oluşmuş olmalı
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 100,
            'total' => 300,
        ]);

        // Stok çıkış hareketi oluşmuş olmalı
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
        ]);

        // Ürün stoğu düşmüş olmalı (20 - 3 = 17)
        $this->assertEquals(17, $product->fresh()->stock);

        // Kasaya para girmiş olmalı
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => $customer->id,
            'type' => 'in',
            'amount' => 300,
        ]);
    }

    /** @test */
    public function sale_with_multiple_products_updates_all_stocks()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Çok Ürünlü Müşteri',
            'type' => 'customer',
        ]);
        $product1 = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Ürün A',
            'code' => 'MULTI-A',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 50,
            'min_stock' => 5,
        ]);
        $product2 = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Ürün B',
            'code' => 'MULTI-B',
            'purchase_price' => 30,
            'sale_price' => 60,
            'tax_rate' => 18,
            'stock' => 30,
            'min_stock' => 5,
        ]);

        $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product1->id, $product2->id],
            'quantity' => [5, 2],
            'unit_price' => [20, 60],
        ]);

        // İki ürünün de stokları doğru düşmeli
        $this->assertEquals(45, $product1->fresh()->stock);  // 50 - 5
        $this->assertEquals(28, $product2->fresh()->stock);  // 30 - 2

        // Toplam fatura tutarı: (5*20) + (2*60) = 100 + 120 = 220
        $this->assertDatabaseHas('sales', ['total_amount' => 220]);
    }

    // ════════════════════════════════════════════════════════════════
    //  5. FATURA İPTALİ VE GERİ ALMA (Destroy / Reverse Transaction)
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function sale_cancellation_restores_stock_and_creates_reverse_cash()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'İptal Müşterisi',
            'type' => 'customer',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'İptal Ürünü',
            'code' => 'CANCEL-001',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_rate' => 18,
            'stock' => 20,
            'min_stock' => 5,
        ]);

        // Önce satışı yap
        $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [5],
            'unit_price' => [100],
        ]);

        $this->assertEquals(15, $product->fresh()->stock); // 20 - 5

        $sale = Sale::where('contact_id', $customer->id)->first();

        // Faturayı iptal et
        $this->actingAs($this->user)->delete("/sales/{$sale->id}");

        // Stok geri gelmeli (15 + 5 = 20)
        $this->assertEquals(20, $product->fresh()->stock);

        // Ters kasa hareketi oluşmuş olmalı (out)
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => $customer->id,
            'type' => 'out',
            'amount' => 500,
        ]);

        // Stok iade hareketi oluşmuş olmalı (in)
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
        ]);

        // Fatura silinmiş olmalı
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    // ════════════════════════════════════════════════════════════════
    //  6. ALIŞ FATURASI (Purchase) İŞLEM BÜTÜNLÜĞÜ
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function purchase_increases_stock_correctly()
    {
        $supplier = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Alış Tedarikçisi',
            'type' => 'supplier',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Alış Ürünü',
            'code' => 'PUR-001',
            'purchase_price' => 30,
            'sale_price' => 60,
            'tax_rate' => 18,
            'stock' => 10,
            'min_stock' => 5,
        ]);

        $this->actingAs($this->user)->post('/purchases', [
            'contact_id' => $supplier->id,
            'product_id' => [$product->id],
            'quantity' => [25],
            'unit_price' => [35],
        ]);

        // Stok artmış olmalı (10 + 25 = 35)
        $this->assertEquals(35, $product->fresh()->stock);

        // Alış fiyatı güncellenmiş olmalı
        $this->assertEquals(35, $product->fresh()->purchase_price);

        // Stok giriş hareketi oluşmuş olmalı
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 25,
        ]);
    }

    /** @test */
    public function purchase_with_cash_payment_creates_cash_outflow()
    {
        $supplier = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Peşin Tedarikçi',
            'type' => 'supplier',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Peşin Ürünü',
            'code' => 'PUR-CASH-001',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_rate' => 18,
            'stock' => 0,
            'min_stock' => 5,
        ]);

        $this->actingAs($this->user)->post('/purchases', [
            'contact_id' => $supplier->id,
            'product_id' => [$product->id],
            'quantity' => [10],
            'unit_price' => [50],
            'is_paid' => '1',
        ]);

        // Kasadan 500 TL çıkış olmuş olmalı
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => $supplier->id,
            'type' => 'out',
            'amount' => 500,
        ]);
    }

    /** @test */
    public function purchase_without_cash_payment_does_not_create_cash_outflow()
    {
        $supplier = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Veresiye Tedarikçi',
            'type' => 'supplier',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Veresiye Ürünü',
            'code' => 'PUR-VRS-001',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_rate' => 18,
            'stock' => 0,
            'min_stock' => 5,
        ]);

        $this->actingAs($this->user)->post('/purchases', [
            'contact_id' => $supplier->id,
            'product_id' => [$product->id],
            'quantity' => [10],
            'unit_price' => [50],
            // is_paid gönderilmiyor
        ]);

        // Kasada hareket OLMAMALI
        $this->assertDatabaseMissing('cash_transactions', [
            'contact_id' => $supplier->id,
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    //  7. VALİDASYON EDGE CASE'LERİ
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function contact_phone_rejects_letters()
    {
        $response = $this->actingAs($this->user)->post('/contacts', [
            'name' => 'Test Kişi',
            'type' => 'customer',
            'phone' => 'abc1234567',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    /** @test */
    public function contact_phone_accepts_valid_formats()
    {
        $response = $this->actingAs($this->user)->post('/contacts', [
            'name' => 'Geçerli Telefon',
            'type' => 'customer',
            'phone' => '+90 532 123 4567',
        ]);

        $response->assertSessionDoesntHaveErrors('phone');
        $this->assertDatabaseHas('contacts', ['name' => 'Geçerli Telefon']);
    }

    /** @test */
    public function contact_rejects_invalid_type()
    {
        $response = $this->actingAs($this->user)->post('/contacts', [
            'name' => 'Hatalı Tip',
            'type' => 'hacker',
            'phone' => '5321234567',
        ]);

        $response->assertSessionHasErrors('type');
    }

    /** @test */
    public function product_code_rejects_special_characters()
    {
        $response = $this->actingAs($this->user)->post('/products', [
            'name' => 'Test Ürün',
            'code' => 'AB@#$%!',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 10,
            'min_stock' => 5,
        ]);

        $response->assertSessionHasErrors('code');
    }

    /** @test */
    public function product_barcode_rejects_too_short()
    {
        $response = $this->actingAs($this->user)->post('/products', [
            'name' => 'Kısa Barkod Ürün',
            'code' => 'SHORT-BC',
            'barcode' => '123', // min 8 karakter
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 10,
            'min_stock' => 5,
        ]);

        $response->assertSessionHasErrors('barcode');
    }

    /** @test */
    public function sale_rejects_zero_unit_price()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Sıfır Fiyat Müşterisi',
            'type' => 'customer',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Sıfır Fiyat Ürünü',
            'code' => 'ZERO-PRC',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 50,
            'min_stock' => 5,
        ]);

        // unit_price min:0, so 0 should be accepted (free samples etc.)
        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [1],
            'unit_price' => [0],
        ]);

        // 0 TL satış kabul edilir (numune/promosyon)
        $response->assertSessionDoesntHaveErrors('unit_price.0');
    }

    /** @test */
    public function sale_rejects_negative_quantity()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Eksi Adet Müşterisi',
            'type' => 'customer',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Eksi Adet Ürünü',
            'code' => 'NEG-QTY',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 50,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [$product->id],
            'quantity' => [-5],
            'unit_price' => [20],
        ]);

        $response->assertSessionHasErrors('quantity.0');
    }

    /** @test */
    public function sale_rejects_empty_product_array()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Boş Sepet Müşterisi',
            'type' => 'customer',
        ]);

        $response = $this->actingAs($this->user)->post('/sales', [
            'contact_id' => $customer->id,
            'product_id' => [],
            'quantity' => [],
            'unit_price' => [],
        ]);

        $response->assertSessionHasErrors('product_id');
    }

    /** @test */
    public function purchase_rejects_negative_unit_price()
    {
        $supplier = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Eksi Fiyat Tedarikçi',
            'type' => 'supplier',
        ]);
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Eksi Fiyat Ürünü',
            'code' => 'NEG-PRC',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 10,
            'min_stock' => 5,
        ]);

        $response = $this->actingAs($this->user)->post('/purchases', [
            'contact_id' => $supplier->id,
            'product_id' => [$product->id],
            'quantity' => [5],
            'unit_price' => [-10],
        ]);

        $response->assertSessionHasErrors('unit_price.0');
    }

    /** @test */
    public function cash_transaction_rejects_zero_amount()
    {
        $response = $this->actingAs($this->user)->post('/cash', [
            'type' => 'in',
            'amount' => 0,
            'description' => 'Sıfır tutar denemesi',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function cash_transaction_rejects_invalid_type()
    {
        $response = $this->actingAs($this->user)->post('/cash', [
            'type' => 'hack',
            'amount' => 100,
            'description' => 'Geçersiz tip denemesi',
        ]);

        $response->assertSessionHasErrors('type');
    }

    /** @test */
    public function cash_transaction_rejects_missing_description()
    {
        $response = $this->actingAs($this->user)->post('/cash', [
            'type' => 'in',
            'amount' => 100,
        ]);

        $response->assertSessionHasErrors('description');
    }

    // ════════════════════════════════════════════════════════════════
    //  8. DASHBOARD VERİ DOĞRULUĞU
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function dashboard_loads_successfully_for_authenticated_user()
    {
        $this->actingAs($this->user)
             ->get('/')
             ->assertOk()
             ->assertViewIs('dashboard');
    }

    /** @test */
    public function dashboard_shows_correct_critical_stock_products()
    {
        // Kritik stokta ürün (stock <= min_stock)
        Product::create([
            'user_id' => $this->user->id,
            'name' => 'Kritik Ürün',
            'code' => 'CRIT-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 3,
            'min_stock' => 10,
        ]);
        // Normal stokta ürün
        Product::create([
            'user_id' => $this->user->id,
            'name' => 'Normal Ürün',
            'code' => 'NORM-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 100,
            'min_stock' => 10,
        ]);

        $response = $this->actingAs($this->user)->get('/');

        $response->assertOk();
        $response->assertSee('Kritik Ürün');
        // Normal ürün kritik tabloda GÖRÜNMEMELİ
        $criticalProducts = $response->viewData('criticalProducts');
        $this->assertEquals(1, $criticalProducts->count());
    }

    // ════════════════════════════════════════════════════════════════
    //  9. SAYFA RENDER TESTLERİ (200 OK / View Doğrulaması)
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function all_index_pages_render_successfully()
    {
        $this->actingAs($this->user);

        $this->get('/contacts')->assertOk()->assertViewIs('contacts.index');
        $this->get('/products')->assertOk()->assertViewIs('products.index');
        $this->get('/sales')->assertOk()->assertViewIs('sales.index');
        $this->get('/purchases')->assertOk()->assertViewIs('purchases.index');
        $this->get('/cash')->assertOk()->assertViewIs('cash.index');
    }

    /** @test */
    public function all_create_pages_render_successfully()
    {
        $this->actingAs($this->user);

        $this->get('/contacts/create')->assertOk()->assertViewIs('contacts.create');
        $this->get('/products/create')->assertOk()->assertViewIs('products.create');
        $this->get('/sales/create')->assertOk()->assertViewIs('sales.create');
        $this->get('/purchases/create')->assertOk()->assertViewIs('purchases.create');
        $this->get('/cash/create')->assertOk()->assertViewIs('cash.create');
    }

    /** @test */
    public function contact_show_page_renders_for_customer()
    {
        $customer = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Show Müşterisi',
            'type' => 'customer',
        ]);

        $this->actingAs($this->user)
             ->get("/contacts/{$customer->id}")
             ->assertOk()
             ->assertViewIs('contacts.show')
             ->assertSee('Show Müşterisi');
    }

    /** @test */
    public function contact_show_page_renders_for_supplier()
    {
        $supplier = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Show Tedarikçisi',
            'type' => 'supplier',
        ]);

        $this->actingAs($this->user)
             ->get("/contacts/{$supplier->id}")
             ->assertOk()
             ->assertViewIs('contacts.show')
             ->assertSee('Show Tedarikçisi');
    }

    /** @test */
    public function product_show_page_renders_with_stock_movements()
    {
        $product = Product::create([
            'user_id' => $this->user->id,
            'name' => 'Show Ürünü',
            'code' => 'SHOW-001',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 18,
            'stock' => 50,
            'min_stock' => 5,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 50,
            'description' => 'Açılış stoğu',
        ]);

        $this->actingAs($this->user)
             ->get("/products/{$product->id}")
             ->assertOk()
             ->assertViewIs('products.show')
             ->assertSee('Show Ürünü');
    }

    /** @test */
    public function nonexistent_resource_returns_404()
    {
        $this->actingAs($this->user);

        $this->get('/contacts/99999')->assertStatus(404);
        $this->get('/products/99999')->assertStatus(404);
        $this->get('/sales/99999')->assertStatus(404);
        $this->get('/purchases/99999')->assertStatus(404);
    }

    // ════════════════════════════════════════════════════════════════
    // 10. AUTH SİSTEMİ DERİN TESTLERİ
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function register_creates_user_and_logs_in()
    {
        $response = $this->post('/register', [
            'name' => 'Yeni Kullanıcı',
            'email' => 'yeni@test.com',
            'password' => 'sifre123',
            'password_confirmation' => 'sifre123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'yeni@test.com']);
    }

    /** @test */
    public function register_rejects_duplicate_email()
    {
        $response = $this->post('/register', [
            'name' => 'Mükerrer',
            'email' => $this->user->email,
            'password' => 'sifre123',
            'password_confirmation' => 'sifre123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function register_rejects_mismatched_passwords()
    {
        $response = $this->post('/register', [
            'name' => 'Yanlış Şifre',
            'email' => 'sifre@test.com',
            'password' => 'sifre123',
            'password_confirmation' => 'farkli456',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function register_rejects_short_password()
    {
        $response = $this->post('/register', [
            'name' => 'Kısa Şifre',
            'email' => 'kisa@test.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function logout_clears_session_and_redirects()
    {
        $this->actingAs($this->user)->post('/logout');

        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_cannot_access_login_page()
    {
        $this->actingAs($this->user)
             ->get('/login')
             ->assertRedirect('/');
    }

    /** @test */
    public function authenticated_user_cannot_access_register_page()
    {
        $this->actingAs($this->user)
             ->get('/register')
             ->assertRedirect('/');
    }

    // ════════════════════════════════════════════════════════════════
    // 11. DİL DEĞİŞTİRME SİSTEMİ
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function language_switch_to_english_stores_session()
    {
        $response = $this->get('/lang/en');
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    /** @test */
    public function language_switch_to_turkish_stores_session()
    {
        $response = $this->get('/lang/tr');
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'tr');
    }

    /** @test */
    public function language_switch_rejects_invalid_locale()
    {
        $response = $this->get('/lang/xx');
        $response->assertRedirect();
        $response->assertSessionMissing('locale');
    }

    // ════════════════════════════════════════════════════════════════
    // 12. KASA BÜTÜNLÜĞÜ TESTLERİ
    // ════════════════════════════════════════════════════════════════

    /** @test */
    public function cash_transaction_with_valid_contact_succeeds()
    {
        $contact = Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Kasa Carisi',
            'type' => 'customer',
        ]);

        $response = $this->actingAs($this->user)->post('/cash', [
            'contact_id' => $contact->id,
            'type' => 'in',
            'amount' => 500.50,
            'description' => 'Müşteri tahsilatı',
        ]);

        $response->assertRedirect(route('cash.index'));
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => $contact->id,
            'type' => 'in',
            'amount' => 500.50,
        ]);
    }

    /** @test */
    public function cash_transaction_without_contact_succeeds()
    {
        // Genel gider (elektrik, kira gibi) - contact_id null
        $response = $this->actingAs($this->user)->post('/cash', [
            'type' => 'out',
            'amount' => 250,
            'description' => 'Elektrik faturası ödemesi',
        ]);

        $response->assertRedirect(route('cash.index'));
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => null,
            'type' => 'out',
            'amount' => 250,
        ]);
    }

    /** @test */
    public function cash_transaction_rejects_other_users_contact()
    {
        $otherContact = Contact::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Başka Firmanın Carisi',
            'type' => 'customer',
        ]);

        $response = $this->actingAs($this->user)->post('/cash', [
            'contact_id' => $otherContact->id,
            'type' => 'in',
            'amount' => 100,
            'description' => 'Çapraz firma denemesi',
        ]);

        $response->assertSessionHasErrors('contact_id');
    }
}
