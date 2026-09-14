<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_download_sales_and_purchase_invoices_as_pdf(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Contact::create([
            'name' => 'PDF Müşterisi',
            'type' => 'customer',
            'balance' => 0,
        ]);
        $supplier = Contact::create([
            'name' => 'PDF Tedarikçisi',
            'type' => 'supplier',
            'balance' => 0,
        ]);
        $product = Product::create([
            'name' => 'PDF Test Ürünü',
            'code' => 'PDF-01',
            'purchase_price' => 75,
            'sale_price' => 100,
            'tax_rate' => 20,
            'min_stock' => 1,
            'stock' => 10,
        ]);

        $sale = Sale::create([
            'contact_id' => $customer->id,
            'invoice_number' => 'FAT-PDF-1',
            'total_amount' => 200,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        $purchase = Purchase::create([
            'contact_id' => $supplier->id,
            'invoice_number' => 'ALIS-PDF-1',
            'total_amount' => 225,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 75,
            'total' => 225,
        ]);

        $saleResponse = $this->get(route('sales.pdf', $sale->id));
        $saleResponse->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('FAT-PDF-1.pdf');
        $this->assertStringStartsWith('%PDF-', $saleResponse->getContent());

        $purchaseResponse = $this->get(route('purchases.pdf', $purchase->id));
        $purchaseResponse->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('ALIS-PDF-1.pdf');
        $this->assertStringStartsWith('%PDF-', $purchaseResponse->getContent());
    }

    public function test_user_cannot_download_another_users_invoices(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($owner);

        $customer = Contact::create([
            'name' => 'Başka Kullanıcının Müşterisi',
            'type' => 'customer',
            'balance' => 0,
        ]);
        $supplier = Contact::create([
            'name' => 'Başka Kullanıcının Tedarikçisi',
            'type' => 'supplier',
            'balance' => 0,
        ]);
        $sale = Sale::create([
            'contact_id' => $customer->id,
            'invoice_number' => 'FAT-GIZLI',
            'total_amount' => 100,
        ]);
        $purchase = Purchase::create([
            'contact_id' => $supplier->id,
            'invoice_number' => 'ALIS-GIZLI',
            'total_amount' => 100,
        ]);

        $this->actingAs($otherUser);

        $this->get(route('sales.pdf', $sale->id))->assertNotFound();
        $this->get(route('purchases.pdf', $purchase->id))->assertNotFound();
    }
}
