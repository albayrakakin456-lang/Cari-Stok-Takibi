<?php

namespace App\Services\Discounts;

use App\Models\Campaign;
use App\Models\Contact;

final class DiscountContext
{
    public array $items;

    public array $discounts = [];

    public int $campaignDiscount = 0;

    public int $customerDiscount = 0;

    public function __construct(array $items, public readonly Contact $contact)
    {
        $this->items = $items;
    }

    public function lineNet(array $item): int
    {
        return max(0, ($item['quantity'] * $item['original_price']) - $item['discount_amount']);
    }

    public function subtotal(): int
    {
        return array_sum(array_map(
            fn (array $item) => $item['quantity'] * $item['original_price'],
            $this->items,
        ));
    }

    public function itemsNet(): int
    {
        return array_sum(array_map(fn (array $item) => $this->lineNet($item), $this->items));
    }

    public function canApplyLineCampaign(int $index): bool
    {
        return isset($this->items[$index])
            && ! $this->items[$index]['campaign_applied'];
    }

    public function addLineDiscount(
        int $index,
        int $amount,
        string $code,
        string $description,
        ?Campaign $campaign = null,
        array $metadata = [],
    ): bool {
        if ($amount <= 0 || ! $this->canApplyLineCampaign($index)) {
            return false;
        }

        // İndirim satırın kalan tutarını aşamaz.
        $amount = min($amount, $this->lineNet($this->items[$index]));
        if ($amount === 0) {
            return false;
        }

        $this->items[$index]['discount_amount'] += $amount;
        $this->items[$index]['campaign_applied'] = true;
        $this->items[$index]['applied_campaign_id'] = $campaign?->id;
        $this->campaignDiscount += $amount;
        $this->discounts[] = $this->discountRecord(
            amount: $amount,
            code: $code,
            description: $description,
            campaign: $campaign,
            itemIndex: $index,
            metadata: $metadata,
        );

        return true;
    }

    public function addCartDiscount(
        int $amount,
        string $code,
        string $description,
        ?Campaign $campaign = null,
        array $metadata = [],
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        // Sepet indirimi kalan fatura tutarını negatife düşüremez.
        $amount = min($amount, $this->payableBeforeCustomerDiscount());
        if ($amount === 0) {
            return false;
        }

        $this->campaignDiscount += $amount;
        $this->discounts[] = $this->discountRecord(
            amount: $amount,
            code: $code,
            description: $description,
            campaign: $campaign,
            metadata: $metadata,
        );

        return true;
    }

    public function payableBeforeCustomerDiscount(): int
    {
        return max(0, $this->subtotal() - $this->campaignDiscount);
    }

    public function applyCustomerDiscount(float $rate): void
    {
        $rate = max(0, min(100, $rate));
        $this->customerDiscount = (int) round($this->payableBeforeCustomerDiscount() * $rate / 100);

        if ($this->customerDiscount === 0) {
            return;
        }

        $this->discounts[] = [
            'scope' => 'cart',
            'item_index' => null,
            'campaign_id' => null,
            'campaign_name' => 'Cari özel iskontosu',
            'campaign_type' => 'customer_discount',
            'code' => 'CUSTOMER_DISCOUNT',
            'description' => "Cari özel iskontosu (%{$rate})",
            'amount' => $this->customerDiscount,
            'metadata' => ['discount_rate' => $rate],
        ];
    }

    private function discountRecord(
        int $amount,
        string $code,
        string $description,
        ?Campaign $campaign,
        ?int $itemIndex = null,
        array $metadata = [],
    ): array {
        return [
            'scope' => $itemIndex === null ? 'cart' : 'line',
            'item_index' => $itemIndex,
            'campaign_id' => $campaign?->id,
            'campaign_name' => $campaign?->name ?? $code,
            'campaign_type' => $campaign?->type->value ?? 'legacy_rule',
            'code' => $campaign?->code ?? $code,
            'description' => $description,
            'amount' => $amount,
            'metadata' => $metadata,
        ];
    }
}
