<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST: Giriş yapmamış kullanıcı cariler sayfasına erişemez
     */
    public function test_guest_cannot_access_contacts(): void
    {
        $response = $this->get('/contacts');
        $response->assertRedirect('/login');
    }

    /**
     * TEST: Giriş yapmış kullanıcı yeni bir müşteri (cari) ekleyebilir
     */
    public function test_authenticated_user_can_create_customer(): void
    {
        // 1. Arrange: Bir kullanıcı oluştur
        $user = User::factory()->create();

        // 2. Act: Kullanıcı olarak oturum aç ve yeni müşteri oluştur
        $response = $this->actingAs($user)->post('/contacts', [
            'name' => 'Akın Albayrak',
            'phone' => '05551234567',
            'email' => 'akin@example.com',
            'type' => 'customer',
            'note' => 'Test müşterisi'
        ]);

        // 3. Assert: 
        // - Başarılı yönlendirme yapıldı mı?
        $response->assertRedirect(route('contacts.index'));
        $response->assertSessionHas('success');

        // - Veritabanına müşteri kaydedildi mi?
        $this->assertDatabaseHas('contacts', [
            'name' => 'Akın Albayrak',
            'email' => 'akin@example.com',
            'type' => 'customer',
            'balance' => 0
        ]);
    }

    /**
     * TEST: Telefon alanına harf veya geçersiz format girilirse validasyon hatası vermeli
     */
    public function test_contact_creation_fails_with_invalid_phone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/contacts', [
            'name' => 'Geçersiz Telefonlu Müşteri',
            'phone' => '0555-ABC-DEFG',
            'type' => 'customer',
        ]);

        $response->assertSessionHasErrors('phone');
    }
}

