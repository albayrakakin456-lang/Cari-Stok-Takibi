<?php

namespace App\Services\Discounts\Strategies;

use App\Models\Campaign;
use App\Services\Discounts\DiscountContext;

final class ProductFixedStrategy extends CampaignStrategy
{
    public function apply(DiscountContext $context, Campaign $campaign): void
    {
        $targetIds = $this->targetIds($campaign);
        $unitDiscount = $this->moneyToKurus($campaign->parameters['discount_amount']);

        foreach ($context->items as $index => $item) {
            if (! in_array($item['product_id'], $targetIds, true)) {
                continue;
            }

            $context->addLineDiscount(
                index: $index,
                amount: $unitDiscount * $item['quantity'],
                code: 'PRODUCT_FIXED',
                description: "{$item['name']}: ürün başına sabit indirim",
                campaign: $campaign,
                metadata: ['unit_discount' => $unitDiscount],
            );
        }
    }
}
