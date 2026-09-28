<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookUrlGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public WebhookDelivery $delivery)
    {
    }

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(WebhookUrlGuard $urlGuard): void
    {
        $this->delivery->refresh();
        $endpoint = WebhookEndpoint::withTrashed()->find($this->delivery->webhook_endpoint_id);

        if (! $endpoint || $endpoint->trashed() || ! $endpoint->active) {
            $this->delivery->update([
                'status' => 'failed',
                'error_message' => 'Webhook endpoint aktif değil.',
            ]);

            return;
        }

        $this->delivery->increment('attempt_count');
        $this->delivery->update([
            'status' => 'processing',
            'last_attempt_at' => now(),
            'error_message' => null,
        ]);

        try {
            $urlGuard->assertSafe($endpoint->url);

            $body = json_encode(
                $this->delivery->payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
            $timestamp = (string) now()->timestamp;
            $signature = hash_hmac('sha256', $timestamp.'.'.$body, $endpoint->secret);

            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withoutRedirecting()
                ->withHeaders([
                    'User-Agent' => 'CariTakip-Webhooks/1.0',
                    'X-Webhook-Id' => $this->delivery->event_id,
                    'X-Webhook-Timestamp' => $timestamp,
                    'X-Webhook-Signature' => 'v1='.$signature,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $this->recordResponse($response);
        } catch (Throwable $exception) {
            $this->delivery->update([
                'status' => 'pending',
                'error_message' => Str::limit($exception->getMessage(), 2000, ''),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->delivery->update([
            'status' => 'failed',
            'error_message' => Str::limit($exception->getMessage(), 2000, ''),
        ]);
    }

    private function recordResponse(Response $response): void
    {
        $responseBody = Str::limit($response->body(), 2000, '');

        if ($response->successful()) {
            $this->delivery->update([
                'status' => 'delivered',
                'response_status' => $response->status(),
                'response_body' => $responseBody,
                'delivered_at' => now(),
            ]);

            return;
        }

        if (in_array($response->status(), [408, 425, 429], true) || $response->serverError()) {
            $this->delivery->update([
                'status' => 'pending',
                'response_status' => $response->status(),
                'response_body' => $responseBody,
                'error_message' => "Geçici webhook hatası: HTTP {$response->status()}",
            ]);

            throw new RuntimeException("Webhook geçici hata döndürdü: HTTP {$response->status()}");
        }

        $this->delivery->update([
            'status' => 'failed',
            'response_status' => $response->status(),
            'response_body' => $responseBody,
            'error_message' => "Kalıcı webhook hatası: HTTP {$response->status()}",
        ]);
    }
}
