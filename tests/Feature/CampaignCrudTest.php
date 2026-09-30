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

class CampaignCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_campaign_with_targets(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct('CRUD-1');

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Kalemlerde 3 Al 2 Öde',
            'code' => 'kalem-3-2',
            'type' => CampaignType::BuyXPayY->value,
            'priority' => 20,
            'is_active' => true,
            'is_exclusive' => true,
            'target_ids' => [$product->id],
            'parameters' => ['buy_quantity' => 3, 'pay_quantity' => 2],
        ]);

        $response->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::sole();
        $this->assertSame($user->id, $campaign->user_id);
        $this->assertSame('KALEM-3-2', $campaign->code);
        $this->assertSame(CampaignType::BuyXPayY, $campaign->type);
        // Kullanıcının gönderdiği değer yok sayılır; sıralamayı sistem yönetir.
        $this->assertSame(100, $campaign->priority);
        $this->assertDatabaseHas('campaign_targets', [
            'campaign_id' => $campaign->id,
            'target_type' => CampaignTargetType::Product->value,
            'target_id' => $product->id,
        ]);
    }

    public function test_campaign_form_shows_conditional_builder_without_priority_field(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('campaigns.create'))
            ->assertOk()
            ->assertSee('Koşullu Kampanya (Özel Kurgu)')
            ->assertSee('Kural Ekle')
            ->assertSee('Eşik katlandıkça indirimi artır')
            ->assertDontSee('Tüm koşullar sağlansın (VE)')
            ->assertDontSee('Herhangi biri yeterli (VEYA)')
            ->assertDontSee('name="priority"', false)
            ->assertDontSee('Yayın ve Öncelik');
    }

    public function test_campaign_update_replaces_old_targets_in_one_transaction(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct('CRUD-2');
        $category = Category::create(['name' => 'Elektronik']);
        $campaign = $this->makeCampaign();
        $campaign->targets()->create([
            'target_type' => CampaignTargetType::Product,
            'target_id' => $product->id,
        ]);

        $this->put(route('campaigns.update', $campaign), [
            'name' => 'Elektronik 500 TL İndirim',
            'code' => 'ELEKTRONIK-500',
            'type' => CampaignType::CategoryThresholdFixed->value,
            'priority' => 30,
            'is_active' => true,
            'is_exclusive' => false,
            'target_ids' => [$category->id],
            'parameters' => ['minimum_amount' => 2500, 'discount_amount' => 500],
        ])->assertRedirect(route('campaigns.index'));

        $campaign->refresh();
        $this->assertSame(CampaignType::CategoryThresholdFixed, $campaign->type);
        $this->assertSame(['minimum_amount' => 2500, 'discount_amount' => 500], $campaign->parameters);
        $this->assertDatabaseCount('campaign_targets', 1);
        $this->assertDatabaseHas('campaign_targets', [
            'campaign_id' => $campaign->id,
            'target_type' => CampaignTargetType::Category->value,
            'target_id' => $category->id,
        ]);
    }

    public function test_owner_can_toggle_and_soft_delete_a_campaign(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $campaign = $this->makeCampaign();

        $this->patch(route('campaigns.toggle', $campaign))
            ->assertRedirect();
        $this->assertFalse($campaign->fresh()->is_active);

        $this->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.index'));
        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    }

    public function test_user_cannot_modify_another_users_campaign(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);
        $campaign = $this->makeCampaign();

        $this->actingAs($otherUser);
        $this->patch(route('campaigns.toggle', $campaign))->assertNotFound();
        $this->delete(route('campaigns.destroy', $campaign))->assertNotFound();

        $this->assertTrue($campaign->fresh()->is_active);
        $this->assertNull($campaign->fresh()->deleted_at);
    }

    private function makeCampaign(): Campaign
    {
        return Campaign::create([
            'name' => 'Ürün İndirimi',
            'type' => CampaignType::ProductPercentage,
            'parameters' => ['discount_rate' => 10],
            'priority' => 100,
            'is_active' => true,
            'is_exclusive' => false,
        ]);
    }

    private function makeProduct(string $code): Product
    {
        return Product::create([
            'name' => $code,
            'code' => $code,
            'purchase_price' => 10,
            'sale_price' => 20,
            'stock' => 10,
        ]);
    }
}
