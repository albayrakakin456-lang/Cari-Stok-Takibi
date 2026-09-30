<?php

namespace Tests\Unit;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use App\Services\Discounts\DiscountContext;
use App\Services\Discounts\Strategies\BuyXPayYStrategy;
use App\Services\Discounts\Strategies\CategoryDifferentProductsStrategy;
use App\Services\Discounts\Strategies\CategoryThresholdFixedStrategy;
use App\Services\Discounts\Strategies\ConditionalCampaignStrategy;
use App\Services\Discounts\Strategies\ProductPercentageStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicCampaignStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_x_pay_y_calculates_multiple_free_groups(): void
    {
        $campaign = $this->campaign(CampaignType::BuyXPayY, [
            'buy_quantity' => 3,
            'pay_quantity' => 2,
        ], CampaignTargetType::Product, 10);
        $context = $this->context([
            $this->item(productId: 10, categoryId: 1, quantity: 7, price: 10_000),
        ]);

        app(BuyXPayYStrategy::class)->apply($context, $campaign);

        $this->assertSame(20_000, $context->campaignDiscount);
        $this->assertSame(2, $context->discounts[0]['metadata']['free_quantity']);
    }

    public function test_percentage_strategy_only_discount_targets_and_locks_the_line(): void
    {
        $campaign = $this->campaign(
            CampaignType::ProductPercentage,
            ['discount_rate' => 25],
            CampaignTargetType::Product,
            10,
        );
        $context = $this->context([
            $this->item(productId: 10, categoryId: 1, quantity: 2, price: 10_000),
            $this->item(productId: 11, categoryId: 1, quantity: 1, price: 10_000),
        ]);

        app(ProductPercentageStrategy::class)->apply($context, $campaign);

        $this->assertSame(5_000, $context->campaignDiscount);
        $this->assertFalse($context->canApplyLineCampaign(0));
        $this->assertTrue($context->canApplyLineCampaign(1));
    }

    public function test_different_products_strategy_discounts_the_cheapest_product(): void
    {
        $campaign = $this->campaign(CampaignType::CategoryThirdHalf, [
            'different_product_count' => 3,
            'discount_rate' => 50,
        ], CampaignTargetType::Category, 5);
        $context = $this->context([
            $this->item(1, 5, 1, 10_000),
            $this->item(2, 5, 1, 30_000),
            $this->item(3, 5, 1, 20_000),
        ]);

        app(CategoryDifferentProductsStrategy::class)->apply($context, $campaign);

        $this->assertSame(5_000, $context->campaignDiscount);
        $this->assertSame(5_000, $context->items[0]['discount_amount']);
    }

    public function test_category_threshold_uses_line_discounted_category_total(): void
    {
        $campaign = $this->campaign(CampaignType::CategoryThresholdFixed, [
            'minimum_amount' => 2500,
            'discount_amount' => 500,
        ], CampaignTargetType::Category, 5);
        $item = $this->item(1, 5, 1, 300_000);
        $item['discount_amount'] = 60_000;
        $context = $this->context([$item]);

        app(CategoryThresholdFixedStrategy::class)->apply($context, $campaign);
        $this->assertSame(0, $context->campaignDiscount);

        $context->items[0]['discount_amount'] = 40_000;
        app(CategoryThresholdFixedStrategy::class)->apply($context, $campaign);
        $this->assertSame(50_000, $context->campaignDiscount);
    }

    public function test_conditional_campaign_requires_all_rules_and_discounts_matching_lines(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $campaign = Campaign::create([
            'name' => 'Koşullu Test',
            'type' => CampaignType::Conditional,
            'parameters' => [
                'conditions' => [
                    ['source_type' => 'category', 'target_id' => 5, 'minimum_quantity' => 3, 'minimum_amount' => null],
                    ['source_type' => 'product', 'target_id' => 2, 'minimum_quantity' => null, 'minimum_amount' => 200],
                ],
                'reward_type' => 'percentage',
                'reward_value' => 10,
                'reward_scope' => 'matching_items',
                'reward_target_ids' => [],
            ],
            'is_active' => true,
        ]);
        $context = $this->context([
            $this->item(1, 5, 2, 10_000),
            $this->item(2, 5, 1, 20_000),
        ]);

        app(ConditionalCampaignStrategy::class)->apply($context, $campaign);

        $this->assertSame(4_000, $context->campaignDiscount);
        $this->assertFalse($context->canApplyLineCampaign(0));
        $this->assertFalse($context->canApplyLineCampaign(1));
    }

    public function test_conditional_campaign_repeats_fixed_reward_for_each_completed_threshold(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $campaign = Campaign::create([
            'name' => 'Katlanan Koşullu Kampanya',
            'type' => CampaignType::Conditional,
            'parameters' => [
                'conditions' => [[
                    'source_type' => 'product',
                    'target_id' => 10,
                    'minimum_quantity' => 3,
                    'minimum_amount' => null,
                ]],
                'reward_type' => 'fixed',
                'reward_value' => 100,
                'reward_scope' => 'matching_items',
                'repeat_reward' => true,
            ],
            'is_active' => true,
        ]);
        $context = $this->context([
            $this->item(productId: 10, categoryId: 1, quantity: 6, price: 10_000),
        ]);

        app(ConditionalCampaignStrategy::class)->apply($context, $campaign);

        $this->assertSame(20_000, $context->campaignDiscount);
        $this->assertSame(2, $context->discounts[0]['metadata']['repeat_count']);
    }

    private function campaign(
        CampaignType $type,
        array $parameters,
        CampaignTargetType $targetType,
        int $targetId,
    ): Campaign {
        $user = User::factory()->create();
        $this->actingAs($user);
        $campaign = Campaign::create([
            'name' => 'Test Kampanyası',
            'type' => $type,
            'parameters' => $parameters,
            'is_active' => true,
        ]);
        $campaign->targets()->create([
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);

        return $campaign;
    }

    private function context(array $items): DiscountContext
    {
        return new DiscountContext($items, new Contact(['discount_rate' => 0]));
    }

    private function item(int $productId, int $categoryId, int $quantity, int $price): array
    {
        return [
            'product_id' => $productId,
            'name' => "Ürün {$productId}",
            'category_id' => $categoryId,
            'quantity' => $quantity,
            'original_price' => $price,
            'discount_amount' => 0,
            'campaign_applied' => false,
            'applied_campaign_id' => null,
        ];
    }
}
