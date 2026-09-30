<?php

namespace Tests\Feature;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaleDiscountPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_persists_line_cart_and_customer_discount_snapshots(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['name' => 'Elektronik']);
        $contact = Contact::create([
            'name' => 'İskontolu Müşteri',
            'type' => 'customer',
            'discount_rate' => 10,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Klavye',
            'code' => 'DISC-SAVE-1',
            'purchase_price' => 100,
            'sale_price' => 1000,
            'stock' => 10,
        ]);

        $lineCampaign = $this->campaign(
            CampaignType::ProductPercentage,
            ['discount_rate' => 10],
            CampaignTargetType::Product,
            $product->id,
            10,
        );
        $cartCampaign = $this->campaign(
            CampaignType::CategoryThresholdFixed,
            ['minimum_amount' => 2500, 'discount_amount' => 500],
            CampaignTargetType::Category,
            $category->id,
            20,
        );

        $this->post(route('sales.store'), [
            'contact_id' => $contact->id,
            'product_id' => [$product->id],
            'quantity' => [3],
            'unit_price' => [1],
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::with(['items', 'discounts'])->sole();

        // 3.000 - 300 satır indirimi - 500 sepet indirimi - 220 cari iskontosu = 1.980 TL.
        $this->assertSame('3000.00', $sale->subtotal);
        $this->assertSame('800.00', $sale->campaign_discount);
        $this->assertSame('220.00', $sale->customer_discount);
        $this->assertSame('1980.00', $sale->total_amount);
        $this->assertCount(3, $sale->discounts);
        $this->assertSame(20, $sale->items->sole()->tax_rate);
        // 1.980 TL nihai toplamın içindeki %20 KDV payı 330 TL'dir.
        $this->assertSame('330.00', $sale->items->sole()->tax_amount);

        $lineDiscount = $sale->discounts->firstWhere('campaign_id', $lineCampaign->id);
        $cartDiscount = $sale->discounts->firstWhere('campaign_id', $cartCampaign->id);
        $customerDiscount = $sale->discounts->firstWhere('campaign_type', 'customer_discount');

        $this->assertSame($sale->items->sole()->id, $lineDiscount->sale_item_id);
        $this->assertNull($cartDiscount->sale_item_id);
        $this->assertNull($customerDiscount->sale_item_id);
        $this->assertSame(10, $lineDiscount->metadata['discount_rate']);

        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('Uygulanan İndirimler')
            ->assertSee($lineCampaign->name)
            ->assertSee($cartCampaign->name)
            ->assertSee('Cari özel iskontosu')
            ->assertSee('KDV Toplamı (Dahil)')
            ->assertSee('%20');
    }

    private function campaign(
        CampaignType $type,
        array $parameters,
        CampaignTargetType $targetType,
        int $targetId,
        int $priority,
    ): Campaign {
        $campaign = Campaign::create([
            'name' => $type->label(),
            'type' => $type,
            'parameters' => $parameters,
            'priority' => $priority,
        ]);
        $campaign->targets()->create([
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);

        return $campaign;
    }
}
