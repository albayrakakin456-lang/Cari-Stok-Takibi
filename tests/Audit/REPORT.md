# Yerel 404 / 500 test raporu — 14 Eylül 2026

## Kapsam ve sonuç

İncelenen kopya: `C:\Users\albay\cari-takip`. Canlı cPanel sunucusu ve canlı veritabanı test edilmedi. Uygulama kodu değiştirilmedi. `tests/Audit/` altında tekrar çalıştırılabilir testler ve sonuçlar eklendi.

Test ortamı: PHP 8.2.12, PHPUnit 11.5.56, SQLite `:memory:`, array session/cache/mail, sahte kuyruk. Mevcut iş kayıtlarına erişilmedi. Laravel test istekleri tarayıcı çalıştırmaz; JavaScript, gerçek CSRF/cookie davranışı, harici CSS/JS servisleri ve sunucu rewrite ayarları bu sonuçlarla doğrulanmış sayılmaz. SQLite sonuçları üretimdeki MySQL sınırlarını garanti etmez.

- PHP sözdizimi: 56 dosya kontrol edildi; 55 geçti, 1 başarısız.
- Mevcut `AuthTest|ExampleTest`: 8 test, 61 doğrulama, hepsi geçti.
- Hatalı trait'e bağımlı olmayan audit grubu: 22 test, 58 doğrulama; 3 geçti, 19 başarısız. Başarısız olan 19 kontrol HTTP 500 döndürdü.
- Tam mevcut test paketi ve tam audit paketi trait sözdizimi hatasında PHP fatal error ile durdu. Bunlar için tüm testler geçti denemez. İlk tam koşunun `results.xml` dosyası tamamlanmış rapor olarak kullanılmamalıdır.

## P0 — Ortak model trait'inde PHP sözdizimi hatası

Dosya: `app/Traits/BelongsToUser.php:25`

```php
$model->user_id = auth()->id();f
```

Satır sonundaki `f`, PHP'nin sonraki `}` karakterinde parse error vermesine neden oluyor. `php -l app/Traits/BelongsToUser.php` başarısız oldu. Tam test paketi Contact modelini yüklerken fatal error ile durdu. Dashboard, cari, ürün, satış, alış ve kasa modelleri bu trait'i kullanıyor. Aynı dosya canlıya yüklenmişse bu hata ilgili istekleri de engeller; canlı kopyanın aynı olduğu doğrulanmadı.

Gerekli düzeltme: fazladan `f` kaldırılmalı, ardından sözdizimi ve tam testler yeniden çalıştırılmalı.

## P1 — Controller metodu olmayan route'lar HTTP 500 veriyor

Kaynak: `routes/web.php:38`–`42`, sınırsız `Route::resource(...)` tanımları. Loglarda `Call to undefined method ...Controller::edit/update/destroy/show()` doğrulandı. Aşağıdaki istekler giriş yapmış test kullanıcısıyla gönderildi; iş kaydı bulunup bulunmamasından önce eksik controller metodu nedeniyle çöküyorlar.

| Kaynak | HTTP 500 döndüren istekler | Adet |
| --- | --- | ---: |
| contacts | GET `/contacts/1/edit`, PUT/PATCH/DELETE `/contacts/1` | 4 |
| products | GET `/products/1/edit`, PUT/PATCH/DELETE `/products/1` | 4 |
| sales | GET `/sales/1/edit`, PUT/PATCH `/sales/1` | 3 |
| purchases | GET `/purchases/1/edit`, PUT/PATCH `/purchases/1` | 3 |
| cash | GET `/cash/1/edit`, GET/PUT/PATCH/DELETE `/cash/1` | 5 |

Bunlar 19 farklı HTTP yöntemi/adres eşleşmesidir, 19 farklı ekran değildir. İncelenen Blade menülerinde bu eksik işlemlere bağlantı bulunmadı; doğrudan URL veya HTTP isteğiyle erişilebiliyorlar.

Gerekli düzeltme: resource route'ları mevcut işlemlerle `only(...)` kullanılarak sınırlandırılmalı veya gerçekten istenen eksik özellikler uygulanmalı. Desteklenmeyen bir URL'nin 404/405 dönmesi normaldir; 500 dönmesi uygulama hatasıdır.

## Bağımsız geçen ek kontroller

- Giriş/kayıt sayfaları, misafir yönlendirmeleri, giriş yapmış kişinin giriş/kayıt sayfalarından dashboard'a yönlenmesi, logout ve logout sonrası erişim engeli.
- Cari, ürün, satış, alış ve kasa formlarına boş veri gönderimi: form hata mesajlarıyla geri yönlendirildi, 500 oluşmadı.
- 100 karakterli şifreyle kayıt isteği yerel PHP ortamında 500 üretmedi. Bu kontrol şifrenin tüm güvenlik özelliklerini değerlendirmez.

## Sözdizimi hatası giderildikten sonra doğrulanacaklar

Audit dosyasında aşağıdaki testler hazırlandı fakat trait hatası nedeniyle çalıştırılıp sonuçlandırılamadı:

- Boş ve dolu hesapta TR/EN dashboard, liste, ekleme ve detay ekranları; oluşturulan HTML içindeki dahili bağlantılar.
- Dil değişiminin geçerli sayfaya dönmesi.
- Başkasına ait ve bulunamayan kayıtların uygun 404 yanıtları.
- Satış formunda dizi uzunlukları eşleşmeyen miktarlar ve iç içe ürün ID'leri. `SaleController.php:66` miktar dizisine kontrolsüz erişiyor; bu erişim try/catch dışında. Çalıştırılarak doğrulanması gereken 500 riski.
- Aynı ürünün birden fazla satırda toplam stoktan fazla satılması. Stok kontrolü satırları topluca değerlendirmiyor; negatif stok riski.
- Satış iptalinde stok ve kasa dengesi.
- Peşin alış iptalinde kasa iadesi. `PurchaseController::destroy()` mevcut kodda stok çıkışı yapıp faturayı siliyor, kasa giriş kaydı oluşturmuyor.
- Alınan ürünler satıldıktan sonra alış iptali. Mevcut kod stok yeterliliği denetlemeden azaltma yapıyor.
- Cari liste bakiyesi ile cari detay bakiyesinin tutarlılığı. Liste saklanan `balance` alanını, detay ise hesaplanan bakiyeyi kullanıyor.

Bu son maddeler kod incelemesi bulgularıdır; tamamlanmış test sonuçlarıyla karıştırılmamalıdır.

## Yeniden çalıştırma

PowerShell'de önce test ortamını sabitleyin:

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:DB_URL=''
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='array'
$env:MAIL_MAILER='array'
$env:QUEUE_CONNECTION='sync'
```

Tam mevcut test paketi:

```powershell
php vendor/phpunit/phpunit/phpunit --no-progress
```

Ek audit paketi (varsayılan test grubuna eklenmedi, açıkça çalıştırılır):

```powershell
php vendor/phpunit/phpunit/phpunit tests/Audit/ReleaseReadinessTest.php --no-progress
```

Tamamlanmış sonuç dosyaları: `auth-results.xml` ve `independent-results.xml`.
