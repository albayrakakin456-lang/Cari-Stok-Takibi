<?php

namespace Tests\Unit;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\Discounts\DiscountEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_rules_run_in_phases_and_a_line_cannot_receive_two_campaigns(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['name' => 'Elektronik']);
        $contact = Contact::create(['name' => 'Müşteri', 'type' => 'customer', 'discount_rate' => 10]);

        $products = collect([
            ['name' => 'Ucuz', 'code' => 'D-1', 'price' => 600, 'quantity' => 3],
            ['name' => 'Orta', 'code' => 'D-2', 'price' => 1000, 'quantity' => 1],
            ['name' => 'Pahalı', 'code' => 'D-3', 'price' => 1200, 'quantity' => 1],
        ])->map(fn (array $row) => [
            'product' => Product::create([
                'category_id' => $category->id,
                'name' => $row['name'],
                'code' => $row['code'],
                'purchase_price' => 1,
                'sale_price' => $row['price'],
                'stock' => 10,
            ]),
            'quantity' => $row['quantity'],
        ]);

        $categoryCampaign = Campaign::create([
            'name' => 'Üç Farklı Ürün',
            'type' => CampaignType::CategoryThirdHalf,
            'parameters' => ['different_product_count' => 3, 'discount_rate' => 50],
            'priority' => 10,
        ]);
        $categoryCampaign->targets()->create([
            'target_type' => CampaignTargetType::Category,
            'target_id' => $category->id,
        ]);
        $thresholdCampaign = Campaign::create([
            'name' => 'Kategori Eşiği',
            'type' => CampaignType::CategoryThresholdFixed,
            'parameters' => ['minimum_amount' => 2500, 'discount_amount' => 500],
            'priority' => 1,
        ]);
        $thresholdCampaign->targets()->create([
            'target_type' => CampaignTargetType::Category,
            'target_id' => $category->id,
        ]);

        $result = app(DiscountEngine::class)->calculate(
            $products->map(fn (array $row) => ['product_id' => $row['product']->id, 'quantity' => $row['quantity']])->all(),
            $contact,
        );

        // Brüt 4.000; ucuz ürüne yalnızca kategori indirimi 300; kategori eşiği sonrası 500; cari %10 = 320.
        $this->assertSame(4000.0, $result['subtotal']);
        $this->assertSame(800.0, $result['campaign_discount']);
        $this->assertSame(320.0, $result['customer_discount']);
        $this->assertSame(2880.0, $result['total_amount']);
        // KDV her fatura satırında kuruşa yuvarlandığı için toplamda 1 kuruş fark oluşabilir.
        $this->assertSame(480.01, array_sum(array_column($result['items'], 'tax_amount')));
        $this->assertSame(['CATEGORY_DIFFERENT_PRODUCTS', 'CATEGORY_THRESHOLD_FIXED', 'CUSTOMER_DISCOUNT'], array_column($result['discounts'], 'code'));
    }

    public function test_buy_three_pay_two_uses_database_price_and_combines_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contact = Contact::create(['name' => 'Müşteri', 'type' => 'customer']);
        $product = Product::create([
            'name' => 'Kalem', 'code' => 'PEN-1', 'purchase_price' => 10,
            'sale_price' => 100, 'stock' => 20,
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

        $result = app(DiscountEngine::class)->calculate([
            ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1],
            ['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 1],
        ], $contact);

        $this->assertCount(1, $result['items']);
        $this->assertSame(600.0, $result['subtotal']);
        $this->assertSame(200.0, $result['campaign_discount']);
        $this->assertSame(400.0, $result['total_amount']);
        $this->assertSame(20, $result['items'][0]['tax_rate']);
        $this->assertSame(66.67, $result['items'][0]['tax_amount']);
    }

    public function test_customer_gets_the_most_advantageous_campaign_when_two_match_same_product(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contact = Contact::create(['name' => 'Müşteri', 'type' => 'customer']);
        $product = Product::create([
            'name' => 'Test Ürünü',
            'code' => 'BEST-1',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_rate' => 20,
            'stock' => 20,
        ]);

        // Önce oluşturulan kampanya yalnız 30 TL indirim verir.
        $percentage = Campaign::create([
            'name' => '%10 İndirim',
            'type' => CampaignType::ProductPercentage,
            'parameters' => ['discount_rate' => 10],
        ]);
        $percentage->targets()->create([
            'target_type' => CampaignTargetType::Product,
            'target_id' => $product->id,
        ]);

        // Sonra oluşturulmasına rağmen 100 TL ile daha avantajlı olduğu için bu seçilmelidir.
        $buyThreePayTwo = Campaign::create([
            'name' => '3 Al 2 Öde',
            'type' => CampaignType::BuyXPayY,
            'parameters' => ['buy_quantity' => 3, 'pay_quantity' => 2],
        ]);
        $buyThreePayTwo->targets()->create([
            'target_type' => CampaignTargetType::Product,
            'target_id' => $product->id,
        ]);

        $result = app(DiscountEngine::class)->calculate([
            ['product_id' => $product->id, 'quantity' => 3],
        ], $contact);

        $this->assertSame(100.0, $result['campaign_discount']);
        $this->assertSame(200.0, $result['total_amount']);
        $this->assertCount(1, $result['discounts']);
        $this->assertSame($buyThreePayTwo->id, $result['discounts'][0]['campaign_id']);
        $this->assertSame('BUY_X_PAY_Y', $result['discounts'][0]['code']);
    }
}
