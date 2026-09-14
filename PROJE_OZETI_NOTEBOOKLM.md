# Laravel ile Cari, Kasa ve Stok Takip Sistemi - Kapsamlı Mimari ve Kod Analiz Kılavuzu

Bu belge, bir yazılım geliştiricinin Laravel framework'ü üzerinde inşa ettiği **Cari, Kasa ve Stok Takip Sistemi**'nin veri akışını, veritabanı kararlarını, Controller iş mantıklarını ve yazılım mimarisini en ince ayrıntısına kadar açıklamak amacıyla hazırlanmıştır. 

NotebookLM veya benzeri yapay zeka çalışma ortamlarına aktarıldığında; projenin "Neden bu kod yazıldı?", "Arka planda SQL motorunda ne oldu?", "Yazılım mühendisliği açısından hangi problemler çözüldü?" sorularına derinlemesine yanıt üretebilecek detaydadır.

---

## 1. Sistemin Genel Amacı ve Büyük Resim

Geleneksel eğitim projelerinde genellikle basit CRUD (Ekle, Oku, Güncelle, Sil) işlemleri yapılır. Ancak gerçek hayatta bir ticari ön muhasebe yazılımında **hiçbir işlem tek başına var olamaz**.

* Bir **Satış Faturası** kesildiğinde:
  1. Fatura oluşur (`sales`).
  2. Faturadaki ürünler tek tek kaydedilir (`sale_items`).
  3. Ürünün ana tablodaki stoğu eksilir (`products.stock`).
  4. Sistemin kara kutusuna stok çıkış logu düşer (`stock_movements`).
  5. Müşterinin cari kartına borç yazılır (`contacts`).
  6. Para peşin alındıysa şirket kasasına nakit girişi işlenir (`cash_transactions`).

Bu 6 farklı adımın aynı milisaniyede, bir bütün halinde ve sıfır veri kaybıyla gerçekleşmesi gerekir. İşte bu proje, bu karmaşık ilişkisel veri ağını Laravel MVC, Eloquent ORM ve Database Transactions ile yönetmeyi öğretir.

---

## 2. Veritabanı Mimarisi ve Foreign Key (Yabancı Anahtar) Mühendisliği

### 2.1. "Foreign Key Constraint is Incorrectly Formed" Hatasının Sebebi
Projenin başında tablolar oluşturulurken alınan bu meşhur hata, SQL motorunun (MySQL InnoDB) **Referans Bütünlüğü (Referential Integrity)** kuralından kaynaklanır.

* **Sorun Neydi?**
  Bir tabloda `$table->foreignId('contact_id')->constrained('contacts');` dendiğinde SQL derleyicisi fiziksel veri kataloğuna (Data Dictionary) bakar. Eğer `contacts` tablosu henüz bellekte/diskte oluşturulmamışsa, SQL motoru var olmayan bir hedefe ok çıkaramaz ve işlemi durdurur. Laravel migration dosyalarını isimlerindeki zaman damgasına göre sırayla çalıştırdığı için, çocuk tablo ana tablodan önce çalışırsa bu hata kaçınılmazdır.

* **Nasıl Çözüldü? (Hiyerarşik Sıralama)**
  Tablolar bağımlılık sırasına göre ikiye ayrıldı:
  1. **Kök (Ebeveyn / Bağımsız) Tablolar:** `contacts` (Cariler) ve `products` (Ürünler). Hiçbir tabloya bağımlı değillerdir, ilk önce bunlar derlenir.
  2. **Dallanmış (Çocuk / Bağımlı) Tablolar:** `sales`, `sale_items`, `purchases`, `purchase_items`, `stock_movements`, `cash_transactions`. Bunlar kök tablolar oluştuktan sonra sırayla derlenir.

* **Silme (`down`) Sırasının Tersliği:**
  Tablolar silinirken (`down()` metodunda) tam tersi sıra işletilmek zorundadır: Önce çocuklar silinir (`purchase_items`, `sale_items` vb.), en son ebeveynler (`products`, `contacts`) silinir. Çünkü çocuğu olan bir ebeveyn tabloyu SQL silmeye izin vermez.

