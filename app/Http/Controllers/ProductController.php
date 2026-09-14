<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::latest()->get();
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 0. Rate Limiting (Kötüye Kullanım Koruması: Dakikada max 30 istek)
        $throttleKey = 'product-store:' . (auth()->id() ?? $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput()->withErrors([
                'rate_limit' => __('Too many requests. Please wait :seconds seconds before trying again.', ['seconds' => $seconds])
            ]);
        }
        RateLimiter::hit($throttleKey, 60);

        // 1. Formdan gelen verileri doğrula (Validation)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            // Kullanıcı bazlı benzersiz stok kodu kontrolü + min/max uzunluk + regex
            'code' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[A-Za-z0-9\-_.]+$/',
                Rule::unique('products', 'code')->where('user_id', auth()->id()),
            ],
            // Barkod: Min 8, Max 30 alfanümerik/tire formatı
            'barcode' => ['nullable', 'string', 'min:8', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/'],
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'tax_rate' => 'required|integer|min:0|max:100',
            'min_stock' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0' // Başlangıç stoğu artık zorunludur!
        ], [
            'name.required' => __('Product name is required.'),
            'code.required' => __('Stock code is required.'),
            'code.min' => __('Stock code must be at least :min characters.'),
            'code.max' => __('Stock code may not be greater than :max characters.'),
            'code.regex' => __('Stock code may only contain letters, numbers, dashes, underscores, and dots.'),
            'code.unique' => __('This stock code is already in use in your account.'),
            'barcode.min' => __('Barcode must be at least :min characters.'),
            'barcode.max' => __('Barcode may not be greater than :max characters.'),
            'barcode.regex' => __('Barcode may only contain letters, numbers, and dashes.'),
            'purchase_price.required' => __('Purchase price is required.'),
            'sale_price.required' => __('Sale price is required.'),
            'min_stock.required' => __('Critical stock level is required.'),
            'stock.required' => __('Initial stock quantity is required.'),
            'stock.integer' => __('Initial stock must be a whole number.'),
            'stock.min' => __('Initial stock cannot be negative.'),
        ]);

        // 2. Doğrulanan veriyi kullanarak ürünü veritabanına kaydet
        $product = Product::create($validatedData);

        // Muhasebe Kuralı: Stok asla hareketsiz değişemez! 
        // Eğer başlangıç stoğu girildiyse bunu ilk stok hareketi (Giriş) olarak kaydediyoruz.
        if ($product->stock > 0) {
            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock,
                'description' => 'Açılış / Sayım Stoğu Girişi'
            ]);
        }

        // 3. Başarı mesajıyla tabloya geri yolla
        return redirect()->route('products.index')->with('success', 'Ürün başarıyla oluşturuldu.');
    }

    /**
     * Ürün Kartı ve Stok Hareketleri Geçmişi
     */
    public function show(string $id)
    {
        // 1. İlişkiyi koşullandırarak çekme (Constrained Eager Loading):
        // Ürünü çekerken stok hareketlerini tarihe göre en yeniden en eskiye sıralatıyoruz
        $product = Product::with(['stockMovements' => function ($query) {
            $query->latest();
        }])->findOrFail($id);

        // 2. Depo İstatistikleri: Toplam giren ve çıkan adetler
        $totalIn = $product->stockMovements->where('type', 'in')->sum('quantity');
        $totalOut = $product->stockMovements->where('type', 'out')->sum('quantity');
    
        // 3. Finansal Stok Değeri: Depodaki malın maliyeti ve potansiyel cirosu
        $stockCostValue = $product->stock * $product->purchase_price;
        $stockSaleValue = $product->stock * $product->sale_price;

        return view('products.show', compact('product', 'totalIn', 'totalOut', 'stockCostValue', 'stockSaleValue'));
    }
}
   