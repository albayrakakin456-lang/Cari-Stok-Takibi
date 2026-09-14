# Cari, Kasa ve Stok Takip Sistemi - Teknik Sunum ve Mülakat Rehberi

Bu belge; projeyi üstlerinize, teknik yöneticilerinize veya mülakatçılara sunarken karşılaşabileceğiniz tüm mimari soruları, kavram tanımlarını ve kod açıklamalarını eksiksiz olarak içermektedir.

---

## 📌 1. Projenin 1 Dakikalık Yönetici Özeti (Pitch)

> **Soru:** *"Bu proje nedir, tam olarak hangi problemi çözüyor?"*

**Senin Cevabın:**  
*"Bu proje, bir KOBİ veya ticaret işletmesinin günlük finansal, stok ve cari hareketlerini uçtan uca yöneten bir **Ön Muhasebe & ERP Web Uygulamasıdır**.*  
*Geleneksel basit form projelerinden farklı olarak; stoklar asla hareketsiz değişmez, satışlar **Database Transactions** ile atomik olarak korunur, fatura bildirimleri **Queue (Kuyruk)** mimarisiyle arka planda asenkron gönderilir, gece yarısı raporları **Laravel Scheduler** otopilotuyla otomatik çıkar ve tüm sistem **Otomatik Feature Testleri** ile korunur."*

---

## 🎯 2. En Çok Gelebilecek "Nasıl Yaptın?" Soruları ve Cevapları

### 1. "Kritik Stok Uyarı Mekanizmasını Nasıl Yaptın?"
> **Yönetici/Kıdemli Sorusu:** *"Dashboard'daki ve rapordaki kritik stok göstergesini nasıl kodladın? Veritabanı sorgusunu nasıl kurguladın?"*

