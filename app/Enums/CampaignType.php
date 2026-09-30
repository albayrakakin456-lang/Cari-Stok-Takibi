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
            self::ProductPercentage => 'Ürüne Yüzde İndirim',
            self::ProductFixed => 'Ürüne Sabit Tutar İndirimi',
            self::CategoryPercentage => 'Kategoriye Yüzde İndirim',
            self::BuyXPayY => 'X Al Y Öde',
            self::CategoryThirdHalf => 'Kategoride Farklı Ürün İndirimi',
            self::CategoryThresholdFixed => 'Kategori Tutar İndirimi',
            self::Conditional => 'Koşullu Kampanya (Özel Kurgu)',
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
