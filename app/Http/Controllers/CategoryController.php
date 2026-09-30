<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        // Kategorileri bağlı ürünleriyle, ürün seçimi için de tüm ürünleri getir.
        $categories = Category::with(['products' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'code', 'category_id']);

        return view('categories.index', compact('categories', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('user_id', auth()->id()),
            ],
        ]);

        Category::create($validated);

        return back()->with('success', 'Kategori başarıyla oluşturuldu.');
    }

    public function syncProducts(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('user_id', auth()->id()),
            ],
        ]);
        $productIds = array_map('intval', $validated['product_ids'] ?? []);

        DB::transaction(function () use ($category, $productIds): void {
            // Bu kategoriden çıkarılan ürünleri kategorisiz bırak.
            $category->products()
                ->when($productIds !== [], fn ($query) => $query->whereNotIn('id', $productIds))
                ->update(['category_id' => null]);

            // Seçilen ürünleri bu kategoriye taşı.
            Product::query()
                ->whereKey($productIds)
                ->update(['category_id' => $category->id]);
        });

        return back()->with('success', 'Kategori ürünleri güncellendi.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        DB::transaction(function () use ($category): void {
            // Paylaşımlı sunucuda FK desteği olmasa da ürünleri güvenle kategorisiz bırak.
            $category->products()->update(['category_id' => null]);
            $category->delete();
        });

        return back()->with('success', 'Kategori silindi; ürünler korunarak kategorisiz bırakıldı.');
    }
}
