<?php

namespace App\Services\Discounts;

use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Services\Discounts\Contracts\CampaignStrategy;
use App\Services\Discounts\Strategies\BuyXPayYStrategy;
use App\Services\Discounts\Strategies\CategoryDifferentProductsStrategy;
use App\Services\Discounts\Strategies\CategoryPercentageStrategy;
use App\Services\Discounts\Strategies\CategoryThresholdFixedStrategy;
use App\Services\Discounts\Strategies\ConditionalCampaignStrategy;
use App\Services\Discounts\Strategies\ProductFixedStrategy;
use App\Services\Discounts\Strategies\ProductPercentageStrategy;
use Illuminate\Contracts\Container\Container;

final class CampaignStrategyFactory
{
    public function __construct(private readonly Container $container) {}

    public function make(Campaign $campaign): CampaignStrategy
    {
        $strategy = match ($campaign->type) {
            CampaignType::ProductPercentage => ProductPercentageStrategy::class,
            CampaignType::ProductFixed => ProductFixedStrategy::class,
            CampaignType::CategoryPercentage => CategoryPercentageStrategy::class,
            CampaignType::BuyXPayY => BuyXPayYStrategy::class,
            CampaignType::CategoryThirdHalf => CategoryDifferentProductsStrategy::class,
            CampaignType::CategoryThresholdFixed => CategoryThresholdFixedStrategy::class,
            CampaignType::Conditional => ConditionalCampaignStrategy::class,
        };

        return $this->container->make($strategy);
    }
}
