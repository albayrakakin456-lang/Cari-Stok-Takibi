<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Product;
use App\Models\Sale;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Yönetici Gösterge Paneli (Dashboard)
     */
    public function index()
    {
        // 1. Genel Sayısal Metrikler (SQL COUNT)
        $totalContacts = Contact::count();
        $totalProducts = Product::count();

        // 2. Depodaki Toplam Malın Maliyet Değeri (Stok x Alış Fiyatı Toplamı)
        $totalStockValue = Product::all()->sum(function ($product) {
            return $product->stock * $product->purchase_price;
        });

        // 3. Kasa Toplam Mevcut Bakiyesi (Tüm Girenler - Tüm Çıkanlar)
        $totalCashIn = CashTransaction::where('type', 'in')->sum('amount');
        $totalCashOut = CashTransaction::where('type', 'out')->sum('amount');
        $cashBalance = $totalCashIn - $totalCashOut;

        // 4. Günlük Performans Metrikleri (Carbon today() ile)
        $todaySales = Sale::whereDate('created_at', today())->sum('total_amount');
        $todayCashIn = CashTransaction::where('type', 'in')->whereDate('created_at', today())->sum('amount');
        $todayCashOut = CashTransaction::where('type', 'out')->whereDate('created_at', today())->sum('amount');

        // 5. Kritik Stok Uyarısı: Stoğu, kendi minimum stok sınırına eşit veya daha az olan ürünler
        // whereColumn: İki SQL sütununu dinamik olarak birbiriyle kıyaslar
        $criticalProducts = Product::whereColumn('stock', '<=', 'min_stock')->get();

        // 6. Son Hareketler (Dashboard vitrini için son 5 fatura ve son 5 kasa hareketi)
        $recentSales = Sale::with('contact')->latest()->take(5)->get();
        $recentTransactions = CashTransaction::with('contact')->latest()->take(5)->get();

        return view('dashboard', compact(
            'totalContacts',
            'totalProducts',
            'totalStockValue',
            'cashBalance',
            'todaySales',
            'todayCashIn',
            'todayCashOut',
            'criticalProducts',
            'recentSales',
            'recentTransactions'
        ));
    }
}

