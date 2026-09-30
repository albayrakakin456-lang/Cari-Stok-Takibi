<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CampaignDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_storage_tables_have_the_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('campaigns', [
            'user_id',
            'name',
            'code',
            'type',
            'description',
            'parameters',
            'priority',
            'is_active',
            'is_exclusive',
            'starts_at',
            'ends_at',
            'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('campaign_targets', [
            'campaign_id',
            'target_type',
            'target_id',
        ]));

        $this->assertTrue(Schema::hasColumns('sale_discounts', [
            'user_id',
            'sale_id',
            'sale_item_id',
            'campaign_id',
            'code',
            'campaign_name',
            'campaign_type',
            'description',
            'amount',
            'metadata',
        ]));
    }
}
