<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Sale;
use App\Models\CashTransaction;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyBusinessReport extends Command
{
    /**
     * Artisan terminalinden çağrılacak komutun adı ve imzası.
     * Terminalden: php artisan report:daily
     *
     * @var string
     */
    protected $signature = 'report:daily';

    /**
     * Komutun açıklaması (php artisan list çalıştırıldığında görünür).
     *
     * @var string
     */
    protected $description = 'Günlük kritik stok, finansal özet ve hareketsiz carileri raporlar ve loglar.';

    /**
     * Komut tetiklendiğinde çalışan ana mantık.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("==================================================");
        $this->info("   📊 GÜNLÜK İŞLETME RAPORU - " . $today->format('d.m.Y'));
        $this->info("==================================================");

        // 1. KRİTİK STOK ANALİZİ
        // whereColumn: SQL düzeyinde iki ayrı sütunu dinamik kıyaslar (stock <= min_stock)
        $criticalProducts = Product::whereColumn('stock', '<=', 'min_stock')->get();
        $this->newLine();
        $this->warn("⚠️  1. KRİTİK SEVİYEDEKİ ÜRÜNLER (Toplam: " . $criticalProducts->count() . ")");

        if ($criticalProducts->isEmpty()) {
            $this->line("   Tüm stoklar güvenli seviyede. Kritik ürün yok.");
        } else {
            $stockRows = $criticalProducts->map(function ($p) {
                return [
                    'ID' => $p->id,
                    'Kod' => $p->code ?? '-',
                    'Ürün Adı' => $p->name,
                    'Mevcut Stok' => $p->stock,
                    'Kritik Eşik' => $p->min_stock,
                    'Fark' => $p->stock - $p->min_stock,
                ];
            });
            $this->table(['ID', 'Kod', 'Ürün Adı', 'Mevcut Stok', 'Kritik Eşik', 'Fark'], $stockRows);
        }

        // 2. GÜNLÜK SATIŞ VE KASA FİNANS ÖZETİ
        $salesTodayCount = Sale::whereDate('created_at', $today)->count();
        $salesTodayTotal = Sale::whereDate('created_at', $today)->sum('total_amount');

        $cashInToday = CashTransaction::where('type', 'in')->whereDate('created_at', $today)->sum('amount');
        $cashOutToday = CashTransaction::where('type', 'out')->whereDate('created_at', $today)->sum('amount');
        $netCashToday = $cashInToday - $cashOutToday;

        $totalCashBalance = CashTransaction::where('type', 'in')->sum('amount') - CashTransaction::where('type', 'out')->sum('amount');

        $this->newLine();
        $this->info("💰 2. GÜNLÜK FİNANS & KASA ÖZETİ");
        $financeRows = [
            ['Bugünkü Toplam Satış Adedi', $salesTodayCount . ' adet'],
            ['Bugünkü Satış Cirosu', '₺' . number_format($salesTodayTotal, 2, ',', '.')],
            ['Bugün Kasaya Giren Nakit', '₺' . number_format($cashInToday, 2, ',', '.')],
            ['Bugün Kasadan Çıkan Nakit', '₺' . number_format($cashOutToday, 2, ',', '.')],
            ['Bugünkü Net Kasa Akışı', '₺' . number_format($netCashToday, 2, ',', '.')],
            ['Mevcut Toplam Kasa Bakiyesi', '₺' . number_format($totalCashBalance, 2, ',', '.')],
        ];
        $this->table(['Gösterge', 'Değer'], $financeRows);

        // 3. HAREKETSİZ VE BAKİYELİ CARİLER
        // Son 30 gündür satış veya alış hareketi görmemiş ve bakiyesi sıfır olmayan cariler
        $idleDate = Carbon::now()->subDays(30);
        $idleContacts = Contact::where('balance', '!=', 0)
            ->whereDoesntHave('sales', fn($q) => $q->where('created_at', '>=', $idleDate))
            ->whereDoesntHave('purchases', fn($q) => $q->where('created_at', '>=', $idleDate))
            ->get();

        $this->newLine();
        $this->error("👥 3. BAKİYELİ VE HAREKETSİZ CARİLER (Son 30 Gün Hareketsiz: " . $idleContacts->count() . ")");

        if ($idleContacts->isEmpty()) {
            $this->line("   Açık bakiyeli olup hareketsiz kalan cari bulunmuyor.");
        } else {
            $contactRows = $idleContacts->map(function ($c) {
                return [
                    'ID' => $c->id,
                    'Cari Adı' => $c->name,
                    'Tür' => $c->type == 'customer' ? 'Müşteri' : ($c->type == 'supplier' ? 'Tedarikçi' : 'Her İkisi'),
                    'Bakiye' => '₺' . number_format($c->balance, 2, ',', '.'),
                    'Telefon' => $c->phone ?? '-',
                ];
            });
            $this->table(['ID', 'Cari Adı', 'Tür', 'Bakiye', 'Telefon'], $contactRows);
        }

        // 4. LOG DOSYASINA KAYIT (Headless background execution için)
        Log::info('DailyBusinessReport çalıştı:', [
            'tarih' => $today->toDateString(),
            'kritik_stok_adedi' => $criticalProducts->count(),
            'gunluk_satis_adedi' => $salesTodayCount,
            'gunluk_satis_cirosu' => (float)$salesTodayTotal,
            'gunluk_net_kasa' => (float)$netCashToday,
            'toplam_kasa_bakiye' => (float)$totalCashBalance,
            'hareketsiz_cari_adedi' => $idleContacts->count(),
        ]);

        $this->newLine();
        $this->info("✅ Rapor başarıyla üretildi ve storage/logs/laravel.log dosyasına kaydedildi.");

        return Command::SUCCESS;
    }
}
