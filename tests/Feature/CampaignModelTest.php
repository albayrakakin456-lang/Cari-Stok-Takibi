<?php

namespace Tests\Feature;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CampaignModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_casts_and_relationships_are_available(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $campaign = Campaign::create([
            'name' => 'Kalemlerde 3 Al 2 Öde',
            'code' => 'KALEM-3-2',
            'type' => CampaignType::BuyXPayY,
            'parameters' => ['buy_quantity' => 3, 'pay_quantity' => 2],
            'priority' => 20,
            'is_active' => true,
            'is_exclusive' => true,
        ]);

        $target = $campaign->targets()->create([
            'target_type' => CampaignTargetType::Product,
            'target_id' => 42,
        ]);

        $campaign->refresh();

        $this->assertSame(CampaignType::BuyXPayY, $campaign->type);
        $this->assertSame(['buy_quantity' => 3, 'pay_quantity' => 2], $campaign->parameters);
        $this->assertTrue($campaign->is_active);
        $this->assertTrue($campaign->is_exclusive);
        $this->assertSame(CampaignTargetType::Product, $target->target_type);
        $this->assertTrue($campaign->is($user->campaigns()->sole()));
        $this->assertTrue($target->is($campaign->targets()->sole()));
    }

    public function test_active_at_scope_honours_status_and_date_range(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->makeCampaign('Aktif', true, '2026-10-01', '2026-10-31');
        $this->makeCampaign('Süresiz', true, null, null);
        $this->makeCampaign('Henüz başlamadı', true, '2026-11-01', null);
        $this->makeCampaign('Süresi doldu', true, null, '2026-09-30');
        $this->makeCampaign('Kapalı', false, null, null);

        $this->assertSame(
            ['Aktif', 'Süresiz'],
            Campaign::query()->activeAt()->orderBy('id')->pluck('name')->all(),
        );

        $this->assertTrue(Campaign::where('name', 'Aktif')->sole()->isActiveAt());
        $this->assertFalse(Campaign::where('name', 'Kapalı')->sole()->isActiveAt());
    }

    public function test_campaigns_are_isolated_by_authenticated_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser);
        $this->makeCampaign('Birinci firmanın kampanyası');

        $this->actingAs($secondUser);
        $this->makeCampaign('İkinci firmanın kampanyası');

        $this->assertSame(['İkinci firmanın kampanyası'], Campaign::pluck('name')->all());
    }

    private function makeCampaign(
        string $name,
        bool $active = true,
        ?string $startsAt = null,
        ?string $endsAt = null,
    ): Campaign {
        return Campaign::create([
            'name' => $name,
            'type' => CampaignType::ProductPercentage,
            'parameters' => ['discount_rate' => 10],
            'is_active' => $active,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
