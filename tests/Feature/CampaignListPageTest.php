<?php

namespace Tests\Feature;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignListPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_list_displays_owned_campaign_and_its_target(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = Product::create([
            'name' => 'Kablosuz Klavye',
            'code' => 'KLV-1',
            'purchase_price' => 100,
            'sale_price' => 200,
            'stock' => 10,
        ]);
        $campaign = Campaign::create([
            'name' => 'Klavye Fırsatı',
            'code' => 'KLV-FIRSAT',
            'type' => CampaignType::BuyXPayY,
            'parameters' => ['buy_quantity' => 3, 'pay_quantity' => 2],
            'priority' => 20,
            'is_active' => true,
        ]);
        $campaign->targets()->create([
            'target_type' => CampaignTargetType::Product,
            'target_id' => $product->id,
        ]);

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee('Kampanyalar')
            ->assertSee('Klavye Fırsatı')
            ->assertSee('Kablosuz Klavye')
            ->assertSee('X Al Y Öde')
            ->assertSee('KLV-FIRSAT');
    }

    public function test_campaign_list_shows_empty_state(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee('Henüz kampanya yok')
            ->assertSee('Yeni Kampanya');
    }
}
