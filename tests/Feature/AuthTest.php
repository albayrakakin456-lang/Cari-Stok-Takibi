<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * RefreshDatabase:
     * Her test çalıştığında veritabanını sıfırdan kurar, test bittiğinde tertemiz yapar.
     * Böylece testler birbirini kirletmez ve gerçek veritabanındaki verilerine asla dokunmaz!
     */
    use RefreshDatabase;

    /**
     * TEST 1: Giriş sayfası sorunsuz açılıyor mu?
     */
    public function test_login_screen_can_be_rendered(): void
    {
        // 1. Act (Eylem): /login adresine sanal bir GET isteği at
        $response = $this->get('/login');

        // 2. Assert (Doğrulama): Sayfa HTTP 200 (Başarılı) koduyla açıldı mı?
        $response->assertStatus(200);
        $response->assertSee('Giriş Yap');
    }

    /**
     * TEST 2: Doğru e-posta ve şifreyle giriş yapılabiliyor mu?
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        // 1. Arrange (Hazırlık): Test için hafızada geçici bir kullanıcı oluştur
        $user = User::create([
            'name' => 'Test Kullanıcısı',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. Act (Eylem): /login adresine form verilerini POST et
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 3. Assert (Doğrulama): 
        // - Ana sayfaya (dashboard) yönlendi mi?
        $response->assertRedirect(route('dashboard'));
        // - Kullanıcının oturumu başarıyla açıldı mı?
        $this->assertAuthenticatedAs($user);
    }

    /**
     * TEST 3: Yanlış şifre girildiğinde sistem girişi engelliyor mu?
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        // 1. Arrange (Hazırlık): Bir kullanıcı oluştur
        $user = User::create([
            'name' => 'Test Kullanıcısı',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. Act (Eylem): Yanlış şifre gönder
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'yanlis-sifre-999',
        ]);

        // 3. Assert (Doğrulama):
        // - Sayfa hata verip geri /login'e döndü mü?
        $response->assertRedirect('/login');
        // - Session'da 'email' hata mesajı oluştu mu?
        $response->assertSessionHasErrors('email');
        // - Kullanıcı hala misafir (giriş yapmamış) durumda mı?
        $this->assertGuest();
    }

    /**
     * TEST 4: 5 defadan fazla hatalı şifre denendiğinde Rate Limit devreye giriyor mu? (Brute-Force Koruması)
     */
    public function test_login_is_throttled_after_too_many_failed_attempts(): void
    {
        $user = User::create([
            'name' => 'Hedef Kullanıcı',
            'email' => 'victim@example.com',
            'password' => Hash::make('dogru-sifre'),
        ]);

        // 5 defa hatalı şifre dene
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->from('/login')->post('/login', [
                'email' => 'victim@example.com',
                'password' => 'hatali-sifre-' . $i,
            ]);
            $response->assertRedirect('/login');
            $response->assertSessionHasErrors('email');
        }

        // 6. deneme: Artık Rate Limit (Kaba kuvvet engeli) devreye girmeli
        $blockedResponse = $this->from('/login')->post('/login', [
            'email' => 'victim@example.com',
            'password' => 'hatali-sifre-6',
        ]);

        $blockedResponse->assertRedirect('/login');
        $blockedResponse->assertSessionHasErrors('email');
        $this->assertTrue(str_contains(session('errors')->first('email'), 'Çok fazla hatalı giriş denemesi yapıldı'));
    }

    /**
     * TEST 5: Kayıt olma (register) formuna bot saldırısı yapıldığında Rate Limit devreye giriyor mu?
     */
    public function test_register_is_throttled_after_too_many_attempts(): void
    {
        // 5 defa peş peşe kayıt denemesi yap
        for ($i = 1; $i <= 5; $i++) {
            $this->from('/register')->post('/register', [
                'name' => 'Kullanıcı ' . $i,
                'email' => 'user' . $i . '@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
            // Her seferinde oturumu kapat ki bir sonraki kayıt denenebilsin
            \Illuminate\Support\Facades\Auth::logout();
        }

        // 6. kayıt denemesi: Rate Limiter engeline takılmalı
        $blocked = $this->from('/register')->post('/register', [
            'name' => 'Spam Bot',
            'email' => 'spambot@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $blocked->assertRedirect('/register');
        $blocked->assertSessionHasErrors('email');
        $this->assertTrue(str_contains(session('errors')->first('email'), 'Kısa sürede çok fazla kayıt denemesi yapıldı'));
    }

    /**
     * TEST 6: Kayıt formunda tüm hata mesajları ve alan adları Türkçe mi? (örn. validation.min.string yerine Türkçe)
     */
    public function test_register_validation_messages_are_in_turkish(): void
    {
        // 1. Şifre 6 karakterden kısa girildiğinde 'validation.min.string' değil Türkçe mesaj gelmeli
        $response = $this->from('/register')->post('/register', [
            'name' => 'Akın Test',
            'email' => 'akintest@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
        
        $passwordError = session('errors')->first('password');
        $this->assertEquals('Şifre en az 6 karakter olmalıdır.', $passwordError);

        // 2. Şifre tekrarı uyuşmadığında
        $response2 = $this->from('/register')->post('/register', [
            'name' => 'Akın Test',
            'email' => 'akintest2@example.com',
            'password' => '123456',
            'password_confirmation' => '654321',
        ]);

        $response2->assertRedirect('/register');
        $response2->assertSessionHasErrors('password');
        $this->assertEquals('Şifre tekrarı eşleşmiyor.', session('errors')->first('password'));

        // 3. Zorunlu alanlar boş bırakıldığında
        $response3 = $this->from('/register')->post('/register', []);
        $response3->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertEquals('Ad Soyad alanı zorunludur.', session('errors')->first('name'));
        $this->assertEquals('E-posta Adresi alanı zorunludur.', session('errors')->first('email'));
    }
}

