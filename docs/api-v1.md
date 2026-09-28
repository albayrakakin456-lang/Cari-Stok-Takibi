# Cari Takip API v1

Hazır Postman dosyaları `docs/postman` klasöründedir. Bunlar yalnızca isteğe bağlı
test araçlarıdır; entegrasyon için dosya içe aktarmak gerekmez. Postman kullanılırsa
panel yöneticisinin verdiği token `api_token` alanına girilir.

Webhook kurulumu, imza doğrulaması ve teslimat davranışı için
`docs/webhooks-v1.md` dosyasına bakın.

## Base URL

Yerel geliştirme:

```text
http://127.0.0.1:8000/api/v1
```

Production adresi, yayın ve doğrulama tamamlandıktan sonra ayrıca bildirilir.

## Kimlik doğrulama

### Token temini

API tokenı yalnızca yetkili yönetici tarafından web panelindeki **API Tokenları**
ekranından ilgili kullanıcı adına oluşturulur. Normal kullanıcılar ve dış servisler
e-posta ve şifreyle token oluşturamaz. Açık
token değeri yalnızca oluşturulduğu anda gösterilir ve güvenli kanaldan iletilir.

Token sahibini kontrol etmek ve kullanılan tokenı iptal etmek için:

```http
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

`logout` yalnızca o istekte kullanılan tokenı iptal eder; kullanıcının diğer
cihaz veya entegrasyon tokenlarına dokunmaz.

### Bearer token kullanımı

Tüm istekler Sanctum Bearer Token gerektirir:

```http
Accept: application/json
Authorization: Bearer <token>
```

JSON gövdeli isteklerde ayrıca:

```http
Content-Type: application/json
```

Token yetenekleri:

- `api:read`: Ürün, cari ve faturaları okur.
- `api:write`: Fatura oluşturur, numarasını değiştirir ve iptal eder.

Tokenların varsayılan ömrü 30 gündür. Token kaynak koda, URL'ye veya paylaşılan
Postman Collection dosyasına yazılmamalıdır.

Gerekirse sistem yöneticisi terminalden de token üretebilir:

```bash
php artisan api:issue-token kullanici@example.com --name=postman
```

Yalnızca listeleme ve görüntüleme yapabilen bir token için:

```bash
php artisan api:issue-token kullanici@example.com --name=postman-read-only --read-only
```

Komutun gösterdiği token yalnızca üretildiği anda görülebilir. Postman'de gizli
bir environment değişkeni olarak `api_token` adıyla saklanmalıdır. `api:read`
yetkili bir token yazma endpoint'ine gönderilirse `403 Forbidden` döner.

## Rate limit

Kimliği doğrulanmış istemci başına dakikada 60 istek kabul edilir. Limit aşılırsa
`429 Too Many Requests` döner.

## Endpoint'ler

| Metot | Adres | Yetki | İşlem |
|---|---|---|---|
| GET | `/products` | `api:read` | Ürünleri listeler |
| POST | `/products` | `api:write` | Ürün ve açılış stoğu oluşturur |
| GET | `/contacts` | `api:read` | Carileri listeler |
| POST | `/contacts` | `api:write` | Cari oluşturur |
| GET | `/invoices` | `api:read` | Faturaları listeler |
| GET | `/invoices/{id}` | `api:read` | Fatura ayrıntısını getirir |
| POST | `/invoices` | `api:write` | Fatura oluşturur |
| PATCH | `/invoices/{id}` | `api:write` | Fatura numarasını değiştirir |
| DELETE | `/invoices/{id}` | `api:write` | Faturayı iptal eder |
| GET | `/webhook-endpoints` | `api:read` | Webhook adreslerini listeler |
| POST | `/webhook-endpoints` | `api:write` | Webhook adresi oluşturur |
| PATCH | `/webhook-endpoints/{id}` | `api:write` | Webhook adresini günceller |
| DELETE | `/webhook-endpoints/{id}` | `api:write` | Webhook adresini pasife alır |
| POST | `/webhook-endpoints/{id}/test` | `api:write` | Test webhookunu kuyruğa ekler |
| GET | `/webhook-deliveries` | `api:read` | Teslimat geçmişini listeler |

Liste endpoint'leri `page` ve `per_page` parametrelerini kabul eder. `per_page`
en fazla 100 olabilir.

## Cari oluşturma

```http
POST /api/v1/contacts
```

```json
{
    "name": "ABC Teknoloji",
    "type": "customer",
    "phone": "05321234567",
    "email": "info@abc.test",
    "address": "İstanbul"
}
```

Satış faturasında kullanılacak carinin `type` değeri `customer` olmalıdır.

## Ürün oluşturma

```http
POST /api/v1/products
```

```json
{
    "name": "Kablosuz Mouse",
    "code": "MOUSE-001",
    "barcode": "869000000001",
    "purchase_price": 300,
    "sale_price": 450,
    "tax_rate": 20,
    "min_stock": 5,
    "opening_stock": 50
}
```

`opening_stock` sıfırdan büyükse ürünle birlikte bir stok giriş hareketi de
oluşturulur. Ürün kodu aynı kullanıcı içinde benzersiz olmalıdır.

## Fatura oluşturma

```http
POST /api/v1/invoices
```

```json
{
    "external_reference": "ERP-SALE-84725",
    "contact_id": 1,
    "invoice_number": "ERP-2026-00001",
    "items": [
        {
            "product_id": 2,
            "quantity": 3
        }
    ]
}
```

`external_reference`, dış sistemdeki işlemi benzersiz tanımlamalıdır. Aynı istek
aynı referansla tekrar gönderilirse mevcut fatura `200 OK` ile döner ve
`meta.idempotent_replay` değeri `true` olur. Aynı referans farklı içerikle tekrar
kullanılırsa `409 Conflict` döner.

İlk başarılı oluşturma cevabı `201 Created` döner. Fiyat ve toplamlar istemciden
alınmaz; sunucu tarafından ürün kayıtlarından hesaplanır.

## Fatura numarasını güncelleme

```http
PATCH /api/v1/invoices/15
```

```json
{
    "invoice_number": "ERP-2026-00001-R1"
}
```

İptal edilmiş faturalar güncellenemez ve `409 Conflict` döner.

## Fatura iptali

```http
DELETE /api/v1/invoices/15
```

İptal stokları geri verir, stok giriş hareketi ve kasa çıkış hareketi oluşturur.
Fatura silinmez; `status=cancelled` ve `cancelled_at` alanlarıyla saklanır. Aynı
DELETE tekrar gönderilirse yeni ters hareket oluşturulmaz.

## Temel durum kodları

| Kod | Anlamı |
|---|---|
| 200 | Başarılı okuma, güncelleme veya tekrarlanan idempotent istek |
| 201 | Yeni fatura oluşturuldu |
| 401 | Token yok veya geçersiz |
| 403 | Token gerekli yeteneğe sahip değil |
| 404 | Kayıt yok veya kullanıcıya ait değil |
| 409 | İşlem mevcut durum ya da dış referansla çakışıyor |
| 422 | Gönderilen alanlar doğrulamadan geçmedi |
| 429 | Dakikalık istek sınırı aşıldı |
| 500 | Beklenmeyen sunucu hatası |

Production ortamında yalnızca HTTPS kullanılmalı ve hata cevaplarında uygulama
dosya yolları veya stack trace gösterilmemelidir.
