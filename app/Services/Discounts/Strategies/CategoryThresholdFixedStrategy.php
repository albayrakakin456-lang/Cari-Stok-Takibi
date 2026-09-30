<?php

namespace App\Services\Discounts\Strategies;

use App\Models\Campaign;
use App\Services\Discounts\DiscountContext;

final class CategoryThresholdFixedStrategy extends CampaignStrategy
{
    public function apply(DiscountContext $context, Campaign $campaign): void
    {
        $minimumAmount = $this->moneyToKurus($campaign->parameters['minimum_amount']);
        $discountAmount = $this->moneyToKurus($campaign->parameters['discount_amount']);

        foreach ($this->targetIds($campaign) as $categoryId) {
            $categoryTotal = array_sum(array_map(
                fn (array $item) => $item['category_id'] === $categoryId
                    ? $context->lineNet($item)
                    : 0,
                $context->items,
            ));

            if ($categoryTotal < $minimumAmount) {
                continue;
            }

            $context->addCartDiscount(
                amount: $discountAmount,
                code: 'CATEGORY_THRESHOLD_FIXED',
                description: "Kategori {$categoryId}: tutar eşiği sabit indirimi",
                campaign: $campaign,
                metadata: [
                    'category_id' => $categoryId,
                    'category_total' => $categoryTotal,
                    'minimum_amount' => $minimumAmount,
                ],
            );
        }
    }
}
