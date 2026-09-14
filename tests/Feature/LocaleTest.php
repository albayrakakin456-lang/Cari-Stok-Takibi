<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Dil İngilizceye çevrildiğinde oturum güncelleniyor ve sayfada İngilizce terimler görünüyor mu?
     */
    public function test_can_switch_locale_to_english_and_render_translated_view(): void
    {
        $user = User::create([
            'name' => 'Akın Albayrak',
            'email' => 'akin@example.com',
            'password' => Hash::make('password'),
        ]);

        // 1. Dil rotasına GET isteği atarak dili 'en' yap
        $response = $this->get('/lang/en');
        $response->assertSessionHas('locale', 'en');

        // 2. İngilizce oturumla Dashboard'a git
        $dashboardResponse = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/');

        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Executive Dashboard');
        $dashboardResponse->assertSee('Contacts');
        $dashboardResponse->assertSee('Products & Stock');
    }

    /**
     * TEST 2: Geçersiz bir dil kodu (örn. 'fr', 'de') gelirse oturum değiştirilmez
     */
    public function test_invalid_locale_is_ignored(): void
    {
        $response = $this->get('/lang/fr');

        $response->assertSessionMissing('locale');
    }

    /**
     * TEST 3: Ürün ekleme formu İngilizce ve Türkçe dilinde doğru çevriliyor mu?
     */
    public function test_product_create_page_localization(): void
    {
        $user = User::create([
            'name' => 'Akın Albayrak',
            'email' => 'akin2@example.com',
            'password' => Hash::make('password'),
        ]);

        // İngilizce kontrolü
        $enResponse = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/products/create');

        $enResponse->assertStatus(200);
        $enResponse->assertSee('Add New Product');
        $enResponse->assertSee('Product Name');
        $enResponse->assertSee('Stock Code');
        $enResponse->assertSee('Purchase Price');
        $enResponse->assertSee('Sale Price');
        $enResponse->assertSee('Critical Stock Level');
        $enResponse->assertSee('Save Product');

        // Türkçe kontrolü
        $trResponse = $this->actingAs($user)
            ->withSession(['locale' => 'tr'])
            ->get('/products/create');

        $trResponse->assertStatus(200);
        $trResponse->assertSee('Yeni Ürün Ekle');
        $trResponse->assertSee('Ürün Adı');
        $trResponse->assertSee('Stok Kodu');
        $trResponse->assertSee('Alış Fiyatı');
        $trResponse->assertSee('Satış Fiyatı');
        $trResponse->assertSee('Kritik Stok Seviyesi');
        $trResponse->assertSee('Ürünü Kaydet');
    }

    /**
     * TEST 4: Tüm Ekleme ve Detay ekranları İngilizce dilinde hatasız açılıyor ve çeviriler yerinde mi?
     */
    public function test_all_create_and_show_pages_localization(): void
    {
        $user = User::create([
            'name' => 'Akın Albayrak',
            'email' => 'akin3@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $contact = \App\Models\Contact::create([
            'name' => 'Test Cari A.Ş.',
            'type' => 'customer',
            'phone' => '05551112233',
        ]);

        $product = \App\Models\Product::create([
            'name' => 'Test Ürün',
            'code' => 'TEST-001',
            'purchase_price' => 100,
            'sale_price' => 150,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 20,
        ]);

        $sale = \App\Models\Sale::create([
            'contact_id' => $contact->id,
            'invoice_number' => 'FAT-TEST-99',
            'total_amount' => 150,
        ]);

        $purchase = \App\Models\Purchase::create([
            'contact_id' => $contact->id,
            'invoice_number' => 'ALIS-TEST-99',
            'total_amount' => 100,
        ]);

        // 1. Contacts Create & Show
        $this->actingAs($user)->withSession(['locale' => 'en'])->get('/contacts/create')
            ->assertStatus(200)
            ->assertSee('Add New Contact')
            ->assertSee('Contact Name / Title')
            ->assertSee('Save Contact');

        $this->actingAs($user)->withSession(['locale' => 'en'])->get("/contacts/{$contact->id}")
            ->assertStatus(200)
            ->assertSee('Contact Card & Statement')
            ->assertSee('Customer (Sales)');

        // 2. Cash Create
        $this->actingAs($user)->withSession(['locale' => 'en'])->get('/cash/create')
            ->assertStatus(200)
            ->assertSee('New Cash Movement')
            ->assertSee('Expense / Outflow (Cash Out)')
            ->assertSee('Save Cash Movement');

        // 3. Sales Create & Show
        $this->actingAs($user)->withSession(['locale' => 'en'])->get('/sales/create')
            ->assertStatus(200)
            ->assertSee('New Sale (Create Invoice)')
            ->assertSee('Customer Information')
            ->assertSee('Complete and Save Sale');

        $this->actingAs($user)->withSession(['locale' => 'en'])->get("/sales/{$sale->id}")
            ->assertStatus(200)
            ->assertSee('Sales Invoice')
            ->assertSee('Cancel Invoice');

        // 4. Purchases Create & Show
        $this->actingAs($user)->withSession(['locale' => 'en'])->get('/purchases/create')
            ->assertStatus(200)
            ->assertSee('New Purchase (Goods Receipt)')
            ->assertSee('Supplier Information')
            ->assertSee('Save Purchase & Receive to Stock');

        $this->actingAs($user)->withSession(['locale' => 'en'])->get("/purchases/{$purchase->id}")
            ->assertStatus(200)
            ->assertSee('Purchase Invoice (Goods Receipt)')
            ->assertSee('Cancel Invoice');

        // 5. Products Show
        $this->actingAs($user)->withSession(['locale' => 'en'])->get("/products/{$product->id}")
            ->assertStatus(200)
            ->assertSee('Product Card & Stock Movements')
            ->assertSee('Pricing & Warehouse Valuation');
    }
}