---

## 3. Satış İşlemi (`SaleController`) ve Transaction Mantığı

### 3.1. `DB::beginTransaction()` Neden Hayatidir?
Veritabanları varsayılan olarak **Autocommit = ON** modunda çalışır. Yani gönderilen her SQL sorgusu anında diske kalıcı yazılır.

Fatura keserken 5. adımda bir hata çıkarsa ne olur?
Fatura kesilmiş, ürün stoktan düşmüş, fakat kasaya para girmemiş olur! Sistemde veri tutarsızlığı (Data Inconsistency) oluşur.

`DB::beginTransaction()` çağrıldığı an:
* SQL motoru o bağlantıya özel izole bir çalışma alanı (Undo Log) açar.
* Tüm `INSERT` ve `UPDATE` işlemleri dış dünyaya kapalı şekilde hafızada bekletilir.
* Eğer her şey hatasız biterse `DB::commit()` çalışır ve tüm adımlar tek bir mikro saniyede kalıcı hale gelir (ACID Atomicity kuralı).
* Bir hata fırlatılırsa `catch` bloğu `DB::rollBack()` çağırır; hafızadaki tüm adımlar hiç yaşanmamış gibi geri alınır.

### 3.2. Satış İşlemindeki Kodların Satır Satır Mantığı
1. **Fatura Başlığı:** `Sale::create(...)` ile önce fatura numarası ve müşteri kimliğiyle satır açılır. Toplam tutar henüz bilinmediği için `0` verilir. Bu işlem faturaya birincil anahtar (`id`) kazandırır.
2. **Sepet Döngüsü:** Formdan gelen dizi (Array) taranır. 
   * `SaleItem::create(...)` ile ürünün faturadaki adedi ve satıldığı andaki birim fiyatı dondurulur. (Birim fiyatın buraya kopyalanması kritiktir; yarın ürünün ana fiyatı değişse bile geçmişteki fatura bozulmaz).
   * `Product::decrement('stock', $quantity)` ile ürünün mevcut stoğu eksiltilir.
   * `StockMovement::create(...)` ile stok tablosuna `type = 'out'` (çıkış) hareketi loglanır.
3. **Fatura Güncelleme:** Kalemlerin toplamı hesaplanıp `Sale::update(['total_amount' => ...])` ile faturanın gerçek tutarı yazılır.
4. **Kasa Hareketi:** `CashTransaction::create(...)` ile müşteriden tahsilat yapıldığı için kasaya `type = 'in'` (giriş) hareketi yazılır.

### 3.3. Fatura Detayı ve "N+1 Sorgu Problemi"
`SaleController::show` içinde şu kod kullanılmıştır:
```php
$sale = Sale::with(['contact', 'items.product'])->findOrFail($id);
```
* **N+1 Problemi Nedir?**
  Eğer faturayı `Sale::findOrFail($id)` diye çekip Blade şablonunda döngüyle kalemlerdeki ürün isimlerine `$item->product->name` diye erişseydik; Laravel faturadaki 10 kalem için veritabanına 10 ayrı SQL sorgusu gönderirdi.
* **Eager Loading (`with`) Çözümü:**
  `with(['contact', 'items.product'])` ifadesi iç içe ilişki yüklemesidir. Laravel tek bir optimize sorguyla faturayı, faturanın müşterisini, fatura kalemlerini ve o kalemlerin bağlı olduğu ürün bilgilerini belleğe paketleyerek getirir. Sorgu sayısı N+1'den 3'e düşer.

### 3.4. Satış İptali (`destroy`) ve "Ters Kayıt" Felsefesi
Muhasebe yazılımlarında bir faturayı doğrudan silmek işletmeyi batırabilir; çünkü silinen faturanın stoğu eksik kalır, parası kasada kalır.
`destroy()` metodunda:
* Kalemler taranır, satılan adet kadar ürün stoğu depoya geri iade edilir (`$product->increment('stock', $quantity)`).
* Stok hareketlerine `in` (giriş - iade) kaydı atılır.
* Kasaya tahsilat iadesi olarak `out` (çıkış) hareketi atılır.
* En son fatura nesnesi silinir (`$sale->delete()`).

