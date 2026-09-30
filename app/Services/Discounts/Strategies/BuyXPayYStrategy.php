<?php

namespace App\Services\Discounts\Strategies;

use App\Models\Campaign;
use App\Services\Discounts\DiscountContext;

final class BuyXPayYStrategy extends CampaignStrategy
{
    public function apply(DiscountContext $context, Campaign $campaign): void//interface uycaksın bunlara

    {
        $targetIds = $this->targetIds($campaign);
        $buyQuantity = (int) $campaign->parameters['buy_quantity'];
        $payQuantity = (int) $campaign->parameters['pay_quantity'];

        foreach ($context->items as $index => $item) {
            if (! in_array($item['product_id'], $targetIds, true)) {
                continue;
            }

            $groupCount = intdiv($item['quantity'], $buyQuantity);
            $freeQuantity = $groupCount * ($buyQuantity - $payQuantity);

            $context->addLineDiscount(
                index: $index,
                amount: $freeQuantity * $item['original_price'],
                code: 'BUY_X_PAY_Y',
                description: "{$item['name']}: {$buyQuantity} al {$payQuantity} öde, {$freeQuantity} adet ücretsiz",
                campaign: $campaign,
                metadata: [
                    'buy_quantity' => $buyQuantity,
                    'pay_quantity' => $payQuantity,
                    'free_quantity' => $freeQuantity,
                ],
            );
        }
    }
}
