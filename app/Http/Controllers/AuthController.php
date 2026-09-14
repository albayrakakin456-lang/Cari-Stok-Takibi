<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Giriş Yapma Formunu Göster
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
      * Giriş İsteğini Doğrula ve Oturumu Başlat (Rate Limit Korumalı)
     */
    public function login(Request $request)
    {
        // 1. Form doğrulaması
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // E-posta ve IP adresini birleştirerek benzersiz bir anahtar (key) oluştur
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // 2. Rate Limit (Brute Force Koruması): 1 dakikada en fazla 5 hatalı deneme
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Çok fazla hatalı giriş denemesi yapıldı. Lütfen {$seconds} saniye sonra tekrar deneyin.",
            ])->onlyInput('email');
        }

        // 3. Auth::attempt ile e-posta ve şifre kontrolü
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Başarılı girişte sayaç sıfırlanır
            RateLimiter::clear($throttleKey);

            // Oturum sabitleme (Session Fixation) saldırılarını engellemek için session ID'sini yeniliyoruz
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))->with('success', 'Başarıyla giriş yaptınız.');
        }

        // 4. Hatalı girişte sayacı 1 artır (60 saniyelik zaman aşımı)
        RateLimiter::hit($throttleKey, 60);

        $remaining = RateLimiter::remaining($throttleKey, 5);

        return back()->withErrors([
            'email' => "Girdiğiniz e-posta veya şifre hatalı. (Kalan deneme hakkınız: {$remaining})",
        ])->onlyInput('email');
    }

    /**
     * Kayıt Olma Formunu Göster
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Yeni Kullanıcı Kaydet ve Oturumunu Aç
     */
    public function register(Request $request)
    {
        // Bot saldırılarına ve sahte kayıt spamlarına karşı Rate Limiting (IP başına dakikada maks 5 istek)
        $throttleKey = 'register|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Kısa sürede çok fazla kayıt denemesi yapıldı. Lütfen {$seconds} saniye sonra tekrar deneyin.",
            ])->withInput();
        }
        RateLimiter::hit($throttleKey, 60);

        // 1. Doğrulama (confirmed kuralı password_confirmation alanıyla eşleşmeyi şart koşar)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        // 2. Kullanıcıyı oluştur (User modelindeki 'password' => 'hashed' cast'i şifreyi otomatik Bcrypt ile şifreler)
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $validatedData['password'],
        ]);

        // 3. Kayıt olan kullanıcıyı anında sisteme giriş yapmış say
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Hesabınız başarıyla oluşturuldu ve giriş yapıldı.');
    }

    /**
     * Güvenli Çıkış Yap (Oturumu Kapat)
     */
    public function logout(Request $request)
    {
        // 1. Kullanıcının kimlik oturumunu sonlandır
        Auth::logout();

        // 2. Session verilerini temizle ve token'ı geçersiz kıl
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Başarıyla çıkış yaptınız.');
    }
}

