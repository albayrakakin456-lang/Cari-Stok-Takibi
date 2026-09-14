# Cari & Stok Takip Sistemi

Cari hesapları, ürünleri, stok hareketlerini, alışları, satışları ve kasa işlemlerini tek panel üzerinden yönetmek için geliştirilmiş Laravel tabanlı web uygulaması.

## Canlı Demo

Uygulamayı incelemek için:

**[Canlı uygulamayı aç](https://depo-takip.u02.dehasofteticaret.com/login)**

## Özellikler

- Cari hesap yönetimi
- Ürün ve stok takibi
- Alış ve satış işlemleri
- Stok hareketlerinin kaydı
- Kasa hareketleri
- Kullanıcı bazlı veri izolasyonu
- Türkçe ve İngilizce dil desteği
- Yetkilendirme, hız sınırlama ve güvenlik kontrolleri
- Otomatik testler

## Kullanılan Teknolojiler

- PHP / Laravel
- Blade
- Vite
- JavaScript
- Tailwind CSS
- SQLite / MySQL uyumlu veritabanı yapısı

## Yerel Kurulum

```bash
git clone https://github.com/albayrakakin456-lang/Cari-Stok-Takibi.git
cd Cari-Stok-Takibi
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Windows PowerShell kullanıyorsanız `.env` dosyasını şu komutla oluşturabilirsiniz:

```powershell
Copy-Item .env.example .env
```

## Testler

```bash
php artisan test
```

## Lisans

Bu proje eğitim ve portföy amacıyla geliştirilmiştir.
