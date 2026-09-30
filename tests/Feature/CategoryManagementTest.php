<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_category_and_assign_existing_products(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->product('CAT-1');

        $this->post(route('categories.store'), ['name' => 'Elektronik'])->assertRedirect();
        $category = Category::sole();

        $this->patch(route('categories.products.sync', $category), [
            'product_ids' => [$product->id],
        ])->assertRedirect();

        $this->assertSame($category->id, $product->fresh()->category_id);
        $this->get(route('campaigns.create'))->assertOk()->assertSee('Elektronik');
    }

    public function test_deleting_category_keeps_product_and_clears_its_category(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['name' => 'Silinecek']);
        $product = $this->product('CAT-2', $category->id);

        $this->delete(route('categories.destroy', $category))->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => null]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_assign_another_users_product(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner);
        $foreignProduct = $this->product('CAT-FOREIGN');

        $this->actingAs($other);
        $category = Category::create(['name' => 'Benim Kategorim']);

        $this->patch(route('categories.products.sync', $category), [
            'product_ids' => [$foreignProduct->id],
        ])->assertSessionHasErrors('product_ids.0');
    }

    private function product(string $code, ?int $categoryId = null): Product
    {
        return Product::create([
            'category_id' => $categoryId,
            'name' => $code,
            'code' => $code,
            'purchase_price' => 10,
            'sale_price' => 20,
            'stock' => 10,
        ]);
    }
}
