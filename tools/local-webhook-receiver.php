<?php

declare(strict_types=1);

// PHP'nin yerleşik sunucusu için yalnızca geliştirme ortamında kullanılan
// basit bir webhook alıcısıdır. Gerçek karşı tarafın sunucusunu taklit eder.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $path === '/') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'Yerel webhook alıcısı çalışıyor.',
        'webhook_url' => 'http://127.0.0.1:8001/webhook',
        'received_events' => 'http://127.0.0.1:8001/events',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return;
}

$logFile = __DIR__.'/../storage/logs/local-webhooks.jsonl';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $path === '/events') {
    header('Content-Type: application/json; charset=utf-8');

    $events = [];
    if (is_file($logFile)) {
        foreach (file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $events[] = $decoded;
            }
        }
    }

    echo json_encode(array_reverse($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $path !== '/webhook') {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Adres bulunamadı.'], JSON_UNESCAPED_UNICODE);
    return;
}

$rawBody = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : [];

$event = [
    'received_at' => date(DATE_ATOM),
    'headers' => [
        'X-Webhook-Id' => $headers['X-Webhook-Id'] ?? null,
        'X-Webhook-Timestamp' => $headers['X-Webhook-Timestamp'] ?? null,
        'X-Webhook-Signature' => $headers['X-Webhook-Signature'] ?? null,
        'User-Agent' => $headers['User-Agent'] ?? null,
    ],
    'body' => json_decode($rawBody, true) ?? $rawBody,
];

file_put_contents(
    $logFile,
    json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
    FILE_APPEND | LOCK_EX,
);

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'received' => true,
    'event_id' => $event['headers']['X-Webhook-Id'],
], JSON_UNESCAPED_UNICODE);
