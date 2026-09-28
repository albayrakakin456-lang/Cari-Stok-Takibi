<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookEndpointController extends Controller
{
    private const EVENTS = ['invoice.created', 'invoice.cancelled'];

    public function __construct(private readonly WebhookDispatcher $webhooks)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $endpoints = $request->user()->webhookEndpoints()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $endpoints,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $plainTextSecret = 'whsec_'.Str::random(48);

        $endpoint = WebhookEndpoint::withTrashed()
            ->where('user_id', $request->user()->id)
            ->where('url', $validated['url'])
            ->first();

        if ($endpoint?->trashed()) {
            $endpoint->fill([
                ...$validated,
                'secret' => $plainTextSecret,
                'active' => $validated['active'] ?? true,
            ]);
            $endpoint->restore();
            $endpoint->save();
        } else {
            $endpoint = $request->user()->webhookEndpoints()->create([
                ...$validated,
                'secret' => $plainTextSecret,
                'active' => $validated['active'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook endpoint oluşturuldu. Secret yalnızca bu cevapta gösterilir.',
            'data' => [
                ...$endpoint->toArray(),
                'secret' => $plainTextSecret,
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $endpoint = $request->user()->webhookEndpoints()->findOrFail($id);
        $validated = $request->validate($this->rules($request, $endpoint, true));

        $endpoint->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Webhook endpoint güncellendi.',
            'data' => $endpoint->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $endpoint = $request->user()->webhookEndpoints()->findOrFail($id);
        $endpoint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook endpoint pasife alındı.',
        ]);
    }

    public function test(Request $request, int $id): JsonResponse
    {
        $endpoint = $request->user()->webhookEndpoints()
            ->where('active', true)
            ->findOrFail($id);
        $delivery = $this->webhooks->testEndpoint($endpoint);

        return response()->json([
            'success' => true,
            'message' => 'Test webhooku kuyruğa eklendi.',
            'data' => $delivery,
        ], 202);
    }

    private function rules(
        Request $request,
        ?WebhookEndpoint $endpoint = null,
        bool $partial = false,
    ): array {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'url' => [
                $required,
                'url:http,https',
                'max:500',
                Rule::unique('webhook_endpoints', 'url')
                    ->ignore($endpoint?->id)
                    ->where(fn ($query) => $query
                        ->where('user_id', $request->user()->id)
                        ->whereNull('deleted_at')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $parts = parse_url((string) $value);
                    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
                    $host = strtolower((string) ($parts['host'] ?? ''));

                    if (isset($parts['user']) || isset($parts['pass'])) {
                        $fail('Webhook URL kullanıcı adı veya şifre içeremez.');
                    }

                    if (app()->environment('production') && $scheme !== 'https') {
                        $fail('Production ortamında webhook URL HTTPS kullanmalıdır.');
                    }

                    if (app()->environment('production') && $host === 'localhost') {
                        $fail('Production ortamında localhost webhook adresi kullanılamaz.');
                    }

                    if (app()->environment('production') && filter_var($host, FILTER_VALIDATE_IP)) {
                        $isPublic = filter_var(
                            $host,
                            FILTER_VALIDATE_IP,
                            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
                        );

                        if ($isPublic === false) {
                            $fail('Production ortamında özel veya rezerve IP adresi kullanılamaz.');
                        }
                    }
                },
            ],
            'events' => [$required, 'array', 'min:1'],
            'events.*' => ['required', 'string', 'distinct', Rule::in(self::EVENTS)],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