---

## 4. Alış İşlemi (`PurchaseController`) - Simetrik Ayna Mimarisi

Alış modülü, satış modülünün işletme mantığı açısından tam tersi (simetriği) olarak kurgulanmıştır:

| Karşılaştırma | Satış Faturası (`Sale`) | Alış Faturası (`Purchase`) |
| :--- | :--- | :--- |
| **Muhatap Cari** | Müşteri (`customer`) | Tedarikçi / Toptancı (`supplier`) |
| **Stok Etkisi** | Depodan Çıkar (`decrement`) | Depoya Girer (`increment`) |
| **Stok Logu** | `type = 'out'` (Çıkış) | `type = 'in'` (Giriş) |
| **Kasa Etkisi** | Kasaya Para Girer (`in`) | Kasadan Para Çıkar (`out`) |
| **Ürün Maliyeti** | Değişmez | Son alış fiyatıyla güncellenir |

* **Maliyet Güncelleme Kodu:**  
  `$product->update(['purchase_price' => $unitPrice]);`  
  Toptancıdan mal aldıkça ürünün sistemdeki en güncel alış maliyeti otomatik olarak yenilenir.
* **Açık Hesap (Veresiye) vs Peşin Seçeneği:**  
  Alış formunda `is_paid` kontrolü vardır. Eğer toptancıya nakit ödeme yapıldıysa kasadan para düşülür (`CashTransaction::create(type: 'out')`). Eğer ödeme yapılmadıysa kasaya dokunulmaz; toptancının ekstresinde işletmenin toptancıya olan borcu artar.

---

## 5. Cari Ekstresi ve Hesap Kartı (`ContactController::show`)

Cari Ekstre, bir müşteri veya tedarikçinin işletmeyle olan tüm finansal tarihçesidir.

### 5.1. Çift Yönlü Bakiye Matematiği
Controller içinde cari tipine göre iki farklı muhasebe mantığı işletilir:

1. **Müşteri Hesabı (`customer`):**
   * **Müşterinin Borcu:** Kestiğimiz faturaların toplamı (`$contact->sales->sum('total_amount')`).
   * **Müşterinin Ödemesi:** Kasaya giren tahsilatlar (`$contact->cashTransactions->where('type', 'in')->sum('amount')`).
   * **Kalan Alacağımız:** $\text{Borç} - \text{Ödenen}$.
2. **Tedarikçi Hesabı (`supplier`):**
   * **Bizim Toptancıya Borcumuz:** Aldığımız alış faturalarının toplamı (`$contact->purchases->sum('total_amount')`).
   * **Toptancıya Yaptığımız Ödeme:** Kasadan toptancı adına çıkan ödemeler (`$contact->cashTransactions->where('type', 'out')->sum('amount')`).
   * **Kalan Borcumuz:** $\text{Alışlar} - \text{Ödenen}$.

### 5.2. Bellekte Çalışan Collection Fonksiyonları
`$contact->sales->sum('total_amount')` ifadesinde `sales()` (parantezli) değil `sales` (parantezsiz) kullanılmıştır.
* **Farkı:** Parantezli kullanım veritabanına yeni bir `SELECT SUM(...)` sorgusu atar. Parantezsiz kullanım ise `with()` ile RAM'e zaten yüklenmiş olan Laravel Collection dizisi üzerinde PHP seviyesinde döngü kurar. Veritabanı gereksiz yere meşgul edilmez.

---

## 6. Kasa Yönetimi ve Carbon Zaman Analitiği (`CashTransactionController`)

