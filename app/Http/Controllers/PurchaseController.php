<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Contact;
use App\Models\StockMovement;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index()
    {
        // Alış faturalarını ve faturanın sahibini (tedarikçiyi) tek seferde çekiyoruz
        $purchases = Purchase::with('contact')->latest()->get();
        return view('purchases.index', compact('purchases'));
    }

    public function create(Request $request)
    {
        // Alış faturası sadece Tedarikçilere (supplier) kesilir
        $contacts = Contact::where('type', 'supplier')->get();
        $products = Product::all();

        // Dashboard'daki kritik stok listesinden gelindiyse ilgili ürünü ilk satırda seç.
        // Ürün koleksiyonu kullanıcıya göre scope edildiği için başka kullanıcıların ürünleri seçilemez.
        $requestedProductId = $request->integer('product_id');
        $selectedProductId = $products->contains('id', $requestedProductId)
            ? $requestedProductId
            : null;

        return view('purchases.create', compact('contacts', 'products', 'selectedProductId'));
    }

    public function store(Request $request)
    {
        // 0. Rate Limiting (Kötüye Kullanım Koruması: Dakikada max 30 alış faturası)
        $throttleKey = 'purchase-store:' . (auth()->id() ?? $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput()->withErrors([
                'rate_limit' => __('Too many requests. Please wait :seconds seconds before trying again.', ['seconds' => $seconds])
            ]);
        }
        RateLimiter::hit($throttleKey, 60);

        // 1. Form doğrulaması (Satır bazlı tam kontrol ve kullanıcı izolasyonu)
        $productCount = count($request->product_id ?? []);
        $request->validate([
            'contact_id' => [
                'required',
                Rule::exists('contacts', 'id')->where('user_id', auth()->id())->where('type', 'supplier'),
            ],
            'product_id' => 'required|array|min:1',
            'product_id.*' => [
                'required',
                Rule::exists('products', 'id')->where('user_id', auth()->id()),
            ],
            'quantity' => "required|array|size:{$productCount}",
            'quantity.*' => 'required|integer|min:1',
            'unit_price' => "required|array|size:{$productCount}",
            'unit_price.*' => 'required|numeric|min:0',
        ], [
            'contact_id.required' => __('Please select a supplier.'),
            'contact_id.exists' => __('The selected supplier does not exist.'),
            'product_id.required' => __('Please add at least one product row.'),
            'product_id.min' => __('Please add at least one product row.'),
            'product_id.*.required' => __('Please select a product for each line.'),
            'product_id.*.exists' => __('One or more selected products are invalid.'),
            'quantity.required' => __('Quantity is required.'),
            'quantity.*.required' => __('Quantity is required for each line.'),
            'quantity.*.integer' => __('Quantity must be a valid whole number.'),
            'quantity.*.min' => __('Quantity must be at least 1.'),
            'unit_price.required' => __('Unit price is required.'),
            'unit_price.*.required' => __('Unit price is required for each line.'),
            'unit_price.*.numeric' => __('Unit price must be a valid number.'),
            'unit_price.*.min' => __('Unit price cannot be negative.'),
        ]);

        // 2. Transaction Başlat
        DB::beginTransaction();

        try {
            $totalAmount = 0;

            // A. Alış Faturası Başlığını Oluştur
            $purchase = Purchase::create([
                'contact_id' => $request->contact_id,
                'invoice_number' => 'ALIS-' . time(),
                'total_amount' => 0,
            ]);

            // B. Alınan ürünleri döngüye sok
            foreach ($request->product_id as $index => $productId) {
                $quantity = (int) $request->quantity[$index];
                $unitPrice = (float) $request->unit_price[$index];
                $lineTotal = $quantity * $unitPrice;
                $totalAmount += $lineTotal;

                // B1. Alış Kalemini (PurchaseItem) Ekle
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ]);

                // B2. Stok Hareketi: Depoya 'Giriş' (in) yaz
                StockMovement::create([
                    'product_id' => $productId,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'description' => $purchase->invoice_number . ' numaralı irsaliye/fatura ile depoya girdi'
                ]);

                // B3. Ürünün mevcut stoğunu artır (increment) ve son alış fiyatını güncelle
                $product = Product::find($productId);
                $product->increment('stock', $quantity);
                $product->update(['purchase_price' => $unitPrice]);
            }

            // C. Fatura toplam tutarını güncelle
            $purchase->update(['total_amount' => $totalAmount]);

            // D. Ödeme Seçeneği: Eğer peşin ödendi kutucuğu işaretlendiyse kasadan para çıkar
            if ($request->has('is_paid') && $request->is_paid == '1') {
                CashTransaction::create([
                    'contact_id' => $request->contact_id,
                    'type' => 'out', // Kasadan çıkış
                    'amount' => $totalAmount,
                    'description' => $purchase->invoice_number . ' numaralı alış faturası peşin ödemesi'
                ]);
            }

            // 3. İşlemleri onayla
            DB::commit();

            return redirect()->route('purchases.index')->with('success', __('Alış faturası başarıyla kaydedildi, stoklar depoya girdi.'));

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Alış faturası kayıt hatası: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors([
                'error' => __('Alış işlemi sırasında beklenmeyen bir hata oluştu. Lütfen ürün satırlarını kontrol edip tekrar deneyiniz.')
            ]);
        }
    }

    public function show(string $id)
    {
        // Eager Loading: Faturayı, tedarikçiyi ve kalemlerdeki ürünleri tek seferde çekiyoruz
        $purchase = Purchase::with(['contact', 'items.product'])->findOrFail($id);
        return view('purchases.show', compact('purchase'));
    }

    public function destroy(string $id)
    {
        // Alış faturasını iptal etme (Ters Kayıt: Giren stokları geri düş, kasadan çıkan parayı geri sok)
        DB::beginTransaction();

        try {
            $purchase = Purchase::with('items')->findOrFail($id);

            foreach ($purchase->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock', $item->quantity);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $item->quantity,
                        'description' => $purchase->invoice_number . ' numaralı alış faturası iptali (Stok Çıkışı)'
                    ]);
                }
            }

            // 2. Peşin ödenmiş bir faturaysa kasadan çıkan parayı iade al (Ters Kasa Hareketi)
            $cashTx = CashTransaction::where('contact_id', $purchase->contact_id)
                ->where('type', 'out')
                ->where('description', 'like', '%' . $purchase->invoice_number . '%')
                ->first();

            if ($cashTx) {
                CashTransaction::create([
                    'contact_id' => $purchase->contact_id,
                    'type' => 'in',
                    'amount' => $cashTx->amount,
                    'description' => $purchase->invoice_number . ' numaralı alış faturası iptali (Ödeme İadesi)'
                ]);
            }

            // 3. Faturayı sil (Cascade kalemleri de temizler)
            $purchase->delete();

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Alış faturası iptal edildi, ürünler depodan düşüldü.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('İptal sırasında hata oluştu: ' . $e->getMessage());
        }
    }
}
