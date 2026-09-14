<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Contact;
use App\Models\StockMovement;
use App\Models\CashTransaction;
use App\Jobs\SendSaleNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Transaction (İşlem Bütünlüğü) için   kilit sınıf
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index()
    {
        // Satışları ve satışın sahibini (müşteriyi) beraber çekiyoruz
        $sales = Sale::with('contact')->latest()->get();
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        // Fatura keserken seçmek için müşterileri ve ürünleri forma yolluyoruz
        $contacts = Contact::where('type', 'customer')->get();
        $products = Product::all();
        return view('sales.create', compact('contacts', 'products'));
    }

    public function store(Request $request)
    {
        // Rate Limiting: Kullanıcı başına dakikada maksimum 30 satış oluşturma
        $throttleKey = 'sale_store|' . auth()->id();
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput()->withErrors([
                'rate_limit' => "Çok sık satış faturası oluşturuyorsunuz. Lütfen {$seconds} saniye sonra tekrar deneyin."
            ]);
        }
        RateLimiter::hit($throttleKey, 60);

        // 1. Gelen form verilerini doğrula (Kullanıcı izolasyonuyla)
        $productCount = count($request->product_id ?? []);
        $request->validate([
            'contact_id' => [
                'required',
                Rule::exists('contacts', 'id')->where('user_id', auth()->id())->where('type', 'customer'),
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
        ]);

        // Eksi Stokta Satış Engeli: Mükerrer satırları gruplayarak toplam talep edilen miktarı hesapla
        $totalsPerProduct = [];
        foreach ($request->product_id as $index => $productId) {
            $qty = (int) ($request->quantity[$index] ?? 0);
            $totalsPerProduct[$productId] = ($totalsPerProduct[$productId] ?? 0) + $qty;
        }

        foreach ($totalsPerProduct as $productId => $totalRequestedQty) {
            $product = Product::find($productId);

            if (!$product || $product->stock < $totalRequestedQty) {
                $available = $product ? $product->stock : 0;
                $productName = $product ? $product->name : "Ürün #{$productId}";
                return back()->withInput()->withErrors([
                    'stock_error' => "Yetersiz Stok! '{$productName}' için depoda {$available} adet mevcut. Toplam {$totalRequestedQty} adet satılamaz. Eksi stokla satış engellendi."
                ]);
            }
        }

        // 2. TRANSACTION BAŞLAT! (Geri dönülemez yola giriyoruz)
        DB::beginTransaction();

        try {
            $totalAmount = 0; // Fatura toplamını hesaplamak için boş değişken

            // A. Fatura Başlığını Oluştur
            $sale = Sale::create([
                'contact_id' => $request->contact_id,
                'invoice_number' => 'FAT-' . time(), // Örn: FAT-1693746252
                'total_amount' => 0, // Şimdilik 0, kalemleri toplayıp güncelleyeceğiz
            ]);

            // B. Formdan gelen ürünleri döngüye sok
            foreach ($request->product_id as $index => $productId) {
                $quantity = $request->quantity[$index];
                $unitPrice = $request->unit_price[$index];
                $lineTotal = $quantity * $unitPrice;
                $totalAmount += $lineTotal;

                // B1. Fatura Kalemini (SaleItem) Ekle
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ]);

                // B2. Stok Hareketi Tablosuna (Çıkış) Kayıt At
                StockMovement::create([
                    'product_id' => $productId,
                    'type' => 'out', // Satış olduğu için stok çıkışı
                    'quantity' => $quantity,
                    'description' => $sale->invoice_number . ' numaralı faturayla satıldı'
                ]);

                // B3. Ürünün kendi stok miktarını düşür (decrement Laravel'in hazır fonksiyonudur)
                $product = Product::find($productId);
                $product->decrement('stock', $quantity);
            }

            // C. Fatura toplam tutarını hesapladığımız gerçek tutarla güncelle
            $sale->update(['total_amount' => $totalAmount]);

            // D. Kasa Hareketi (Para Girişi) Oluştur (Müşteri peşin ödedi varsayıyoruz)
            CashTransaction::create([
                'contact_id' => $request->contact_id,
                'type' => 'in', 
                'amount' => $totalAmount,
                'description' => $sale->invoice_number . ' numaralı satış tahsilatı'
            ]);

            // 3. HER ŞEY KUSURSUZ ÇALIŞTI, İŞLEMLERİ ONAYLA VE VERİTABANINA YAZ!
            DB::commit();

            // 4. MÜŞTERİYE SATIŞ BİLDİRİMİNİ ARKA PLANDA (QUEUE) GÖNDER!
            // E-posta/SMS gönderimini burada bekletmiyoruz. 
            // Kuyruğa Job atarak kullanıcının formda beklemesini engelliyoruz.
            SendSaleNotification::dispatch($sale);

            return redirect()->route('sales.index')->with('success', 'Satış başarıyla tamamlandı, bildirim arka plan kuyruğuna alındı.');

        } catch (\Throwable $e) {
            // 4. BİR YERDE HATA ÇIKTI, TÜM İŞLEMLERİ GERİ AL!
            DB::rollBack();
            Log::error('Satış faturası kayıt hatası: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            // Kullanıcıyı kullanıcı dostu hata mesajıyla forma geri gönder
            return back()->withInput()->withErrors([
                'error' => 'Satış faturası oluşturulurken bir hata oluştu. Lütfen ürün ve miktar bilgilerini kontrol ediniz.'
            ]);
        }
    }

    /**
     * Fatura Detayını Görüntüle
     */
    public function show(string $id)
    {
        // Eager Loading: N+1 problemini engelleyerek faturayı, müşteriyi ve kalemlerin ürünlerini tek pakette çekiyoruz
        $sale = Sale::with(['contact', 'items.product'])->findOrFail($id);
        return view('sales.show', compact('sale'));
    }

    /**
     * Faturayı İptal Et (Ters Kayıt Mantığıyla Geri Alma)
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::with('items')->findOrFail($id);

            // 1. Kalemleri döngüye alıp düşülen stokları depoya geri ekliyoruz
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);

                    // Stok hareketlerine 'Giriş' (İade) logu atıyoruz
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'description' => $sale->invoice_number . ' numaralı faturanın iptali (Stok İadesi)'
                    ]);
                }
            }

            // 2. Kasaya girmiş olan parayı geri çıkarıyoruz (Ters Kasa Hareketi)
            CashTransaction::create([
                'contact_id' => $sale->contact_id,
                'type' => 'out',
                'amount' => $sale->total_amount,
                'description' => $sale->invoice_number . ' numaralı faturanın iptali (Tahsilat İadesi)'
            ]);

            // 3. Faturayı siliyoruz (Veritabanındaki cascade kuralı kalemleri de temizler)
            $sale->delete();

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Fatura başarıyla iptal edildi. Stoklar ve kasa dengelendi.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Fatura iptal edilirken bir hata oluştu: ' . $e->getMessage());
        }
    }
}

