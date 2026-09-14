<?php

namespace App\Http\Controllers;

use App\Models\Contact; // Modeli içeri aktardık
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function index()
    {
        // 1. Veritabanındaki tüm carileri en yeniden en eskiye sıralayarak çek. 
        // Bunu nesnelerden oluşan bir ArrayList gibi düşünebilirsin.
        $contacts = Contact::latest()->get();
        
        // 2. Çekilen bu veriyi 'contacts.index' isimli görünüm (Blade/HTML) dosyasına yolla.
        return view('contacts.index', compact('contacts'));
    }

    public function create()
    {
        // Sadece yeni kayıt ekleme formunu (HTML) ekrana bas.
        return view('contacts.create');
    }

    public function store(Request $request)
    {
        // 0. Rate Limiting (Kötüye Kullanım Koruması: Dakikada max 30 istek)
        $throttleKey = 'contact-store:' . (auth()->id() ?? $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput()->withErrors([    
                'rate_limit' => __('Too many requests. Please wait :seconds seconds before trying again.', ['seconds' => $seconds])
            ]);
        }
        RateLimiter::hit($throttleKey, 60);

        // 1. Gelen veriyi doğrula (Strictly numeric & regex tabanlı telefon kontrolü)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\s()\-]+$/'],
            'email' => 'nullable|email',
            'type' => 'required|in:customer,supplier',
            'address' => 'nullable|string',
            'note' => 'nullable|string'
        ], [
            'name.required' => __('Contact name is required.'),
            'phone.regex' => __('The phone number format is invalid. Letters are not allowed.'),
            'phone.min' => __('The phone number must be at least :min characters.'),
            'phone.max' => __('The phone number may not be greater than :max characters.'),
            'type.required' => __('Contact type is required.'),
            'type.in' => __('Selected contact type is invalid.'),
        ]);

        // 2. Doğrulanan veriyi kullanarak veritabanına yeni kaydı ekle (SQL INSERT işlemi).
        // Hatırlarsan Model'deki $fillable dizisi sayesinde bu işlem tamamen güvenli.
        Contact::create($validatedData);

        // 3. İşlem bitince kullanıcıyı liste sayfasına yönlendir ve geçici bir başarı mesajı iliştir.
        return redirect()->route('contacts.index')->with('success', __('Cari başarıyla eklendi.'));
    }

    /**
     * Cari Ekstresi ve Detay Sayfası
     */
    public function show(string $id)
    {
        // 1. Eager Loading: Müşteriyi/Tedarikçiyi, satışlarını, alışlarını ve kasa hareketlerini tek seferde çekiyoruz
        $contact = Contact::with(['sales', 'purchases', 'cashTransactions'])->findOrFail($id);

        if ($contact->type == 'customer') {
            // MÜŞTERİ HESABI: Satışlar (Müşterinin Borcu) - Tahsilatlar (Müşterinin Ödemesi)
            $totalInvoices = $contact->sales->sum('total_amount');
            $totalPaid = $contact->cashTransactions->where('type', 'in')->sum('amount');
            $totalRefunded = $contact->cashTransactions->where('type', 'out')->sum('amount');
            $netPaid = $totalPaid - $totalRefunded;
            $balance = $totalInvoices - $netPaid; // Müşterinin bize kalan borcu
        } else {
            // TEDARİKÇİ HESABI: Alışlar (Bizim Tedarikçiye Borcumuz) - Ödemeler (Tedarikçiye Yaptığımız Ödeme)
            $totalInvoices = $contact->purchases->sum('total_amount');
            $totalPaid = $contact->cashTransactions->where('type', 'out')->sum('amount');
            $totalRefunded = $contact->cashTransactions->where('type', 'in')->sum('amount');
            $netPaid = $totalPaid - $totalRefunded;
            $balance = $totalInvoices - $netPaid; // Bizim toptancıya kalan borcumuz
        }

        return view('contacts.show', compact('contact', 'totalInvoices', 'netPaid', 'balance'));
    }

    // edit, update, destroy metodlarına sonraki adımlarda döneceğiz.
}