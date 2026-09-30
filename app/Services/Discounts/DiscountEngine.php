<?php

namespace App\Services\Discounts;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class DiscountEngine
{
    public function __construct(private readonly CampaignStrategyFactory $strategyFactory) {}

    public function calculate(array $cart, Contact $contact): array
    {
        $quantities = [];
        foreach ($cart as $row) {
            $id = (int) $row['product_id'];
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) $row['quantity'];
        }

        $products = Product::query()->with('category')->whereKey(array_keys($quantities))->get()->keyBy('id');
        if ($products->count() !== count($quantities)) {
            throw ValidationException::withMessages(['product_id' => 'Sepette erişilemeyen bir ürün var.']);
        }

        $items = $this->makeItems($products, $quantities);
        $subtotal = array_sum(array_map(fn (array $item) => $item['quantity'] * $item['original_price'], $items));

        $context = new DiscountContext($items, $contact);

        $campaigns = Campaign::query()
            ->with('targets')
            ->activeAt()
            ->get()
            ->sort(function (Campaign $left, Campaign $right): int {
                return [$left->type->phase(), $left->id]
                    <=> [$right->type->phase(), $right->id];
            });

        // Her satır kampanyasını bağımsız hesapla ve ürün başına en yüksek indirimi seç.
        $exclusiveLineCampaignApplied = $this->applyBestLineCampaigns(
            $context,
            $campaigns->filter(fn (Campaign $campaign) => $campaign->type->phase() === 1),
        );

        foreach ($campaigns->filter(fn (Campaign $campaign) => $campaign->type->phase() === 2) as $campaign) {
            if ($exclusiveLineCampaignApplied) {
                break;
            }

            // Exclusive kampanya daha önce uygulanmış bir kampanyayla birleşmez.
            if ($campaign->is_exclusive && $context->campaignDiscount > 0) {
                continue;
            }

            $discountBefore = $context->campaignDiscount;
            $this->strategyFactory->make($campaign)->apply($context, $campaign);

            // Exclusive kampanya indirim ürettiyse sonraki kampanyaları çalıştırma.
            if ($campaign->is_exclusive && $context->campaignDiscount > $discountBefore) {
                break;
            }
        }

        // Cari iskontosu kampanyalardan bağımsız olarak her zaman en son çalışır.
        $context->applyCustomerDiscount((float) $contact->discount_rate);

        $lineDiscountTotal = array_sum(array_column($context->items, 'discount_amount'));
        $cartLevelDiscount = max(0, $context->campaignDiscount - $lineDiscountTotal)
            + $context->customerDiscount;
        $cartDiscountShares = $this->allocateCartDiscount($context->items, $cartLevelDiscount);

        $resultItems = array_map(function (array $item, int $index) use ($cartDiscountShares): array {
            $lineTotal = ($item['quantity'] * $item['original_price']) - $item['discount_amount'];
            $finalGross = max(0, $lineTotal - $cartDiscountShares[$index]);
            $taxAmount = $item['tax_rate'] > 0
                ? (int) round($finalGross * $item['tax_rate'] / (100 + $item['tax_rate']))
                : 0;

            return [
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'category_id' => $item['category_id'],
                'quantity' => $item['quantity'],
                'original_price' => $this->tl($item['original_price']),
                // Birim fiyat, satır indiriminin adetlere dağıtılmış efektif fiyatıdır.
                'unit_price' => $this->tl((int) round($lineTotal / $item['quantity'])),
                'discount_amount' => $this->tl($item['discount_amount']),
                'tax_rate' => $item['tax_rate'],
                // KDV, tüm kampanya ve cari indirimleri sonrası kalan tutarın içinden ayrıştırılır.
                'tax_amount' => $this->tl($taxAmount),
                'total' => $this->tl($lineTotal),
            ];
        }, $context->items, array_keys($context->items));

