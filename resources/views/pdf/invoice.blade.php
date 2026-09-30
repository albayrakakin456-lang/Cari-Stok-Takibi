<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 16px; margin-bottom: 24px; }
        .brand { color: #1d4ed8; font-size: 22px; font-weight: bold; }
        .invoice-title { font-size: 18px; font-weight: bold; text-align: right; }
        .muted { color: #6b7280; }
        .info { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        .info td { width: 50%; vertical-align: top; padding: 4px 0; }
        .label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .value { font-size: 13px; font-weight: bold; margin-top: 3px; }
        .items { width: 100%; border-collapse: collapse; }
        .items th { background: #eff6ff; color: #1e3a8a; padding: 9px 7px; text-align: left; border-bottom: 1px solid #bfdbfe; }
        .items td { padding: 9px 7px; border-bottom: 1px solid #e5e7eb; }
        .items .number { text-align: right; white-space: nowrap; }
        .items .center { text-align: center; }
        .total-label { text-align: right; font-size: 14px; font-weight: bold; padding-top: 14px; }
        .total { text-align: right; color: #166534; font-size: 16px; font-weight: bold; padding-top: 14px; }
        .discount { text-align: right; color: #b91c1c; font-weight: bold; }
        .footer { position: fixed; bottom: -12px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $isSale = $invoiceType === 'sale';
        $title = $isSale ? 'SATIŞ FATURASI' : 'ALIŞ FATURASI';
        $partyTitle = $isSale ? 'Müşteri Bilgileri' : 'Tedarikçi Bilgileri';
        $totalTax = $invoice->items->sum(function ($item) {
            $rate = $item->tax_rate ?? $item->product?->tax_rate ?? 0;

            return $item->tax_rate === null
                ? round((float) $item->total * $rate / (100 + $rate), 2)
                : (float) $item->tax_amount;
        });
    @endphp

    <table class="header" width="100%">
        <tr>
            <td class="brand">Cari &amp; Stok Takip PRO</td>
            <td class="invoice-title">
                {{ $title }}<br>
                <span class="muted" style="font-size: 11px;">{{ $invoice->invoice_number }}</span>
            </td>
        </tr>
    </table>

    <div style="font-size: 14px; font-weight: bold; margin-bottom: 10px;">{{ $partyTitle }}</div>
    <table class="info">
        <tr>
            <td>
                <div class="label">Firma / Cari</div>
                <div class="value">{{ $invoice->contact->name }}</div>
            </td>
            <td>
                <div class="label">Fatura Tarihi</div>
                <div class="value">{{ $invoice->created_at->format('d.m.Y H:i') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Telefon</div>
                <div>{{ $invoice->contact->phone ?? 'Belirtilmedi' }}</div>
            </td>
            <td>
                <div class="label">E-posta</div>
                <div>{{ $invoice->contact->email ?? 'Belirtilmedi' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">Adres</div>
                <div>{{ $invoice->contact->address ?? 'Belirtilmedi' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th style="width: 95px;">Stok Kodu</th>
                <th>Ürün</th>
                <th class="center" style="width: 60px;">Miktar</th>
                <th class="number" style="width: 72px;">Birim Fiyat</th>
                <th class="center" style="width: 42px;">KDV</th>
                <th class="number" style="width: 68px;">KDV Tutarı</th>
                <th class="number" style="width: 78px;">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                @php
                    $lineTaxRate = $item->tax_rate ?? $item->product?->tax_rate ?? 0;
                    $lineTaxAmount = $item->tax_rate === null
                        ? round((float) $item->total * $lineTaxRate / (100 + $lineTaxRate), 2)
                        : (float) $item->tax_amount;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->code ?? '-' }}</td>
                    <td>{{ $item->product->name ?? 'Silinmiş Ürün' }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="number">{{ number_format($item->unit_price, 2, ',', '.') }} TL</td>
                    <td class="center">%{{ $lineTaxRate }}</td>
                    <td class="number">{{ number_format($lineTaxAmount, 2, ',', '.') }} TL</td>
                    <td class="number">{{ number_format($item->total, 2, ',', '.') }} TL</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($isSale)
                <tr>
                    <td colspan="7" class="total-label">ARA TOPLAM</td>
                    <td class="number">{{ number_format($invoice->subtotal, 2, ',', '.') }} TL</td>
                </tr>
                <tr>
                    <td colspan="7" class="total-label">KAMPANYA İNDİRİMİ</td>
                    <td class="discount">-{{ number_format($invoice->campaign_discount, 2, ',', '.') }} TL</td>
                </tr>
                <tr>
                    <td colspan="7" class="total-label">CARİ İSKONTOSU</td>
                    <td class="discount">-{{ number_format($invoice->customer_discount, 2, ',', '.') }} TL</td>
                </tr>
            @endif
            <tr>
                <td colspan="7" class="total-label">KDV TOPLAMI (DAHİL)</td>
                <td class="number">{{ number_format($totalTax, 2, ',', '.') }} TL</td>
            </tr>
            <tr>
                <td colspan="7" class="total-label">GENEL TOPLAM (KDV DAHİL)</td>
                <td class="total">{{ number_format($invoice->total_amount, 2, ',', '.') }} TL</td>
            </tr>
        </tfoot>
    </table>

    @if($isSale && $invoice->discounts->isNotEmpty())
        <div style="font-size: 12px; font-weight: bold; margin-top: 20px;">Uygulanan İndirimler</div>
        <ul>
            @foreach($invoice->discounts as $discount)
                <li>{{ $discount->campaign_name }} — {{ $discount->description }}: -{{ number_format($discount->amount, 2, ',', '.') }} TL</li>
            @endforeach
        </ul>
    @endif

    <div class="footer">Bu belge Cari &amp; Stok Takip PRO tarafından oluşturulmuştur.</div>
</body>
</html>
