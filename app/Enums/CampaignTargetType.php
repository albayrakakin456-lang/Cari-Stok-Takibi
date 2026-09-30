<?php

namespace App\Enums;

enum CampaignTargetType: string
{
    case Product = 'product';
    case Category = 'category';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Ürün',
            self::Category => 'Kategori',
        };
    }
}
