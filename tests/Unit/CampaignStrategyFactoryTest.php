<?php

namespace Tests\Unit;

use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Services\Discounts\CampaignStrategyFactory;
use App\Services\Discounts\Strategies\BuyXPayYStrategy;
use App\Services\Discounts\Strategies\CategoryDifferentProductsStrategy;
use App\Services\Discounts\Strategies\CategoryPercentageStrategy;
use App\Services\Discounts\Strategies\CategoryThresholdFixedStrategy;
use App\Services\Discounts\Strategies\ConditionalCampaignStrategy;
use App\Services\Discounts\Strategies\ProductFixedStrategy;
use App\Services\Discounts\Strategies\ProductPercentageStrategy;
use Tests\TestCase;

class CampaignStrategyFactoryTest extends TestCase
{
    public function test_it_resolves_each_campaign_type_to_its_strategy(): void
    {
        $expected = [
            CampaignType::ProductPercentage->value => ProductPercentageStrategy::class,
            CampaignType::ProductFixed->value => ProductFixedStrategy::class,
            CampaignType::CategoryPercentage->value => CategoryPercentageStrategy::class,
            CampaignType::BuyXPayY->value => BuyXPayYStrategy::class,
            CampaignType::CategoryThirdHalf->value => CategoryDifferentProductsStrategy::class,
            CampaignType::CategoryThresholdFixed->value => CategoryThresholdFixedStrategy::class,
            CampaignType::Conditional->value => ConditionalCampaignStrategy::class,
        ];

        foreach (CampaignType::cases() as $type) {
            $campaign = new Campaign(['type' => $type]);

            $this->assertInstanceOf(
                $expected[$type->value],
                app(CampaignStrategyFactory::class)->make($campaign),
            );
        }
    }
}
