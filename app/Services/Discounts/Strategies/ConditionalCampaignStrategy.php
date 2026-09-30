<?php

namespace App\Services\Discounts\Strategies;

use App\Models\Campaign;
use App\Services\Discounts\DiscountContext;

final class ConditionalCampaignStrategy extends CampaignStrategy
{
    public function apply(DiscountContext $context, Campaign $campaign): void
    {
        $parameters = $campaign->parameters;
        $results = array_map(
            fn (array $condition) => $this->evaluateCondition($context, $condition),
            $parameters['conditions'],
        );

        // Panelde VE/VEYA seçimi yoktur; eklenen bütün koşullar sağlanmalıdır.
        $matched = collect($results)->every(fn (array $result) => $result['passed']);

        if (! $matched) {
            return;
        }

        $repeatCount = ($parameters['repeat_reward'] ?? false)
            ? min(array_column($results, 'repeat_count'))
            : 1;

        $indexes = $this->rewardIndexes($context, $parameters, $results);
        if ($indexes === []) {
            return;
        }

        if ($parameters['reward_type'] === 'percentage') {
            // Tekrarlanan yüzde ödülü doğrusal artar ancak satırı ücretsizden daha aşağı indiremez.
            $rate = min(100, (float) $parameters['reward_value'] * $repeatCount);
            $this->applyPercentage($context, $campaign, $indexes, $rate, $repeatCount);

            return;
        }

        $this->applyFixedAmount(
            $context,
            $campaign,
            $indexes,
            $this->moneyToKurus($parameters['reward_value']) * $repeatCount,
            $repeatCount,
        );
    }

    private function evaluateCondition(DiscountContext $context, array $condition): array
    {
        $indexes = array_keys(array_filter(
            $context->items,
            fn (array $item) => $condition['source_type'] === 'product'
                ? $item['product_id'] === (int) $condition['target_id']
                : $item['category_id'] === (int) $condition['target_id'],
        ));

        $quantity = array_sum(array_map(
            fn (int $index) => $context->items[$index]['quantity'],
            $indexes,
        ));
        $amount = array_sum(array_map(
            fn (int $index) => $context->items[$index]['quantity'] * $context->items[$index]['original_price'],
            $indexes,
        ));

        $minimumQuantity = isset($condition['minimum_quantity']) && $condition['minimum_quantity'] !== ''
            ? (int) $condition['minimum_quantity']
            : null;
        $minimumAmount = isset($condition['minimum_amount']) && $condition['minimum_amount'] !== ''
            ? $this->moneyToKurus($condition['minimum_amount'])
            : null;

        return [
            'passed' => $indexes !== []
                && ($minimumQuantity === null || $quantity >= $minimumQuantity)
                && ($minimumAmount === null || $amount >= $minimumAmount),
            'indexes' => $indexes,
            'repeat_count' => $this->conditionRepeatCount(
                $quantity,
                $amount,
                $minimumQuantity,
                $minimumAmount,
            ),
        ];
    }

    private function conditionRepeatCount(
        int $quantity,
        int $amount,
        ?int $minimumQuantity,
        ?int $minimumAmount,
    ): int {
        $counts = [];
        if ($minimumQuantity !== null) {
            $counts[] = intdiv($quantity, $minimumQuantity);
        }
        if ($minimumAmount !== null) {
            $counts[] = intdiv($amount, $minimumAmount);
        }

        return $counts === [] ? 1 : max(1, min($counts));
    }

    private function rewardIndexes(DiscountContext $context, array $parameters, array $results): array
    {
        $scope = $parameters['reward_scope'];
        $targetIds = array_map('intval', $parameters['reward_target_ids'] ?? []);

        $indexes = match ($scope) {
            'selected_products' => array_keys(array_filter(
                $context->items,
                fn (array $item) => in_array($item['product_id'], $targetIds, true),
            )),
            'selected_categories' => array_keys(array_filter(
                $context->items,
                fn (array $item) => in_array($item['category_id'], $targetIds, true),
            )),
            default => collect($results)
                ->filter(fn (array $result) => $result['passed'])
                ->flatMap(fn (array $result) => $result['indexes'])
                ->unique()
                ->values()
                ->all(),
        };

        // Bir ürün satırına yalnızca bir satır kampanyası uygulanabilir.
        return array_values(array_filter(
            $indexes,
            fn (int $index) => $context->canApplyLineCampaign($index),
        ));
    }

    private function applyPercentage(
        DiscountContext $context,
        Campaign $campaign,
        array $indexes,
        float $rate,
        int $repeatCount,
    ): void {
        foreach ($indexes as $index) {
            $item = $context->items[$index];
            $context->addLineDiscount(
                index: $index,
                amount: (int) round($context->lineNet($item) * $rate / 100),
                code: 'CONDITIONAL_PERCENTAGE',
                description: "{$item['name']}: koşullu kampanya %{$rate} indirimi",
                campaign: $campaign,
                metadata: [
                    'reward_type' => 'percentage',
                    'reward_value' => $rate,
                    'repeat_count' => $repeatCount,
                ],
            );
        }
    }

    private function applyFixedAmount(
        DiscountContext $context,
        Campaign $campaign,
        array $indexes,
        int $totalDiscount,
        int $repeatCount,
    ): void {
        // Sabit ödülü, satırların kalan tutarı tükenene kadar sırayla dağıtırız.
        foreach ($indexes as $index) {
            if ($totalDiscount <= 0) {
                break;
            }

            $item = $context->items[$index];
            $amount = min($totalDiscount, $context->lineNet($item));
            if ($context->addLineDiscount(
                index: $index,
                amount: $amount,
                code: 'CONDITIONAL_FIXED',
                description: "{$item['name']}: koşullu kampanya sabit indirimi",
                campaign: $campaign,
                metadata: ['reward_type' => 'fixed', 'repeat_count' => $repeatCount],
            )) {
                $totalDiscount -= $amount;
            }
        }
    }
}
