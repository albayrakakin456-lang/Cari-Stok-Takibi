<?php

namespace App\Services\Discounts\Strategies;

use App\Models\Campaign;
use App\Services\Discounts\Contracts\CampaignStrategy as CampaignStrategyContract;

abstract class CampaignStrategy implements CampaignStrategyContract
{
    protected function targetIds(Campaign $campaign): array
    {
        $campaign->loadMissing('targets');

        return $campaign->targets
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function moneyToKurus(int|float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