### 6.1. `contact_id` Alanının `nullable` Olma Nedeni
`cash_transactions` tablosundaki `contact_id` alanı opsiyoneldir (`nullable`).
* **Nedeni:** İşletmenin yaptığı her harcama bir cariye (müşteriye/tedarikçiye) ait değildir. Örneğin "BEDAŞ Elektrik Faturası - 2.500 TL" genel bir giderdir. Bu harcamada `contact_id = NULL` olarak kaydedilir. Ancak "Ahmet Yılmaz elden tahsilat" işleminde `contact_id = 1` seçilerek hem kasa hem müşteri ekstresi senkronize edilir.
* **Validasyon Kuralı:** `'contact_id' => 'nullable|exists:contacts,id'`  
  Boş bırakılabilir, fakat eğer doluysa `contacts` tablosunda gerçekten var olan bir ID olmak zorundadır.

### 6.2. Carbon Kütüphanesi ile Zaman Filtreleme
* **Günlük:** `whereDate('created_at', today())`  
  Bugün saat 00:00:00'dan şu ana kadarki işlemleri süzer.
* **Haftalık:** `whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])`  
  Haftanın Pazartesi sabahı ile Pazar gecesi arasındaki zaman dilimini kilitler.
* **Aylık:** `whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)`  
  Sadece `whereMonth()` yazmak hatalıdır; çünkü gelecek yılların aynı ayındaki verileriyle çakışır. Hem yıl hem ay filtrelenerek sadece içinde bulunulan takvim ayı garanti edilir.

---

## 7. Ürün ve Stok Yönetimi (`ProductController`)

### 7.1. "Hiçbir Stok Hareketsiz Değişemez" Kuralı
Ürün oluşturulurken test veya sayım amaçlı başlangıç stoğu (örneğin 10 adet) girilirse, `ProductController::store` şu işlemi yapar:
```php
if ($product->stock > 0) {
    StockMovement::create([
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => $product->stock,
        'description' => 'Açılış / Sayım Stoğu Girişi'
    ]);
}
```
Böylece depoya giren 10 adet ürünün bile arkasında resmi bir hareket logu bulunur. Sistemde kaynağı belirsiz hiçbir fiziksel mal barınamaz.

### 7.2. Constrained Eager Loading (Koşullu İlişki Yükleme)
`ProductController::show` içinde stok hareketleri çekilirken:
```php
$product = Product::with(['stockMovements' => function ($query) {
    $query->latest();
}])->findOrFail($id);
```
Closure fonksiyonu kullanılarak stok hareketleri sorgusuna doğrudan `ORDER BY created_at DESC` kuralı enjekte edilmiştir. Böylece yüzlerce hareket arasından en güncel giriş ve çıkışlar listenin en tepesinde görünür.

---

## 8. Özet: Yazılım Mimarisi Cheat-Sheet (Önemli Çıkarımlar)
## 8. Kuyruk Sistemi (Queue - Asenkron İşlemler)

### 8.1. Web Sunucusunu Rahatlatma Felsefesi
Senkron (klasik) web mimarisinde kullanıcı "Kaydet" butonuna bastığında; fatura kesilir, e-posta gönderilir veya SMS atılır. E-posta sunucusunun cevap vermesi 3-5 saniye sürerse, kullanıcı beyaz bir sayfada bekler.
Queue (Kuyruk) mimarisinde ise:
1. Controller işlemi tamamlar ve bir Job (İş) paketini veritabanındaki `jobs` tablosuna bırakır (`dispatch`).
2. Kullanıcıya **anında (10-20 ms içinde)** "İşleminiz tamamlandı" cevabı döner.
3. Arka planda sürekli çalışan `php artisan queue:work` işçisi (daemon worker), kuyruktaki işleri teker teker çeker ve bağımsız bir PHP prosesi olarak çalıştırır.

### 8.2. Race Condition (Yarış Durumu) Tuzağı ve Çözümü
Queue işi tetiklenirken en büyük hata `SendSaleNotification::dispatch($sale)` satırını `DB::commit()` metodundan **önce** çağırmaktır.
Eğer işçi (worker) çok hızlıysa, fatura henüz diske yazılmadan kuyruktan işi alıp okumaya çalışır ve `ModelNotFoundException` fırlatır. Bu sebeple kuyruk tetiklemeleri **mutlaka `DB::commit()` satırından sonra** yapılmalıdır.

