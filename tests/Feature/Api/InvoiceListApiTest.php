<?php

// Bu test sinifinin uygulamadaki adresini belirtiyoruz.

namespace Tests\Feature\Api;

// Testte fatura oluşturmak için Sale modelini içeri aktariyoruz.
use App\Models\Contact;
// Faturaya bağlanacak örnek müşteriyi oluşturmak için Contact modelini içeri aktariyoruz.
use App\Models\Sale;
// Test kullanicisi oluşturmak için User modelini içeri aktariyoruz.
use App\Models\User;
// Her testten önce veritabanini temizleyen yardımcı trait'i içeri aktariyoruz.
use Illuminate\Foundation\Testing\RefreshDatabase;
// API isteğini giriş yapmiş gibi göndermemizi sağlayan Sanctum sinifini içeri aktariyoruz.
use Laravel\Sanctum\Sanctum;
// Laravel'in temel test sinifini içeri aktariyoruz.
use Tests\TestCase;

// Fatura listeleme API'sinin davranışini kontrol eden test sinifini tanimliyoruz.
class InvoiceListApiTest extends TestCase
{
    // Her testte temiz bir veritabani kullanilmasini sağliyoruz.
    use RefreshDatabase;

    // Token olmadan gelen isteğin reddedildiğini test ediyoruz.
    public function test_guest_cannot_list_invoices(): void
    {
        // Token göndermeden endpoint'e GET isteği yapiyoruz ve 401 bekliyoruz.
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
    }

    // Giriş yapan kullanicinin fatura listesini alabildiğini test ediyoruz.
    public function test_authenticated_user_can_list_own_invoices(): void
    {
        // Veritabaninda bir test kullanicisi oluşturuyoruz.
        $user = User::factory()->create();
        // Sanctum'a sonraki isteği bu kullanici yapmiş gibi davranmasini söylüyoruz.
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        // Faturanin bağlanacaği örnek müşteriyi veritabaninda oluşturuyoruz.
        $contact = Contact::query()->create([
            // Müşteriyi test kullanicisina bağliyoruz.
            'user_id' => $user->id,
            // Örnek müşterinin adini belirliyoruz.
            'name' => 'Test Müşterisi',
            // Kaydin bir müşteri olduğunu belirtiyoruz.
            'type' => 'customer',
        ]);

        // Test kullanicisina ait örnek bir fatura kaydi oluşturuyoruz.
        Sale::query()->create([
            // Faturayi test kullanicisina bağliyoruz.
            'user_id' => $user->id,
            // Faturayi biraz önce oluşturduğumuz müşteriye bağliyoruz.
            'contact_id' => $contact->id,
            // Benzersiz bir örnek fatura numarasi veriyoruz.
            'invoice_number' => 'FAT-TEST-1',
            // Örnek toplam tutari belirliyoruz.
            'total_amount' => 100,
        ]);

        // Endpoint'e istek gönderip cevap yapisini kontrol ediyoruz.
        $this->getJson('/api/v1/invoices')
            // HTTP durum kodunun 200 olduğunu doğruluyoruz.
            ->assertOk()
            // Beklediğimiz temel JSON alanlarini doğruluyoruz.
            ->assertJsonPath('success', true)
            // İlk faturanin numarasini doğruluyoruz.
            ->assertJsonPath('data.0.invoice_number', 'FAT-TEST-1');
    }
}