        return [
            'items' => $resultItems,
            'subtotal' => $this->tl($subtotal),
            'campaign_discount' => $this->tl($context->campaignDiscount),
            'customer_discount' => $this->tl($context->customerDiscount),
            'total_amount' => $this->tl(max(0, $subtotal - $context->campaignDiscount - $context->customerDiscount)),
            'discounts' => array_map(fn (array $discount) => [...$discount, 'amount' => $this->tl($discount['amount'])], $context->discounts),
        ];
    }

    private function applyBestLineCampaigns(DiscountContext $context, Collection $campaigns): bool
    {
        $bestPerLine = [];
        $exclusiveCandidates = [];

        foreach ($campaigns as $campaign) {
            // Her kampanya temiz sepet kopyasında çalışır; önce çalışan kampanya diğerini engellemez.
            $simulation = new DiscountContext($context->items, $context->contact);
            $this->strategyFactory->make($campaign)->apply($simulation, $campaign);

            $candidates = collect($simulation->discounts)
                ->filter(fn (array $discount) => $discount['scope'] === 'line')
                ->map(fn (array $discount) => [
                    'campaign' => $campaign,
                    'discount' => $discount,
                ])
                ->values()
                ->all();

            if ($campaign->is_exclusive) {
                if ($candidates !== []) {
                    $exclusiveCandidates[] = $candidates;
                }

                continue;
            }

            foreach ($candidates as $candidate) {
                $index = $candidate['discount']['item_index'];
                // Eşit indirimde daha önce oluşturulan kampanya korunur.
                if (! isset($bestPerLine[$index])
                    || $candidate['discount']['amount'] > $bestPerLine[$index]['discount']['amount']) {
                    $bestPerLine[$index] = $candidate;
                }
            }
        }

        $normalTotal = array_sum(array_map(
            fn (array $candidate) => $candidate['discount']['amount'],
            $bestPerLine,
        ));
        $bestExclusive = null;
        $bestExclusiveTotal = 0;
        foreach ($exclusiveCandidates as $candidates) {
            $total = array_sum(array_column(array_column($candidates, 'discount'), 'amount'));
            if ($total > $bestExclusiveTotal) {
                $bestExclusive = $candidates;
                $bestExclusiveTotal = $total;
            }
        }

        // Birleşmeyen kampanya yalnızca müşteriye daha yüksek toplam avantaj sağlıyorsa seçilir.
        $selected = $bestExclusiveTotal > $normalTotal
            ? $bestExclusive
            : array_values($bestPerLine);

        foreach ($selected ?? [] as $candidate) {
            $discount = $candidate['discount'];
            $context->addLineDiscount(
                index: $discount['item_index'],
                amount: $discount['amount'],
                code: $discount['code'],
                description: $discount['description'],
                campaign: $candidate['campaign'],
                metadata: $discount['metadata'],
            );
        }

        return $bestExclusiveTotal > $normalTotal && $bestExclusiveTotal > 0;
    }

    private function makeItems(Collection $products, array $quantities): array
    {
        return array_values(array_map(function (int $productId, int $quantity) use ($products): array {
            $product = $products[$productId];

            return [
                'product_id' => $productId,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'quantity' => $quantity,
                'original_price' => (int) round((float) $product->sale_price * 100),
                'tax_rate' => (int) $product->tax_rate,
                'discount_amount' => 0,
                'campaign_applied' => false,
                'applied_campaign_id' => null,
            ];
        }, array_map('intval', array_keys($quantities)), array_values($quantities)));
    }

    private function tl(int $kurus): float
    {
        return round($kurus / 100, 2);
    }

    private function allocateCartDiscount(array $items, int $discount): array
    {
        $lineTotals = array_map(
            fn (array $item) => max(0, ($item['quantity'] * $item['original_price']) - $item['discount_amount']),
            $items,
        );
        $base = array_sum($lineTotals);
        $shares = array_fill(0, count($items), 0);

        if ($discount <= 0 || $base <= 0) {
            return $shares;
        }

        $remaining = min($discount, $base);
        $lastPositiveIndex = array_key_last(array_filter($lineTotals));
        foreach ($lineTotals as $index => $lineTotal) {
            if ($lineTotal <= 0) {
                continue;
            }

            $share = $index === $lastPositiveIndex
                ? $remaining
                : min($remaining, intdiv($discount * $lineTotal, $base));
            $shares[$index] = min($share, $lineTotal);
            $remaining -= $shares[$index];
        }

        return $shares;
    }
}
