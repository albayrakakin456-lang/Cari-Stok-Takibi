<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /** Token sahibinin ürünlerini dış servis için gerekli alanlarla listeler. */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $products = $request->user()->products()
            ->select(['id', 'name', 'code', 'barcode', 'sale_price', 'tax_rate', 'stock'])
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Ürünler başarıyla listelendi.',
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /** Ürünü ve varsa açılış stok hareketini tek işlemde oluşturur. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[A-Za-z0-9\-_.]+$/',
                Rule::unique('products', 'code')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'barcode' => ['nullable', 'string', 'min:8', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'opening_stock' => ['required', 'integer', 'min:0'],
        ]);

        $openingStock = $validated['opening_stock'];
        unset($validated['opening_stock']);
        $validated['stock'] = $openingStock;

        $product = DB::transaction(function () use ($request, $validated, $openingStock) {
            $product = $request->user()->products()->create($validated);

            if ($openingStock > 0) {
                $product->stockMovements()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'in',
                    'quantity' => $openingStock,
                    'description' => 'API açılış stoğu girişi',
                ]);
            }

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Ürün başarıyla oluşturuldu.',
            'data' => $product,
        ], 201);
    }
}
