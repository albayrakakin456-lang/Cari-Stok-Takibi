<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\StockMovement;
use App\Services\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct(private readonly WebhookDispatcher $webhooks)
    {
    }

    /** Token sahibinin faturalarını sayfalı olarak listeler. */
    public function index(Request $request): JsonResponse
    {
        // İstemci en fazla 100 kayıt isteyebilir; varsayılan sayfa boyutu 20'dir.
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        // Sorgu kullanıcı ilişkisi üzerinden kurulduğu için başka kullanıcının faturası gelemez.
        $invoices = $request->user()->sales()
            ->select([
                'id', 'user_id', 'contact_id', 'invoice_number', 'external_reference',
                'total_amount', 'status', 'cancelled_at', 'created_at', 'updated_at',
            ])
            ->with('contact:id,name')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Faturalar başarıyla listelendi.',
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /** Ürün, stok, kasa ve fatura kayıtlarını tek işlemde oluşturur. */
    public function store(Request $request): JsonResponse
    {
        // Tekrar gelen isteğin kendi fatura numarasına takılmaması için mevcut referansı önceden buluruz.
        $existingInvoiceId = $request->user()->sales()
            ->where('external_reference', $request->input('external_reference'))
            ->value('id');

        $validated = $request->validate([
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where(fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->where('type', 'customer')),
            ],
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sales', 'invoice_number')
                    ->ignore($existingInvoiceId)
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'external_reference' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $requestFingerprint = $this->invoiceRequestFingerprint($validated);

        [$invoice, $replayed] = DB::transaction(function () use ($request, $validated, $requestFingerprint) {
            // Aynı kullanıcıdan eş zamanlı gelen fatura isteklerini sıraya alırız.
            $request->user()->newQuery()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingInvoice = $request->user()->sales()
                ->where('external_reference', $validated['external_reference'])
                ->first();

            if ($existingInvoice) {
                if (! hash_equals((string) $existingInvoice->request_fingerprint, $requestFingerprint)) {
                    abort(409, 'Bu external_reference daha önce farklı bir istekle kullanıldı.');
                }

                return [$existingInvoice, true];
            }

            $invoiceTotal = 0.0;

            $invoice = $request->user()->sales()->create([
                'contact_id' => $validated['contact_id'],
                'invoice_number' => $validated['invoice_number'],
                'external_reference' => $validated['external_reference'],
                'request_fingerprint' => $requestFingerprint,
                'total_amount' => 0,
                'status' => 'active',
            ]);

            foreach ($validated['items'] as $index => $item) {
                // Satışlar yarıştığında eksi stok oluşmaması için ürün satırı kilitlenir.
                $product = $request->user()->products()
                    ->whereKey($item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = (int) $item['quantity'];

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "{$product->name} için yeterli stok yok. Mevcut stok: {$product->stock}.",
                    ]);
                }

                // Fiyat istemciden alınmaz; güvenilir ürün kaydından okunur.
                $unitPrice = (float) $product->sale_price;
                $lineTotal = round($quantity * $unitPrice, 2);
                $invoiceTotal = round($invoiceTotal + $lineTotal, 2);

                $invoice->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ]);

                $product->decrement('stock', $quantity);

                StockMovement::create([
                    'user_id' => $request->user()->id,
                    'product_id' => $product->id,
                    'sale_id' => $invoice->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'description' => "{$invoice->invoice_number} numaralı API satış faturası",
                ]);
            }

            $invoice->update(['total_amount' => $invoiceTotal]);

            // Mevcut web uygulamasındaki peşin satış davranışıyla aynı kasa kaydı oluşturulur.
            CashTransaction::create([
                'user_id' => $request->user()->id,
                'contact_id' => $invoice->contact_id,
                'sale_id' => $invoice->id,
                'type' => 'in',
                'amount' => $invoiceTotal,
                'description' => "{$invoice->invoice_number} numaralı API satış tahsilatı",
            ]);

            $this->webhooks->invoiceCreated(
                $request->user(),
                $invoice->fresh(),
            );

            return [$invoice, false];
        });

        $invoice->load(['contact:id,name', 'items.product:id,name,code']);

        return response()->json([
            'success' => true,
            'message' => $replayed
                ? 'Bu istek daha önce işlendi; mevcut fatura döndürüldü.'
                : 'Fatura başarıyla oluşturuldu.',
            'data' => $invoice,
            'meta' => ['idempotent_replay' => $replayed],
        ], $replayed ? 200 : 201);
    }

    /** Token sahibine ait tek faturayı ayrıntılarıyla gösterir. */
    public function show(Request $request, int $id): JsonResponse
    {
        $invoice = $request->user()->sales()
            ->with(['contact:id,name', 'items.product:id,name,code'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Fatura başarıyla getirildi.',
            'data' => $invoice,
        ]);
    }

    /** Finansal toplamı değiştirmeden yalnızca fatura numarasını günceller. */
    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = $request->user()->sales()->findOrFail($id);

        if ($invoice->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'İptal edilmiş faturanın numarası değiştirilemez.',
            ], 409);
        }

        $validated = $request->validate([
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sales', 'invoice_number')
                    ->ignore($invoice->id)
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
        ]);

        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fatura numarası başarıyla güncellendi.',
            'data' => $invoice->fresh()->load('contact:id,name'),
        ]);
    }

    /** Faturayı iptal eder; stok ve kasa için ters kayıt oluşturur. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $alreadyCancelled = DB::transaction(function () use ($request, $id) {
            $invoice = $request->user()->sales()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($id);

            // DELETE tekrar gönderilirse stok ve kasa ikinci kez değiştirilmez.
            if ($invoice->status === 'cancelled') {
                return true;
            }

            foreach ($invoice->items as $item) {
                $product = $request->user()->products()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $product->increment('stock', $item->quantity);

                    StockMovement::create([
                        'user_id' => $request->user()->id,
                        'product_id' => $product->id,
                        'sale_id' => $invoice->id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'description' => "{$invoice->invoice_number} numaralı API faturasının iptali",
                    ]);
                }
            }

            CashTransaction::create([
                'user_id' => $request->user()->id,
                'contact_id' => $invoice->contact_id,
                'sale_id' => $invoice->id,
                'type' => 'out',
                'amount' => $invoice->total_amount,
                'description' => "{$invoice->invoice_number} numaralı API faturasının iptali",
            ]);

            $invoice->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->webhooks->invoiceCancelled(
                $request->user(),
                $invoice->fresh(),
            );

            return false;
        });

        return response()->json([
            'success' => true,
            'message' => $alreadyCancelled
                ? 'Fatura daha önce iptal edilmiş; yeni hareket oluşturulmadı.'
                : 'Fatura iptal edildi; stok ve kasa ters kayıtları oluşturuldu.',
            'meta' => ['idempotent_replay' => $alreadyCancelled],
        ]);
    }

    /** Aynı referansın farklı içerikle kullanılmasını tespit eden sabit özet üretir. */
    private function invoiceRequestFingerprint(array $validated): string
    {
        $items = $validated['items'];
        usort($items, fn (array $left, array $right) => $left['product_id'] <=> $right['product_id']);

        return hash('sha256', json_encode([
            'contact_id' => (int) $validated['contact_id'],
            'invoice_number' => $validated['invoice_number'],
            'items' => $items,
        ], JSON_THROW_ON_ERROR));
    }
}
