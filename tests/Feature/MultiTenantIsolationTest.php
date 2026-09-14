<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\Sale;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Her kullanıcı sadece kendi verilerini görür (Dashboard, Cariler, Ürünler, Faturalar, Kasa)
     */
    public function test_user_can_only_see_their_own_data(): void
    {
        $userA = User::factory()->create(['name' => 'Akın Albayrak', 'email' => 'akin@example.com']);
        $userB = User::factory()->create(['name' => 'Mehmet Demir', 'email' => 'mehmet@example.com']);

        // User A olarak veri oluştur
        $this->actingAs($userA);
        $contactA = Contact::create(['name' => 'Müşteri A', 'type' => 'customer', 'balance' => 0]);
        $productA = Product::create([
            'name' => 'Laptop A',
            'code' => 'URN-01',
            'purchase_price' => 100,
            'sale_price' => 150,
            'tax_rate' => 20,
            'stock' => 10,
            'min_stock' => 2
        ]);
        CashTransaction::create([
            'contact_id' => $contactA->id,
            'type' => 'in',
            'amount' => 500,
            'description' => 'A Tahsilat'
        ]);

        // User A Dashboard'da kendi bakiyesini ve hareketini görür
        $responseA = $this->actingAs($userA)->get('/');
        $responseA->assertStatus(200);
        $responseA->assertSee('500,00');
        $responseA->assertSee('A Tahsilat');

        // User A carilerinde Müşteri A'yı görür
        $this->actingAs($userA)->get('/contacts')
            ->assertStatus(200)
            ->assertSee('Müşteri A');

        // User B sisteme girer
        $responseB = $this->actingAs($userB)->get('/');
        $responseB->assertStatus(200);
        // User A'nın tahsilatı ve bakiyesi User B'nin dashboard'unda GÖRÜNMEMELİDİR!
        $responseB->assertDontSee('A Tahsilat');
        $responseB->assertDontSee('500,00');
        $responseB->assertSee('₺0,00');

        // Cariler listesi izolasyonu: User B User A'nın müşterisini göremez
        $this->actingAs($userB)->get('/contacts')
            ->assertStatus(200)
            ->assertDontSee('Müşteri A');

        // Ürünler listesi izolasyonu: User B User A'nın ürününü göremez
        $this->actingAs($userB)->get('/products')
            ->assertStatus(200)
            ->assertDontSee('Laptop A')
            ->assertDontSee('URN-01');

        // Kasa listesi izolasyonu: User B User A'nın kasasını göremez
        $this->actingAs($userB)->get('/cash')
            ->assertStatus(200)
            ->assertDontSee('A Tahsilat');
    }

    /**
     * TEST 2: Doğrudan URL / ID üzerinden başka bir kullanıcının kaydına erişilemez (404 döner)
     */
    public function test_user_cannot_view_another_users_resource_by_direct_id(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);
        $contactA = Contact::create(['name' => 'Gizli Müşteri A', 'type' => 'customer', 'balance' => 0]);
        $productA = Product::create([
            'name' => 'Gizli Ürün A',
            'code' => 'SECRET-01',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_rate' => 20,
            'stock' => 5,
            'min_stock' => 1
        ]);

        // User B doğrudan ID ile User A'nın carisini görmeye çalışır -> 404
        $this->actingAs($userB)->get("/contacts/{$contactA->id}")
            ->assertStatus(404);

        // User B doğrudan ID ile User A'nın ürününü görmeye çalışır -> 404
        $this->actingAs($userB)->get("/products/{$productA->id}")
            ->assertStatus(404);
    }

    /**
     * TEST 3: Farklı kullanıcılar aynı stok kodunu bağımsızca kullanabilir (Composite Unique)
     */
    public function test_different_users_can_use_same_product_code(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A 'KOD-100' kodlu ürün ekler
        $resA = $this->actingAs($userA)->post('/products', [
            'name' => 'A Ürünü',
            'code' => 'KOD-100',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 20,
            'min_stock' => 2,
            'stock' => 5,
        ]);
        $resA->assertRedirect(route('products.index'));

        // User B de aynı 'KOD-100' kodunu ekleyebilmelidir!
        $resB = $this->actingAs($userB)->post('/products', [
            'name' => 'B Ürünü',
            'code' => 'KOD-100',
            'purchase_price' => 30,
            'sale_price' => 45,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 15,
        ]);
        $resB->assertRedirect(route('products.index'));

        // Ama User B aynı kodu kendi hesabında ikinci kez eklemeye kalkarsa hata almalıdır
        $resB2 = $this->actingAs($userB)->post('/products', [
            'name' => 'B İkinci Ürün',
            'code' => 'KOD-100',
            'purchase_price' => 30,
            'sale_price' => 45,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 15,
        ]);
        $resB2->assertSessionHasErrors('code');
    }

    /**
     * TEST 4: Kullanıcı başka bir kullanıcının müşterisine veya ürününe satış faturası kesemez
     */
    public function test_user_cannot_sell_another_users_product_or_customer(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);
        $contactA = Contact::create(['name' => 'Müşteri A', 'type' => 'customer', 'balance' => 0]);
        $productA = Product::create([
            'name' => 'Ürün A',
            'code' => 'P-A',
            'purchase_price' => 10,
            'sale_price' => 20,
            'tax_rate' => 20,
            'stock' => 5,
            'min_stock' => 1
        ]);

        // User B, User A'nın müşteri ve ürün ID'lerini göndererek fatura kesmeye çalışır
        $response = $this->actingAs($userB)->post('/sales', [
            'contact_id' => $contactA->id,
            'product_id' => [$productA->id],
            'quantity' => [1],
            'unit_price' => [20],
        ]);

        $response->assertSessionHasErrors(['contact_id', 'product_id.0']);
    }
}