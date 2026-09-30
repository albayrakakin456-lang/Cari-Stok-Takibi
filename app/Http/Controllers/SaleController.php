<?php

namespace App\Http\Controllers;

use App\Jobs\SendSaleNotification;
use App\Models\CashTransaction;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\Discounts\DiscountEngine;
use App\Services\WebhookDispatcher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request; // Transaction (İşlem Bütünlüğü) için   kilit sınıf
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function __construct(
        private readonly WebhookDispatcher $webhooks,
        private readonly DiscountEngine $discountEngine,
    ) {}

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
        $throttleKey = 'sale_store|'.auth()->id();
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withInput()->withErrors([
                'rate_limit' => "Çok sık satış faturası oluşturuyorsunuz. Lütfen {$seconds} saniye sonra tekrar deneyin.",
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

            if (! $product || $product->stock < $totalRequestedQty) {
                $available = $product ? $product->stock : 0;
                $productName = $product ? $product->name : "Ürün #{$productId}";

                return back()->withInput()->withErrors([
                    'stock_error' => "Yetersiz Stok! '{$productName}' için depoda {$available} adet mevcut. Toplam {$totalRequestedQty} adet satılamaz. Eksi stokla satış engellendi.",
                ]);
            }
        }

        $contact = Contact::findOrFail($request->contact_id);
        $cart = collect($request->product_id)->map(fn ($productId, $index) => [
            'product_id' => (int) $productId,
            'quantity' => (int) $request->quantity[$index],
        ])->all();

        // Bütün fiyat ve kampanya hesapları tek bir merkezde, tanımlı öncelik sırasıyla yapılır.
        $calculation = $this->discountEngine->calculate($cart, $contact);

        // 2. TRANSACTION BAŞLAT! (Geri dönülemez yola giriyoruz)
        DB::beginTransaction();

        try {
            // A. Fatura Başlığını Oluştur
            $sale = Sale::create([
                'contact_id' => $request->contact_id,
                'invoice_number' => 'FAT-'.time(), // Örn: FAT-1693746252
                'subtotal' => $calculation['subtotal'],
                'campaign_discount' => $calculation['campaign_discount'],
                'customer_discount' => $calculation['customer_discount'],
                'total_amount' => $calculation['total_amount'],
            ]);

            // İndirim dökümündeki item_index değerlerini gerçek SaleItem kayıtlarına bağlamak için tutulur.
            $saleItems = [];

            // B. Motor mükerrer ürünleri birleştirdiği için hesaplanmış kalemleri kaydet.
            foreach ($calculation['items'] as $itemIndex => $item) {
                $product = Product::findOrFail($item['product_id']);

                // B1. Fatura Kalemini (SaleItem) Ekle
                $saleItems[$itemIndex] = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'original_price' => $item['original_price'],
                    'discount_amount' => $item['discount_amount'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);

                // B2. Stok Hareketi Tablosuna (Çıkış) Kayıt At
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'out', // Satış olduğu için stok çıkışı
                    'quantity' => $item['quantity'],
                    'description' => $sale->invoice_number.' numaralı faturayla satıldı',
                ]);

                // B3. Ürünün kendi stok miktarını düşür (decrement Laravel'in hazır fonksiyonudur)
                $product->decrement('stock', $item['quantity']);
            }

            // C. Uygulanan kampanya ve cari iskontolarının denetim kaydını oluştur.
            foreach ($calculation['discounts'] as $discount) {
                SaleDiscount::create([
                    'sale_id' => $sale->id,
                    'sale_item_id' => $discount['item_index'] !== null
                        ? $saleItems[$discount['item_index']]->id
                        : null,
                    'campaign_id' => $discount['campaign_id'],
                    'code' => $discount['code'],
                    'campaign_name' => $discount['campaign_name'],
                    'campaign_type' => $discount['campaign_type'],
                    'description' => $discount['description'],
                    'amount' => $discount['amount'],
                    'metadata' => $discount['metadata'],
                ]);
            }

            // D. Kasa Hareketi (Para Girişi) Oluştur (Müşteri peşin ödedi varsayıyoruz)
            CashTransaction::create([
                'contact_id' => $request->contact_id,
                'type' => 'in',
                'amount' => $calculation['total_amount'],
                'description' => $sale->invoice_number.' numaralı satış tahsilatı',
            ]);

            $this->webhooks->invoiceCreated(
                $request->user(),
                $sale->fresh(),
            );

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
            Log::error('Satış faturası kayıt hatası: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Kullanıcıyı kullanıcı dostu hata mesajıyla forma geri gönder
            return back()->withInput()->withErrors([
                'error' => 'Satış faturası oluşturulurken bir hata oluştu. Lütfen ürün ve miktar bilgilerini kontrol ediniz.',
            ]);
        }
    }

    /**
     * Fatura Detayını Görüntüle
     */
    public function show(string $id)
    {
        // Eager Loading: N+1 problemini engelleyerek faturayı, müşteriyi ve kalemlerin ürünlerini tek pakette çekiyoruz
        $sale = Sale::with(['contact', 'items.product', 'discounts'])->findOrFail($id);

        return view('sales.show', compact('sale'));
    }

    /**
     * Satış faturasını PDF olarak indir.
     */
    public function downloadPdf(string $id)
    {
        $sale = Sale::with(['contact', 'items.product', 'discounts'])->findOrFail($id);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $sale,
            'invoiceType' => 'sale',
        ])->setPaper('a4')->download($sale->invoice_number.'.pdf');
    }

    /**
     * Faturayı İptal Et (Ters Kayıt Mantığıyla Geri Alma)
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $alreadyCancelled = DB::transaction(function () use ($request, $id) {
                $sale = Sale::with('items')
                    ->lockForUpdate()
                    ->findOrFail($id);

                // Aynı iptal isteği tekrar gelirse stok ve kasa ikinci kez değiştirilmez.
                if ($sale->status === 'cancelled') {
                    return true;
                }

                foreach ($sale->items as $item) {
                    $product = Product::whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment('stock', $item->quantity);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'sale_id' => $sale->id,
                            'type' => 'in',
                            'quantity' => $item->quantity,
                            'description' => $sale->invoice_number.' numaralı faturanın iptali (Stok İadesi)',
                        ]);
                    }
                }

                CashTransaction::create([
                    'contact_id' => $sale->contact_id,
                    'sale_id' => $sale->id,
                    'type' => 'out',
                    'amount' => $sale->total_amount,
                    'description' => $sale->invoice_number.' numaralı faturanın iptali (Tahsilat İadesi)',
                ]);

                $sale->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                $this->webhooks->invoiceCancelled(
                    $request->user(),
                    $sale->fresh(),
                );

                return false;
            });

            return redirect()->route('sales.index')->with(
                'success',
                $alreadyCancelled
                    ? 'Fatura daha önce iptal edilmiş; yeni hareket oluşturulmadı.'
                    : 'Fatura başarıyla iptal edildi. Stoklar ve kasa dengelendi.',
            );

        } catch (\Exception $e) {
            return back()->withErrors('Fatura iptal edilirken bir hata oluştu: '.$e->getMessage());
        }
    }
}
