<?php

namespace Tests\Feature;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignFormPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_displays_campaign_types_and_owned_targets(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct('Form Ürünü', 'FORM-1');
        $category = Category::create(['name' => 'Form Kategorisi']);

        $this->get(route('campaigns.create'))
            ->assertOk()
            ->assertSee('Yeni Kampanya')
            ->assertSee('X Al Y Öde')
            ->assertSee($product->name)
            ->assertSee($category->name)
            ->assertSee('parameters[buy_quantity]', false);
    }

    public function test_edit_form_displays_existing_campaign_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct('Kalem', 'PEN-EDIT');
        $campaign = Campaign::create([
            'name' => 'Kalemlerde 3 Al 2 Öde',
            'code' => 'PEN-3-2',
            'type' => CampaignType::BuyXPayY,
            'parameters' => ['buy_quantity' => 3, 'pay_quantity' => 2],
            'priority' => 20,
            'is_active' => true,
        ]);
        $campaign->targets()->create([
            'target_type' => CampaignTargetType::Product,
            'target_id' => $product->id,
        ]);

        $this->get(route('campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('Kampanyayı Düzenle')
            ->assertSee('Kalemlerde 3 Al 2 Öde')
            ->assertSee('PEN-3-2')
            ->assertSee('value="3"', false)
            ->assertSee('value="2"', false);
    }

    private function makeProduct(string $name, string $code): Product
    {
        return Product::create([
            'name' => $name,
            'code' => $code,
            'purchase_price' => 10,
            'sale_price' => 20,
            'stock' => 10,
        ]);
    }
}
