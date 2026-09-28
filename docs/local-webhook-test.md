# Yerel webhook testi

Bu araç gerçek webhook alıcısının yerel bir taklididir. Yalnızca geliştirme ve
öğrenme amacıyla kullanılır.

## 1. Alıcıyı çalıştır

```powershell
php -S 127.0.0.1:8001 tools/local-webhook-receiver.php
```

Alıcı adresi: `http://127.0.0.1:8001/webhook`

Gelen olayları görüntüleme adresi: `http://127.0.0.1:8001/events`

## 2. Kuyruk çalışanını çalıştır

Başka bir terminalde:

```powershell
php artisan queue:work --queue=webhooks,default --tries=5
```

## 3. Postman'den gönder

Environment içindeki `webhook_receiver_url` değerini
`http://127.0.0.1:8001/webhook` yapın. Ardından sırasıyla:

1. `7 - Webhook endpoint oluştur`
2. `8 - Test webhooku kuyruğa ekle`
3. `9 - Webhook gönderimlerini listele`

isteklerini gönderin.

`8` numaralı istek webhook'u doğrudan göndermez; veritabanına teslimat kaydı ve
kuyruk görevi ekler. Asıl HTTP isteğini `queue:work` komutu gönderir.
