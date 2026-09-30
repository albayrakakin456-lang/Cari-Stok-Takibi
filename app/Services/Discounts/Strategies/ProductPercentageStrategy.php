<?php

namespace App\Services\Discounts\Strategies;

use App\Models\Campaign;
use App\Services\Discounts\DiscountContext;

final class ProductPercentageStrategy extends CampaignStrategy
{
    public function apply(DiscountContext $context, Campaign $campaign): void
    {
        $targetIds = $this->targetIds($campaign);
        $rate = (float) $campaign->parameters['discount_rate'];

        foreach ($context->items as $index => $item) {
            if (! in_array($item['product_id'], $targetIds, true)) {
                continue;
            }

            $amount = (int) round($context->lineNet($item) * $rate / 100);
            $context->addLineDiscount(
                index: $index,
                amount: $amount,
                code: 'PRODUCT_PERCENTAGE',
                description: "{$item['name']}: %{$rate} ürün indirimi",
                campaign: $campaign,
                metadata: ['discount_rate' => $rate],
            );
        }
    }
}
