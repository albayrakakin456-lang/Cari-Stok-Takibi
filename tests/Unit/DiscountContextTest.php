<?php

namespace Tests\Unit;

use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Contact;
use App\Services\Discounts\DiscountContext;
use PHPUnit\Framework\TestCase;

class DiscountContextTest extends TestCase
{
    public function test_line_discount_locks_the_line_and_creates_an_audit_record(): void
    {
        $campaign = new Campaign([
            'name' => '3 Al 2 Öde',
            'code' => 'UC-AL-IKI-ODE',
            'type' => CampaignType::BuyXPayY,
        ]);
        $campaign->id = 15;
        $context = new DiscountContext([$this->item()], new Contact(['discount_rate' => 0]));

        $applied = $context->addLineDiscount(
            index: 0,
            amount: 10_000,
            code: 'BUY_X_PAY_Y',
            description: 'Bir adet ücretsiz',
            campaign: $campaign,
            metadata: ['free_quantity' => 1],
        );

        $this->assertTrue($applied);
        $this->assertFalse($context->canApplyLineCampaign(0));
        $this->assertSame(10_000, $context->campaignDiscount);
        $this->assertSame(15, $context->discounts[0]['campaign_id']);
        $this->assertSame('line', $context->discounts[0]['scope']);
        $this->assertSame(['free_quantity' => 1], $context->discounts[0]['metadata']);

        // Aynı satıra ikinci kampanya uygulanamaz.
        $this->assertFalse($context->addLineDiscount(0, 5_000, 'SECOND', 'İkinci kampanya'));
    }

    public function test_discounts_cannot_make_the_invoice_negative_and_customer_discount_is_last(): void
    {
        $contact = new Contact(['discount_rate' => 10]);
        $context = new DiscountContext([$this->item()], $contact);

        // Brüt 30.000 kuruş; sepet indirimi kalan tutarla sınırlandırılır.
        $context->addCartDiscount(50_000, 'FIXED', 'Yüksek sabit indirim');
        $context->applyCustomerDiscount((float) $contact->discount_rate);

        $this->assertSame(30_000, $context->subtotal());
        $this->assertSame(30_000, $context->campaignDiscount);
        $this->assertSame(0, $context->customerDiscount);
        $this->assertSame(0, $context->payableBeforeCustomerDiscount());
    }

    private function item(): array
    {
        return [
            'product_id' => 1,
            'name' => 'Kalem',
            'category_id' => 2,
            'quantity' => 3,
            'original_price' => 10_000,
            'discount_amount' => 0,
            'campaign_applied' => false,
            'applied_campaign_id' => null,
        ];
    }
}
