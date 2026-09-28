# Cari Takip Webhooks v1

## Genel akış

Cari Takip, satış faturası oluşturulduğunda veya iptal edildiğinde kullanıcının
aktif webhook adreslerine arka planda HTTP POST gönderir.

Desteklenen olaylar:

- `invoice.created`
- `invoice.cancelled`
- `webhook.test` (yalnızca bağlantı testi)

## Endpoint kaydetme

```http
POST /api/v1/webhook-endpoints
Authorization: Bearer <token>
Content-Type: application/json
```

```json
{
    "url": "https://example.com/webhooks/cari-takip",
    "events": ["invoice.created", "invoice.cancelled"]
}
```

Başarılı cevap `201 Created` döner. Cevaptaki `secret` yalnızca oluşturma
anında gösterilir ve imza doğrulaması için güvenli bir yerde saklanmalıdır.

## Payload

```json
{
    "id": "3efec29a-1dc9-4b46-b767-cba7d3631833",
    "type": "invoice.created",
    "api_version": "v1",
    "occurred_at": "2026-09-21T15:30:00Z",
    "data": {
        "invoice": {
            "id": 15,
            "invoice_number": "FAT-2026-001",
            "external_reference": "ERP-1001",
            "contact_id": 4,
            "total_amount": "1350.00",
            "status": "active",
            "created_at": "2026-09-21T15:30:00Z",
            "cancelled_at": null
        }
    }
}
```

## İmza doğrulaması

Her istekte aşağıdaki headerlar gönderilir:

```http
X-Webhook-Id: 3efec29a-1dc9-4b46-b767-cba7d3631833
X-Webhook-Timestamp: 1789983000
X-Webhook-Signature: v1=<hex-hmac>
```

İmzalanan metin:

```text
<timestamp>.<ham JSON body>
```

Beklenen imza:

```text
HMAC-SHA256(imzalanan metin, webhook secret)
```

Karşılaştırma zamanlama saldırılarına karşı sabit süreli bir fonksiyonla
yapılmalıdır (PHP'de `hash_equals`). Eski isteklerin yeniden oynatılmasını
önlemek için timestamp toleransı uygulanmalı ve işlenmiş `X-Webhook-Id`
değerleri saklanmalıdır.

## Teslimat ve tekrar deneme

- Herhangi bir `2xx` cevap başarılı kabul edilir.
- Timeout, bağlantı hatası, `408`, `425`, `429` ve `5xx` cevaplarda tekrar denenir.
- Diğer `4xx` cevaplar kalıcı hata kabul edilir.
- Denemeler yaklaşık 1 dakika, 5 dakika, 15 dakika ve 1 saat aralıklarla yapılır.
- Aynı olayın bütün tekrarlarında event ID değişmez.

Alıcı servis webhooku hızlıca doğrulayıp `200`, `202` veya `204` dönmeli; uzun
işlemleri kendi kuyruğunda arka planda gerçekleştirmelidir.

## Queue worker

Webhookların gönderilebilmesi için sunucuda sürekli çalışan worker gerekir:

```bash
php artisan queue:work --queue=webhooks,default --tries=5
```

Production ortamında bu komut Supervisor, systemd veya hosting sağlayıcısının
process manager özelliğiyle sürekli çalıştırılmalıdır.