---

## 9. Zamanlanmış Görevler (Schedule - Cron Mimarisi)

### 9.1. Neden Sunucu Crontab'ına 50 Satır Yazılmaz?
Geleneksel Linux sistemlerinde her görev için crontab'a ayrı bir satır girilirdi (`0 0 * * * /path/to/script.sh`). Bu yöntem:
* Görevlerin versiyon kontrolüne (Git) girmesini engeller.
* Sunucu değiştiğinde tüm cron ayarlarının kaybolmasına yol açar.
* Hangi görevin ne zaman çalıştığını izlemeyi zorlaştırır.

Laravel Çözümü:
İşletim sistemi crontab'ına **tek bir satır** yazılır:
`* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`
Bu satır her dakika uyanır ve Laravel'in `routes/console.php` dosyasındaki takvimi denetler. Süresi gelen görevleri (`dailyAt('00:00')`, `hourly()` vb.) otomatik olarak çalıştırır.

### 9.2. `DailyBusinessReport` Komutu ve Veri Analizi
Geliştirilen `report:daily` komutu 3 kritik ticari veriyi analiz eder:
1. **Dinamik Eşik Kıyaslaması (`whereColumn`):**
   `Product::whereColumn('stock', '<=', 'min_stock')` sorgusu ile her ürünün stoğu kendi özel kritik eşiği ile dinamik kıyaslanır.
2. **Finansal Kasa ve Satış Mutabakatı:**
   Günün satış cirosu, kasaya giren/çıkan nakit akışı ve anlık toplam kasa bakiyesi toplanır.
3. **Atıl / Riskli Alacak Takibi (`whereDoesntHave`):**
   `balance != 0` olan ve son 30 gündür hiçbir satış ya da alış hareketi görmemiş cariler SQL `NOT EXISTS` alt sorgusuyla filtrelenir.
4. **Çakışma Önleme (`withoutOverlapping`):**
   Rapor çalışması uzun sürerse bir sonraki zaman diliminde aynı görevin ikinci kez başlatılıp sunucu belleğini kilitlemesini (Mutex lock) önler.

---

## 10. Özet: Yazılım Mimarisi Cheat-Sheet (Önemli Çıkarımlar)

1. **Bağımlılık Sıralaması:** Foreign key olan yapılarda her zaman önce bağımsız ana tablolar, sonra onlara bağlanan alt tablolar oluşturulur.
2. **Veri Bütünlüğü (Atomicity):** Birbirini tetikleyen 2 veya daha fazla tablonun etkilendiği her yerde `DB::beginTransaction()`, `DB::commit()` ve `DB::rollBack()` zorunludur.
3. **N+1 Sorgu Engeli:** İlişkili modeller Blade şablonuna gönderilmeden önce Controller katmanında `with()` ile eager loading yapılmalıdır.
4. **Çift Yönlü Modeller:** Ebeveyn tabloda `hasMany`, çocuk tabloda `belongsTo` tanımlanarak ilişkiler çift yönlü okunabilir hale getirilir.
5. **Denetim İzi (Audit Log):** Muhasebe ve stok sistemlerinde bakiye ya da stok sayısı tek başına bir kolon olarak değiştirilmez; her değişimin arkasında bir log tablosu (`stock_movements`, `cash_transactions`) bulunur.
6. **Asenkron Kuyruk Güvenliği:** Kuyruk işleri daima `DB::commit()` sonrasında fırlatılmalı, veritabanı kilitleri kuyruk süreçlerini etkilememelidir.
7. **Merkezi Zamanlama (Schedule):** Tüm periyodik işler işletim sistemi crontab'ı yerine kod tabanında (`routes/console.php`) tanımlanmalı ve Git ile versiyonlanmalıdır.


