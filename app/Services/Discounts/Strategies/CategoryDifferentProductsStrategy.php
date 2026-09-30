<?php

namespace App\Services\Discounts\Strategies;

use App\Models\Campaign;
use App\Services\Discounts\DiscountContext;

final class CategoryDifferentProductsStrategy extends CampaignStrategy
{
    public function apply(DiscountContext $context, Campaign $campaign): void
    {
        $requiredCount = (int) $campaign->parameters['different_product_count'];
        $rate = (float) $campaign->parameters['discount_rate'];

        foreach ($this->targetIds($campaign) as $categoryId) {
            $indexes = array_keys(array_filter(
                $context->items,
                fn (array $item) => $item['category_id'] === $categoryId
                    && ! $item['campaign_applied'],
            ));

            // Müşteri lehine en ucuz ürünler önce sıralanır.
            usort(
                $indexes,
                fn (int $left, int $right) => $context->items[$left]['original_price']
                    <=> $context->items[$right]['original_price'],
            );

            $discountRightCount = intdiv(count($indexes), $requiredCount);
            foreach (array_slice($indexes, 0, $discountRightCount) as $index) {
                $item = $context->items[$index];
                $amount = (int) round($item['original_price'] * $rate / 100);

                $context->addLineDiscount(
                    index: $index,
                    amount: $amount,
                    code: 'CATEGORY_DIFFERENT_PRODUCTS',
                    description: "{$item['name']}: en ucuz farklı ürüne %{$rate} indirim",
                    campaign: $campaign,
                    metadata: [
                        'category_id' => $categoryId,
                        'different_product_count' => $requiredCount,
                        'discount_rate' => $rate,
                    ],
                );
            }
        }
    }
}
