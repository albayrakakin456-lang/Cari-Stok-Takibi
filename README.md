# Cari & Stok Takip Sistemi

Cari hesapları, ürünleri, stok hareketlerini, alış-satış faturalarını ve kasa işlemlerini tek panelden yönetmek için geliştirilmiş Laravel tabanlı web uygulaması. Proje ayrıca harici uygulamalar için yetkili REST API ve imzalı webhook desteği sunar.

## Canlı Bağlantılar

- [Uygulamayı aç](https://depo-takip.u02.dehasofteticaret.com/login)
- [API ve webhook entegrasyon dokümanı](https://depo-takip.u02.dehasofteticaret.com/integration/docs)

## Başlıca Özellikler

- Cari hesap yönetimi
- Ürün ve stok takibi
- Alış ve satış faturaları
- Stok ve kasa hareketleri
- PDF fatura çıktısı
- Kullanıcı bazlı veri izolasyonu
- Türkçe ve İngilizce dil desteği
- Yetkilendirme, hız sınırlama ve güvenlik kontrolleri
- Otomatik testler

## API ve Webhook

API temel adresi:

```text
https://depo-takip.u02.dehasofteticaret.com/api/v1
```

- Kimlik doğrulama Laravel Sanctum Bearer token ile yapılır.
- Tokenlar yönetici panelinden oluşturulur ve `read` veya `write` yetkileriyle sınırlandırılabilir.
- Cari, ürün ve fatura işlemleri API üzerinden gerçekleştirilebilir.
- Kullanıcılar kendi webhook adreslerini API üzerinden kaydedebilir.
- `invoice.created` ve `invoice.cancelled` olayları kuyruk üzerinden iletilir.
- Webhook istekleri HMAC-SHA256 ile imzalanır ve başarısız teslimatlar yeniden denenir.
- API isteklerinde kimlik doğrulama hataları yönlendirme yerine JSON `401 Unauthorized` cevabı döndürür.

Ayrıntılı kullanım için:

- [API v1 dokümanı](docs/api-v1.md)
- [Webhook v1 dokümanı](docs/webhooks-v1.md)
- [Yerel webhook testi](docs/local-webhook-test.md)
- [Postman koleksiyonu](docs/postman/Cari-Takip-API-v1.postman_collection.json)
- [Postman canlı ortamı](docs/postman/Production-Test.postman_environment.json)
- [Postman yerel ortamı](docs/postman/Local.postman_environment.json)

> API tokenı ve webhook secret değeri yalnızca oluşturulduklarında açık olarak gösterilir. Bu değerleri güvenli bir parola kasasında saklayın; kaynak koda veya Git deposuna eklemeyin.

## Güvenlik Özellikleri

- Kullanıcıya ait kayıtların diğer kullanıcılar tarafından erişilememesi
- API tokenlarında yetenek bazlı `read` / `write` kontrolü
- Token yönetiminin yalnızca yönetici hesaplarına açık olması
- Sunucu tarafında fiyat ve toplam hesaplama
- Kritik işlemlerde veritabanı transaction kullanımı
- Tekrarlanan istekleri önlemek için idempotency kontrolü
- API hız sınırlama
- Webhook adreslerinde SSRF koruması
- Webhook imza doğrulaması ve teslimat geçmişi

## Kullanılan Teknolojiler

- PHP 8.2+
- Laravel 12
- Laravel Sanctum 4
- Blade, Bootstrap 5 ve JavaScript
- Vite
- MySQL ve SQLite uyumlu veritabanı yapısı
- DomPDF
- PHPUnit

## Yerel Kurulum

```bash
git clone https://github.com/albayrakakin456-lang/Cari-Stok-Takibi.git
cd Cari-Stok-Takibi
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Windows PowerShell kullanıyorsanız `.env` dosyasını şu komutla oluşturabilirsiniz:

```powershell
Copy-Item .env.example .env
```

`.env` içindeki veritabanı bilgilerini ayarladıktan sonra:

```bash
php artisan migrate
npm run build
php artisan serve
```

Kuyruktaki bildirim ve webhook işlerini geliştirme ortamında çalıştırmak için ayrı bir terminal açın:

```bash
php artisan queue:work --queue=webhooks,default
```

## Yönetici Yetkisi Verme

Mevcut bir kullanıcıyı e-posta adresiyle yönetici yapmak için:

```bash
php artisan user:grant-admin kullanici@example.com
```

Bu komutu yalnızca gerçekten yönetici olması gereken hesaplar için kullanın. Yönetici hesapları API tokenı oluşturabilir ve yönetebilir.

## Testler

Tüm otomatik testleri çalıştırmak için:

```bash
php artisan test
```

Kod biçimini kontrol etmek veya düzeltmek için:

```bash
vendor/bin/pint --test
vendor/bin/pint
```

## Canlı Ortam Notları

Canlı ortamda en az aşağıdaki ayarlar kullanılmalıdır:

```dotenv
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=database
```

Standart dağıtım komutları:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Webhookların otomatik teslim edilmesi için sunucuda kalıcı bir queue worker veya düzenli çalışan cron görevi bulunmalıdır. `.env`, API tokenları, webhook secret değerleri ve çalışma zamanı cache dosyaları Git'e eklenmemelidir.

## Lisans

Bu proje eğitim ve portföy amacıyla geliştirilmiştir.
