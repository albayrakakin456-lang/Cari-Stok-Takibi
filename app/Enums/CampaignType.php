<?php

namespace App\Enums;

enum CampaignType: string
{
    case ProductPercentage = 'product_percentage';
    case ProductFixed = 'product_fixed';
    case CategoryPercentage = 'category_percentage';
    case BuyXPayY = 'buy_x_pay_y';
    case CategoryThirdHalf = 'category_third_half';
    case CategoryThresholdFixed = 'category_threshold_fixed';
    case Conditional = 'conditional';

    public function label(): string
    {
        return match ($this) {
            self::ProductPercentage => __('Product Percentage Discount'),
            self::ProductFixed => __('Product Fixed Amount Discount'),
            self::CategoryPercentage => __('Category Percentage Discount'),
            self::BuyXPayY => __('Buy X Pay Y'),
            self::CategoryThirdHalf => __('Different Products in Category Discount'),
            self::CategoryThresholdFixed => __('Category Amount Discount'),
            self::Conditional => __('Conditional Campaign (Custom Setup)'),
        };
    }

    public function targetType(): ?CampaignTargetType
    {
        return match ($this) {
            self::ProductPercentage,
            self::ProductFixed,
            self::BuyXPayY => CampaignTargetType::Product,
            self::CategoryPercentage,
            self::CategoryThirdHalf,
            self::CategoryThresholdFixed => CampaignTargetType::Category,
            // Koşullu kampanyanın hedefleri parameters JSON alanında saklanır.
            self::Conditional => null,
        };
    }

    public function phase(): int
    {
        return match ($this) {
            self::ProductPercentage,
            self::ProductFixed,
            self::CategoryPercentage,
            self::BuyXPayY,
            self::CategoryThirdHalf => 1,
            self::CategoryThresholdFixed => 2,
            self::Conditional => 1,
        };
    }
}