**Senin Cevabın:**
*"Bunu yaparken sabit bir sayı ile (örneğin `where('stock', '<=', 10)`) filtreleme **yapmadım**. Çünkü işletmede vida satan reyon için 10 adet kritik bir eşikken, araba galerisi için 2 araba normal bir stoktur.*  
*Veritabanında her ürünün kendine ait bir `min_stock` (kritik eşik) kolonu bulunur.*  
*[`DashboardController.php`](file:///c:/Users/albay/cari-takip/app/Http/Controllers/DashboardController.php) içinde Eloquent'in **`whereColumn`** metodunu kullandım:*
```php
Product::whereColumn('stock', '<=', 'min_stock')->get();
```
*Bu sorgu SQL motoruna şunu iletir: `SELECT * FROM products WHERE stock <= min_stock;`  
Yani SQL her satırın mevcut stoğunu, o satırın kendi kritik eşiğiyle dinamik olarak kıyaslar. Böylece sıfır hatalı, her ürüne özel esnek bir uyarı tablosu elde edilir."*

---

### 2. "Satış İşleminde Veri Bütünlüğünü Nasıl Garanti Ettin?"
> **Yönetici/Kıdemli Sorusu:** *"Fatura keserken faturayı kaydettin, stoktan düştün, kasaya para ekledin. Tam o anda elektrik kesilirse veya sunucu hata verirse ne olur?"*

**Senin Cevabın:**
*"Sistemde **Database Transactions (`DB::beginTransaction`)** kullandım.*  
*Fatura başlığını açma, sepet döngüsünde stok eksiltme, stok hareket logu yazma ve kasaya nakit girişi adımlarını tek bir atomik paket (ACID Atomicity kuralı) haline getirdim:*
```php
DB::beginTransaction();
try {
    // 1. Fatura oluştur
    // 2. Stoğu düş & log yaz
    // 3. Kasaya para gir
    DB::commit(); // Hepsi başarılıysa tek milisaniyede veritabanına mühürle
} catch (\Exception $e) {
    DB::rollBack(); // En ufak bir aksilikte her şeyi hiç yaşanmamış gibi geri al
}
```
*Eğer 2. adımda elektrik kesilirse `rollBack()` çalışır; kasada para yokken depodan mal eksilmiş gibi yarım kalmış (inconsistent) veri asla oluşamaz. 'Ya hep ya hiç' kuralı geçerlidir."*

---

### 3. "Fatura İptal Edildiğinde Ne Oluyor? (Reversal Logic)"
> **Yönetici/Kıdemli Sorusu:** *"Kullanıcı kesilmiş bir faturayı iptal ederse sistemde ne gerçekleşir?"*

**Senin Cevabın:**
*"Fatura silinirken ters işlem (Reversal) işletilir:*  
*1. Faturadaki ürünlerin adetleri tespit edilir ve depoya geri iade edilir (`$product->increment('stock', $quantity)`).*  
*2. Kasadan müşteriye para iadesi olarak eksi hareket yazılır (`CashTransaction::create(['type' => 'out', ...])`).*  
*3. Fatura ancak bu denge sağlandıktan sonra güvenle silinir."*

---

### 4. "Kuyruk Sistemi (Queue - Job) Neden Gerekliydi?"
> **Yönetici/Kıdemli Sorusu:** *"Satış bildirimini neden doğrudan Controller içinde göndermedin de Job (`SendSaleNotification`) yazdın?"*

**Senin Cevabın:**
*"Kullanıcı deneyimini (UX) ve web sunucusunun yanıt hızını korumak için asenkron kuyruk kullandım.*  
*Controller içinde senkron mail/SMS göndermeye kalkarsak, harici sunucunun cevap vermesi 3-5 saniye sürebilir ve kullanıcı beyaz bir ekranda bekler.*  
*Ben faturayı kaydettiğim an `SendSaleNotification::dispatch($sale)` ile bu işi veritabanındaki `jobs` tablosuna bıraktım. Kullanıcıya **10 milisaniyede 'İşlem Başarılı'** cevabı döndü.*  
*Arka planda çalışan `php artisan queue:work` işçisi ise bu görevi sessizce ve kullanıcıyı bekletmeden icra etti.*  
*Ayrıca iş hata verirse pes etmesin diye `$tries = 3` ve `$backoff = 5` ile otomatik yeniden deneme kurguladım."*

---

### 5. "Job'ı Neden `DB::commit()`ten Sonra Çağırdın?" (Tuzak Mülakat Sorusu!)
> **Yönetici/Kıdemli Sorusu:** *"Neden `dispatch()` satırını `DB::commit()`ten önce yazmadın?"*

**Senin Cevabın:**
*"Bu, **Race Condition (Yarış Durumu)** hatasını engellemek içindir.*  
*`commit` çalışana kadar fatura veritabanına kalıcı yazılmamıştır. Eğer postacıya (Job) 'Faturayı al götür' emrini `commit`ten önce verirsek; çok hızlı çalışan kuyruk işçisi faturayı okumaya gider ve veritabanında bulamayarak `ModelNotFoundException` hatasıyla çöker.*  
*Kural: Önce veritabanına mühür basılır (`commit`), ardından postacı yola çıkarılır (`dispatch`)."*

---

### 6. "Zamanlanmış Görevler (Schedule) Kendi Kendine Nasıl Çalışıyor?"
> **Yönetici/Kıdemli Sorusu:** *"Gece saat 00:00'da çalışan raporu sunucuya nasıl bağladın?"*

**Senin Cevabın:**
*"Geleneksel yöntemde sunucu crontab'ına onlarca satır yazılırdı. Bu yöntem kod tabanından bağımsızdır ve sunucu değişince kaybolur.*  
*Ben Laravel'in **Single Crontab** mimarisini kullandım. Sunucu işletim sistemine tek bir görev verilir: 'Her dakika `php artisan schedule:run` komutunu çalıştır (Kalp Atışı)'.*  
*Laravel her dakika uyanır ve [`routes/console.php`](file:///c:/Users/albay/cari-takip/routes/console.php) dosyasına bakar. Saat 00:00 olduğunda `report:daily` komutunu ateşler.*  
*Ayrıca `withoutOverlapping()` kilidi koyarak, rapor uzun sürerse bir sonraki dakikada ikinci bir görevin başlayıp sunucu belleğini kilitlemesini (Mutex Lock) engelledim."*

---

### 7. "Sistemin Çalıştığını Nasıl Kanıtlıyorsun?"
> **Yönetici/Kıdemli Sorusu:** *"Yazdığın kodların sağlam olduğunu nereden biliyorsun?"*

**Senin Cevabın:**
*"Projemizde **Otomatik Feature Testleri** var (`AuthTest`, `ContactTest`, `ProductTest`, `SaleTest`).*  
*Tarayıcıyı bile açmadan terminalden `php artisan test` çalıştırıyoruz.*  
*Testler `RefreshDatabase` trait'i sayesinde RAM üzerinde (`sqlite :memory:`) çalışır. Gerçek müşteri veya ürün verilerimize 1 bayt bile dokunmaz.*  
*Yalnızca **0.78 saniyede 9 test ve 31 güvenlik kuralı** yeşil (`PASS`) yanar. Hatta testler sayesinde `ProductController` içindeki mükerrer kayıt hatasını canlıya çıkmadan anında yakalayıp düzelttik."*

---

#### 8. "Karanlık / Aydınlık Modu (Dark/Light Mode) Nasıl Kodladın?"
> **Yönetici/Kıdemli Sorusu:** *"Sistemde koyu mod var, bunu nasıl yaptın? Backend'e istek atıyor mu?"*

**Senin Cevabın:**
*"Hayır, backend'e veya veritabanına istek atıp sunucuya yük bindirmedim. **Bootstrap 5.3'ün yerel Color Modes (`data-bs-theme`)** özelliği ile tarayıcının **`localStorage` API**'sini entegre ettim.*  
*Kullanıcı navbar'daki Ay/Güneş butonuna bastığı anda JavaScript `<html>` etiketine `data-bs-theme="dark"` özniteliğini atar ve tercihi `localStorage`'a yazar.*  
*Ayrıca sayfa yenilendiğinde beyaz parlama (FOUC) olmaması için `<head>` içerisinde minik bir erken başlatıcı script ile sıfır milisaniye gecikmeyle kullanıcının son tercihi geri yüklenir."*

---

### 9. "Çoklu Dil Desteğini (i18n - TR / EN) Nasıl Kurguladın?"
> **Yönetici/Kıdemli Sorusu:** *"Sistemde İngilizce ve Türkçe dil desteği var. Veritabanını mı şişirdin, nasıl bir mimari kurdun?"*

**Senin Cevabın:**
*"Veritabanına hiç dokunmadım ve performanstan ödün vermedim. Laravel'in kurumsal **Localization (i18n)** motoru ve özel bir **Middleware** mimarisi kurdum:*
1. *Kullanıcı navbar'dan **🇹🇷 TR / 🇬🇧 EN** butonuna bastığında `/lang/{locale}` rotası tetiklenir ve seçilen dil **Session**'a kaydedilir.*
2. *Yazdığım `SetLocale` middleware'i gelen her web isteğinde araya girer; session'daki dili okur ve Laravel'e `App::setLocale($locale)` emrini verir.*
3. *Arayüzdeki tüm terimler `lang/tr.json` ve `lang/en.json` sözlük dosyalarından `{{ __('...') }}` fonksiyonuyla $O(1)$ karmaşıklığında okunur.*
4. *Üstelik bu mimari için `LocaleTest` adında otomatik feature testi yazdım; dil değişiminin doğruluğunu `php artisan test` ile garanti altına aldım."*

---

## 📖 3. "Bu Kelime Ne Anlama Geliyor?" Teknik Sözlük

| Kavram / Fonksiyon | Mülakatta Söylenecek 1 Cümlelik Tanım |
| :--- | :--- |
| **`Eager Loading` (`with()`)** | N+1 sorgu problemini engellemek için, ilişkili modelleri (örneğin faturanın müşterisini) döngü içinde tek tek sorgulamak yerine tek bir SQL sorgusuyla hafızaya yükleme tekniğidir. |
| **`Race Condition`** | İki farklı işlemin (örneğin veritabanı yazımı ile kuyruk okuyucusunun) zamanlama uyumsuzluğu yüzünden birbirinin önüne geçerek hata üretmesi durumudur. |
| **`ACID / Atomicity`** | Bir veritabanı işleminde yer alan adımların ya tamamen başarılı olmasını ya da en ufak hatada hiçbirinin gerçekleşmemiş sayılmasını sağlayan bölünemezlik kuralıdır. |
| **`Single Crontab Paradigması`**| Sunucuya sadece tek bir cron yazıp, tüm zamanlama kurallarını kod tabanında (`routes/console.php`) Git ile versiyonlama mimarisidir. |
| **`withoutOverlapping()`** | Zamanlanmış bir görev henüz bitmeden bir sonraki periyodun tetiklenmesini engelleyen Mutex dosya kilidi mekanizmasıdır. |
| **`Constrained Eager Loading`** | `with(['stockMovements' => fn($q) => $q->latest()])` örneğindeki gibi, ilişkili alt kayıtları çekerken doğrudan ilişki sorgusuna sıralama veya filtre ekleme yöntemidir. |
| **`session()->regenerate()`** | Oturum Sabitleme (Session Fixation) adlı siber saldırıyı engellemek amacıyla kullanıcı başarılı giriş yaptığı an tarayıcı oturum kimliğini yenileme işlemidir. |
| **`Master Layout` (`@extends`)** | DRY (Don't Repeat Yourself) prensibi gereğince menü, stil ve sayfa çatısını tek bir dosyada tutup diğer sayfalara kalıtım (inheritance) yoluyla aktarma yöntemidir. |
| **`data-bs-theme` & `localStorage`** | Bootstrap 5.3'ün CSS değişkenleriyle renk paletini tersyüz etmesi ve kullanıcının tema tercihini sunucuya gitmeden tarayıcı hafızasında saklama mimarisidir. |
| **`i18n & Localization`** | Uygulamanın kod mantığını değiştirmeden, Session + Middleware + JSON sözlükleri aracılığıyla birden fazla dilde hizmet verebilmesini sağlayan uluslararasılaştırma mimarisidir. |

---

## 🏆 4. Başarı Tablosu: Neler Tamamlandı?

1. ✅ **Cari Modülü:** Müşteri/Tedarikçi ayrımı, bakiye borç/alacak matematiği, ekstre ekranı.
2. ✅ **Ürün & Stok:** Dinamik maliyet, stok hareket geçmişi (`stock_movements`).
3. ✅ **Satış & Fatura:** DB Transactions, stok düşüşü, kasa nakit girişi, iade mekanizması.
4. ✅ **Alış & Tedarikçi:** Mal kabulü, depoya giriş, maliyet güncelleme.
5. ✅ **Kasa Defteri:** Gelir/gider takibi, genel işletme giderleri, Carbon dönemsel filtreleri.
6. ✅ **Dashboard:** Canlı KPI göstergeleri, dinamik kritik stok uyarıları (`whereColumn`).
7. ✅ **Güvenlik (Auth):** Custom MVC Auth, `guest` ve `auth` middleware'leri, Session yönetimi.
8. ✅ **Kuyruk (Queue):** `SendSaleNotification` Job'ı, `ShouldQueue`, retry & backoff.
9. ✅ **Zamanlayıcı (Schedule):** `report:daily`, otomatik gece raporu, cron entegrasyonu.
10. ✅ **Otomatik Testler:** PHPUnit test paketi, AAA deseni, 11 test / 37 assertion PASS (0.83s).
11. ✅ **Modern UI/UX & Dark Mode:** Master layout, Plus Jakarta Sans, Bootstrap Icons, Koyu/Açık Tema Desteği (`localStorage`).
12. ✅ **Çoklu Dil (i18n):** TR / EN dil desteği, SetLocale Middleware, JSON sözlükleri, Navbar dil seçici.
