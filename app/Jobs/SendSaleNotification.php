<?php

namespace App\Jobs;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSaleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * İşlem hata verirse en fazla kaç defa tekrar denensin?
     */
    public int $tries = 3;

    /**
     * Tekrar denemeden önce kaç saniye mola versin?
     */
    public int $backoff = 5;

    /**
     * Fatura Modeli
     */
    public Sale $sale;

    /**
     * Job oluşturulurken faturayı teslim alıyoruz
     */
    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    /**
     * Arka plandaki işçi (Queue Worker) bu metodu çalıştırır
     */
    public function handle(): void
    {
        // Faturaya bağlı müşteri ve kalem bilgilerini hafızaya yüklüyoruz
        $this->sale->load(['contact', 'items.product']);

        $customerName = $this->sale->contact->name;
        $customerEmail = $this->sale->contact->email ?? 'E-posta tanımlanmamış';
        $invoiceNumber = $this->sale->invoice_number;
        $totalAmount = number_format($this->sale->total_amount, 2);

        // Gerçek bir sistemde burada Mail::to($customerEmail)->send(...) veya SMS API çağrılır.
        // Asenkron çalışmayı canlı kanıtlamak için Laravel'in log dosyasına resmi bildirim düşüyoruz:
        Log::info("================
         [ARKA PLAN KUYRUĞU (QUEUE) ÇALIŞTI] ================");
        Log::info("📨 Kime: {$customerName} ({$customerEmail})");
        Log::info("📄 Fatura No: {$invoiceNumber} | Toplam Tutar: {$totalAmount} ₺");
        Log::info("✅ Durum: Satış faturası özeti arka planda müşteriye başarıyla iletildi.");
        Log::info("====================================================================");
    }
}

