# Dinamik indirim motoru

## Mimari

`DiscountEngine` sepeti hazırlar, aktif kampanyaları getirir ve her kampanyayı
`CampaignStrategyFactory` üzerinden kendi Strategy sınıfına gönderir. Controller
kampanya çeşidine göre `if/else` çalıştırmaz.

Akış sırası sistem tarafından belirlenir:

1. Ürün ve satır kampanyaları
2. Kategori/sepet eşik kampanyaları
3. Cari özel iskontosu

Panel kullanıcısı öncelik numarası girmez. Aynı aşamadaki kampanyalar oluşturulma
sırasına göre değerlendirilir. Bir satıra indirim uygulandığında
`campaign_applied` işaretlenir; başka bir satır kampanyası aynı satıra uygulanmaz.

## Koşullu kampanya

Koşullu kampanya `CampaignType::Conditional` türüdür. Ayarları `campaigns.parameters`
JSON alanında saklanır ve `ConditionalCampaignStrategy` tarafından çalıştırılır.

Her koşul şu alanlardan oluşur:

- `source_type`: `product` veya `category`
- `target_id`: seçilen ürün ya da kategori
- `minimum_quantity`: isteğe bağlı minimum adet
- `minimum_amount`: isteğe bağlı minimum TL tutarı

Bir koşulda adet ve tutar birlikte girilirse ikisinin de sağlanması gerekir.
Birden fazla kural eklenmişse kampanyanın çalışması için bütün kurallar sağlanmalıdır.

Ödül ayarları:

- `reward_type`: yüzde veya sabit tutar
- `reward_value`: uygulanacak indirim değeri
- `reward_scope`: koşulla eşleşen ürünler, belirli ürünler veya belirli kategoriler
- `repeat_reward`: eşik katlandığında ödülün de katlanıp katlanmayacağı

`repeat_reward` kapalıysa ödül bir kez uygulanır. Açıksa bütün koşulların ortak
karşıladığı en düşük kat sayısı kullanılır. Sabit tutar bu sayıyla çarpılır; yüzde
indirimi de artar ancak %100 sınırını geçemez.

Ödül için seçilen ürün veya kategoriler sepette bulunmuyorsa koşullar sağlansa bile
uygulanabilecek bir satır olmadığı için indirim oluşmaz. Bu davranış, örneğin
“A ürününü al, B kategorisindeki sepette bulunan ürünlere indirim kazan” gibi çapraz
kampanyaların kurulabilmesini sağlar.

Marka koşulu eklenmemiştir; mevcut `products` tablosunda marka ilişkisi yoktur.
İleride marka altyapısı eklenirse yeni bir koşul kaynağı olarak genişletilebilir.

## Para ve kayıt güvenliği

Hesap sırasında tutarlar kuruş cinsinden integer tutulur. Sonuç dışarı verilirken
TL'ye çevrilir. `SaleController`, motorun döndürdüğü kalemleri ve indirim dökümünü
transaction içinde `sales`, `sale_items` ve `sale_discounts` tablolarına kaydeder.
Stok gerçek adet kadar, kasa ise net `total_amount` kadar hareket görür.

Yeni kampanya türü eklemek için enum'a tür eklenir, `CampaignStrategy` sözleşmesini
uygulayan sınıf yazılır ve `CampaignStrategyFactory` eşlemesine eklenir.
