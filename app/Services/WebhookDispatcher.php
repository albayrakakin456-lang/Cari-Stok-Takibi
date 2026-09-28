<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Sale;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    public function invoiceCreated(User $user, Sale $invoice): void
    {
        $this->dispatchForUser($user, 'invoice.created', [
            'invoice' => $this->invoicePayload($invoice),
        ]);
    }

    public function invoiceCancelled(User $user, Sale $invoice): void
    {
        $this->dispatchForUser($user, 'invoice.cancelled', [
            'invoice' => $this->invoicePayload($invoice),
        ]);
    }

    public function testEndpoint(WebhookEndpoint $endpoint): WebhookDelivery
    {
        return $this->createDelivery($endpoint, (string) Str::uuid(), 'webhook.test', [
            'message' => 'Cari Takip webhook bağlantısı başarılı.',
        ]);
    }

    private function dispatchForUser(User $user, string $eventType, array $data): void
    {
        $eventId = (string) Str::uuid();

        $user->webhookEndpoints()
            ->where('active', true)
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint) => in_array($eventType, $endpoint->events, true))
            ->each(fn (WebhookEndpoint $endpoint) => $this->createDelivery(
                $endpoint,
                $eventId,
                $eventType,
                $data,
            ));
    }

    private function createDelivery(
        WebhookEndpoint $endpoint,
        string $eventId,
        string $eventType,
        array $data,
    ): WebhookDelivery {
        $delivery = $endpoint->deliveries()->create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => [
                'id' => $eventId,
                'type' => $eventType,
                'api_version' => 'v1',
                'occurred_at' => now()->toISOString(),
                'data' => $data,
            ],
            'status' => 'pending',
        ]);

        DeliverWebhook::dispatch($delivery)
            ->onQueue('webhooks')
            ->afterCommit();

        return $delivery;
    }

    private function invoicePayload(Sale $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'external_reference' => $invoice->external_reference,
            'contact_id' => $invoice->contact_id,
            'total_amount' => (string) $invoice->total_amount,
            'status' => $invoice->status,
            'created_at' => $invoice->created_at?->toISOString(),
            'cancelled_at' => $invoice->cancelled_at?->toISOString(),
        ];
    }
}
