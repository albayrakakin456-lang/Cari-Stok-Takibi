<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class CashTransactionController extends Controller
{
    /**
     * Kasa Defteri ve Dönemsel Raporlar
     */
    public function index()
    {
        // 1. Kasa hareketlerini ve varsa ilişkili cariyi tek seferde çekiyoruz (Eager Loading)
        $transactions = CashTransaction::with('contact')->latest()->get();

        // 2. Genel Toplam Kasa Bakiyesi (Tüm Zamanlar)
        $totalIn = CashTransaction::where('type', 'in')->sum('amount');
        $totalOut = CashTransaction::where('type', 'out')->sum('amount');
        $netBalance = $totalIn - $totalOut;

        // 3. Günlük Kasa Raporu (Bugün saat 00:00'dan şu ana kadar)
        $todayIn = CashTransaction::where('type', 'in')->whereDate('created_at', today())->sum('amount');
        $todayOut = CashTransaction::where('type', 'out')->whereDate('created_at', today())->sum('amount');
        $todayNet = $todayIn - $todayOut;

        // 4. Haftalık Kasa Raporu (Pazartesi'den Pazar'a)
        $weekIn = CashTransaction::where('type', 'in')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');
        $weekOut = CashTransaction::where('type', 'out')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');
        $weekNet = $weekIn - $weekOut;

        // 5. Aylık Kasa Raporu (Bu ayın 1'inden son gününe kadar)
        $monthIn = CashTransaction::where('type', 'in')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        $monthOut = CashTransaction::where('type', 'out')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        $monthNet = $monthIn - $monthOut;

        return view('cash.index', compact(
            'transactions',
            'netBalance', 'totalIn', 'totalOut',
            'todayIn', 'todayOut', 'todayNet',
            'weekIn', 'weekOut', 'weekNet',
            'monthIn', 'monthOut', 'monthNet'
        ));
    }

    /**
     * Yeni Gelir / Gider Ekleme Formu
     */
    public function create()
    {
        // Eğer hareket bir müşteri veya tedarikçiyle ilişkiliyse seçebilmek için carileri yolluyoruz
        $contacts = Contact::all();
        return view('cash.create', compact('contacts'));
    }

    /**
     * Yeni Hareketi Kaydet
     */
    public function store(Request $request)
    {
        // 0. Rate Limiting (Kötüye Kullanım Koruması: Dakikada max 30 kasa hareketi)
        $throttleKey = 'cash-store:' . (auth()->id() ?? $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput()->withErrors([
                'rate_limit' => __('Too many requests. Please wait :seconds seconds before trying again.', ['seconds' => $seconds])
            ]);
        }
        RateLimiter::hit($throttleKey, 60);

        // 1. Form doğrulaması (Kullanıcı izolasyonuyla)
        // Not: contact_id nullable'dır. Elektrik faturası gibi genel giderlerde cari seçilmesi zorunlu değildir!
        $validatedData = $request->validate([
            'contact_id' => [
                'nullable',
                Rule::exists('contacts', 'id')->where('user_id', auth()->id()),
            ],
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255'
        ]);

        // 2. Kasa hareketini kaydet
        CashTransaction::create($validatedData);

        // 3. Kasa defterine başarı mesajıyla dön
        return redirect()->route('cash.index')->with('success', __('Kasa hareketi başarıyla kaydedildi.'));
    }
}

