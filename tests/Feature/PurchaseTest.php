<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST: Gecerli alis faturasi kaydedildiginde stok artmali ve hareket yazilmali
     */
    public function test_authenticated_user_can_create_purchase(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Contact::create([
            'name' => 'Toptanci A.S.',
            'type' => 'supplier',
            'balance' => 0,
        ]);

        $product = Product::create([
            'name' => 'Monitor 24 inc',
            'code' => 'MON-24',
            'purchase_price' => 2000,
            'sale_price' => 3000,
            'tax_rate' => 20,
            'min_stock' => 5,
            'stock' => 10,
        ]);

        $response = $this->post('/purchases', [
            'contact_id' => $supplier->id,
            'product_id' => [$product->id],
            'quantity' => [5],
            'unit_price' => [2100],
            'is_paid' => '1',
        ]);

        $response->assertRedirect(route('purchases.index'));

        // Stok 10 + 5 = 15 olmali
        $this->assertEquals(15, $product->fresh()->stock);

        // Alis faturasi kaydedilmis olmali
        $this->assertDatabaseHas('purchases', [
            'contact_id' => $supplier->id,
            'total_amount' => 10500,
        ]);

        // Kasadan pesin cikis islenmis olmali
        $this->assertDatabaseHas('cash_transactions', [
            'contact_id' => $supplier->id,
            'type' => 'out',
            'amount' => 10500,
        ]);
    }

    /**
     * TEST: Alis faturasinda bos veya gecersiz satir gonderilirse validasyon hatasi vermeli
     */
    public function test_purchase_fails_validation_with_empty_or_invalid_rows(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Contact::create([
            'name' => 'Toptanci B',
            'type' => 'supplier',
        ]);

        // Miktar ve birim fiyat bos veya gecersiz
        $response = $this->post('/purchases', [
            'contact_id' => $supplier->id,
            'product_id' => [''],
            'quantity' => [''],
            'unit_price' => [''],
        ]);

        $response->assertSessionHasErrors(['product_id.0', 'quantity.0', 'unit_price.0']);
    }

    /**
     * TEST: Miktar 0 veya negatif girilirse validasyon hatasi vermeli
     */
    public function test_purchase_fails_with_zero_or_negative_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Contact::create([
            'name' => 'Toptanci C',
            'type' => 'supplier',
        ]);

        $product = Product::create([
            'name' => 'Kablo',
            'code' => 'KBL-01',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_rate' => 20,
            'min_stock' => 1,
            'stock' => 10,
        ]);

        $response = $this->post('/purchases', [
            'contact_id' => $supplier->id,
            'product_id' => [$product->id],
            'quantity' => [0], // 0 girilemez
            'unit_price' => [50],
        ]);

        $response->assertSessionHasErrors('quantity.0');
    }
}