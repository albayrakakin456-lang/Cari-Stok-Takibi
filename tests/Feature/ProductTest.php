<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST: Ürün eklendiğinde başlangıç stoğu için otomatik stok hareketi oluşuyor mu?
     */
    public function test_authenticated_user_can_create_product_with_stock_movement(): void
    {
        // 1. Arrange: Oturum açacak kullanıcı
        $user = User::factory()->create();

        // 2. Act: 10 adet başlangıç stoğu olan ürün oluştur
        $response = $this->actingAs($user)->post('/products', [
            'name' => 'Logitech Mouse',
            'code' => 'MOU-001',
            'barcode' => '8690000111222',
            'purchase_price' => 200,
            'sale_price' => 350,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 10,
        ]);

        // 3. Assert:
        $response->assertRedirect(route('products.index'));

        // - Ürün tablosuna yazıldı mı?
        $this->assertDatabaseHas('products', [
            'code' => 'MOU-001',
            'stock' => 10,
        ]);

        // - Kuralımız: "Stok asla hareketsiz değişemez!" Stok hareketi tablosuna giriş yazıldı mı?
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'in',
            'quantity' => 10,
            'description' => 'Açılış / Sayım Stoğu Girişi',
        ]);
    }

    /**
     * TEST: Başlangıç stoğu girilmezse validasyon hatası vermeli
     */
    public function test_product_creation_requires_initial_stock(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/products', [
            'name' => 'Klavye',
            'code' => 'KLV-01',
            'purchase_price' => 100,
            'sale_price' => 200,
            'tax_rate' => 20,
            'min_stock' => 5,
        ]);

        $response->assertSessionHasErrors('stock');
    }

    /**
     * TEST: Geçersiz stok kodu (çok kısa veya geçersiz sembollü) reddedilmeli
     */
    public function test_product_creation_validates_code_format_and_length(): void
    {
        $user = User::factory()->create();

        // 1 karakter (min 2 kuralı)
        $res1 = $this->actingAs($user)->post('/products', [
            'name' => 'Ürün 1',
            'code' => 'A',
            'purchase_price' => 100,
            'sale_price' => 200,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 0,
        ]);
        $res1->assertSessionHasErrors('code');

        // Geçersiz karakterler (boşluk ve ünlem)
        $res2 = $this->actingAs($user)->post('/products', [
            'name' => 'Ürün 2',
            'code' => 'KOD 123!',
            'purchase_price' => 100,
            'sale_price' => 200,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 0,
        ]);
        $res2->assertSessionHasErrors('code');
    }

    /**
     * TEST: Geçersiz barkod (8 karakterden kısa) reddedilmeli
     */
    public function test_product_creation_validates_barcode_format_and_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/products', [
            'name' => 'Ürün 3',
            'code' => 'KOD-03',
            'barcode' => '12345', // 8 karakterden kısa
            'purchase_price' => 100,
            'sale_price' => 200,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 0,
        ]);

        $response->assertSessionHasErrors('barcode');
    }
}

