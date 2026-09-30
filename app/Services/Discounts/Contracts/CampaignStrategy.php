<?php

namespace App\Services\Discounts\Contracts;

use App\Models\Campaign;
use App\Services\Discounts\DiscountContext;

interface CampaignStrategy
{
    public function apply(DiscountContext $context, Campaign $campaign): void;
}
