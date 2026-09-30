<?php

namespace Tests\Feature;

use App\Enums\CampaignType;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CampaignRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/_test/campaign-request', function (StoreCampaignRequest $request) {
            return response()->json($request->validated());
        })->middleware('web');
    }

    public function test_buy_x_pay_y_accepts_only_valid_product_targets_and_parameters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct('OWN-1');

        $this->postJson('/_test/campaign-request', [
            'name' => 'Kalemlerde 3 Al 2 Öde',
            'code' => ' kalem-3-2 ',
            'type' => CampaignType::BuyXPayY->value,
            'priority' => 20,
            'is_active' => true,
            'is_exclusive' => false,
            'target_ids' => [$product->id],
            'parameters' => ['buy_quantity' => 3, 'pay_quantity' => 2],
        ])->assertOk()
            ->assertJsonPath('code', 'KALEM-3-2')
            ->assertJsonPath('parameters.buy_quantity', 3);
    }

    public function test_campaign_specific_invalid_parameters_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct('OWN-2');

        $this->postJson('/_test/campaign-request', [
            'name' => 'Hatalı Kampanya',
            'type' => CampaignType::BuyXPayY->value,
            'priority' => 20,
            'is_active' => true,
            'is_exclusive' => false,
            'target_ids' => [$product->id],
            'parameters' => ['buy_quantity' => 2, 'pay_quantity' => 3],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('parameters.pay_quantity');
    }

    public function test_a_product_campaign_cannot_target_another_users_product(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser);
        $foreignProduct = $this->makeProduct('FOREIGN-1');

        $this->actingAs($secondUser);

        $this->postJson('/_test/campaign-request', [
            'name' => 'Yetkisiz Hedef',
            'type' => CampaignType::ProductPercentage->value,
            'priority' => 100,
            'is_active' => true,
            'is_exclusive' => false,
            'target_ids' => [$foreignProduct->id],
            'parameters' => ['discount_rate' => 10],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('target_ids.0');
    }

    public function test_category_campaign_requires_an_owned_category_and_valid_dates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['name' => 'Elektronik']);

        $this->postJson('/_test/campaign-request', [
            'name' => 'Elektronik Eşik Kampanyası',
            'type' => CampaignType::CategoryThresholdFixed->value,
            'priority' => 30,
            'is_active' => true,
            'is_exclusive' => false,
            'starts_at' => '2026-10-31 00:00:00',
            'ends_at' => '2026-10-01 00:00:00',
            'target_ids' => [$category->id],
            'parameters' => ['minimum_amount' => 2500, 'discount_amount' => 3000],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ends_at',
                'parameters.discount_amount',
            ]);
    }

    public function test_conditional_campaign_accepts_owned_dynamic_conditions_without_standard_targets(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['name' => 'Elektronik']);

        $this->postJson('/_test/campaign-request', [
            'name' => 'Elektronikte Koşullu İndirim',
            'type' => CampaignType::Conditional->value,
            'is_active' => true,
            'is_exclusive' => false,
            'parameters' => [
                'conditions' => [[
                    'source_type' => 'category',
                    'target_id' => $category->id,
                    'minimum_quantity' => 3,
                    'minimum_amount' => null,
                ]],
                'reward_type' => 'percentage',
                'reward_value' => 15,
                'reward_scope' => 'matching_items',
                'reward_target_ids' => [],
            ],
        ])->assertOk()
            ->assertJsonPath('type', CampaignType::Conditional->value)
            ->assertJsonPath('priority', 100)
            ->assertJsonPath('parameters.repeat_reward', false)
            ->assertJsonPath('parameters.conditions.0.target_id', $category->id);
    }

    public function test_conditional_campaign_returns_user_friendly_validation_messages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['name' => 'Elektronik']);

        $response = $this->postJson('/_test/campaign-request', [
            'name' => 'Eksik Koşullu Kampanya',
            'type' => CampaignType::Conditional->value,
            'is_active' => true,
            'is_exclusive' => false,
            'parameters' => [
                'conditions' => [[
                    'source_type' => 'category',
                    'target_id' => $category->id,
                    'minimum_quantity' => null,
                    'minimum_amount' => null,
                ]],
                'reward_type' => 'percentage',
                'reward_value' => null,
                'reward_scope' => 'selected_categories',
                'reward_target_ids' => [],
            ],
        ])->assertUnprocessable();

        $errors = $response->json('errors');

        $this->assertSame(
            ['İndirim değerini girin.'],
            $errors['parameters.reward_value'],
        );
        $this->assertSame(
            ['İndirimin uygulanacağı en az bir ürün veya kategori seçin.'],
            $errors['parameters.reward_target_ids'],
        );
        $this->assertSame(
            ['1. kural için minimum adet veya minimum tutardan birini girin.'],
            $errors['parameters.conditions.0.minimum_quantity'],
        );
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
