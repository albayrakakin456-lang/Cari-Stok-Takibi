@extends('layouts.app')

@section('title', __('Contact Card & Statement') . ' - ' . $contact->name)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge {{ $contact->type == 'customer' ? 'bg-primary' : 'bg-success' }} fs-6 px-3 py-2 rounded-pill">
                {{ $contact->type == 'customer' ? __('Customer (Sales)') : __('Supplier (Purchases)') }}
            </span>
            <h3 class="fw-bold mb-0">{{ $contact->name }}</h3>
        </div>
        <p class="text-muted small mb-0">{{ __('Contact Card & Statement') }}</p>
    </div>
    <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Contacts') }}</span>
    </a>
</div>

<!-- 3 Özet Finansal Gösterge Kartı -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card kpi-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">
                    {{ $contact->type == 'customer' ? __('Total Sales (Customer Debt)') : __('Total Purchases (Our Debt)') }}
                </span>
                <div class="kpi-icon-badge bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
            <h2 class="fw-bold text-dark mb-1">{{ number_format($totalInvoices, 2, ',', '.') }} ₺</h2>
            <div class="text-muted small">
                {{ $contact->type == 'customer' ? __('Invoices billed to customer') : __('Invoices received from supplier') }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">
                    {{ $contact->type == 'customer' ? __('Total Collections (Received)') : __('Payments Made to Supplier') }}
                </span>
                <div class="kpi-icon-badge bg-success bg-opacity-10 text-success">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <h2 class="fw-bold text-dark mb-1">{{ number_format($netPaid, 2, ',', '.') }} ₺</h2>
            <div class="text-muted small">
                {{ $contact->type == 'customer' ? __('Net cash received into register') : __('Cash paid to supplier') }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">
                    {{ $contact->type == 'customer' ? __('Remaining Receivable (Customer Debt)') : __('Remaining Payable (To Supplier)') }}
                </span>
                <div class="kpi-icon-badge {{ $balance > 0 ? 'bg-warning bg-opacity-10 text-warning' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                    <i class="bi bi-scale"></i>
                </div>
            </div>
            <h2 class="fw-bold {{ $balance > 0 ? 'text-warning' : 'text-dark' }} mb-1">{{ number_format($balance, 2, ',', '.') }} ₺</h2>
            <div class="text-muted small">
                {{ $balance > 0 ? __('Open account balance to be settled') : __('Account settled / Zero balance') }}
            </div>
        </div>
    </div>
</div>

<!-- Cari Sabit Bilgileri -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-info-circle me-1"></i>
            {{ __('Contact & Detail Information') }}
        </h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small mb-1">{{ __('Phone') }}</div>
                <div class="fw-semibold">{{ $contact->phone ?? __('Not Specified') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small mb-1">{{ __('Email') }}</div>
                <div class="fw-semibold">{{ $contact->email ?? __('Not Specified') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small mb-1">{{ __('Address') }}</div>
                <div class="fw-semibold">{{ $contact->address ?? __('Not Specified') }}</div>
            </div>
        </div>
        @if($contact->note)
            <hr class="my-3">
            <div class="text-muted small mb-1">{{ __('Note:') }}</div>
            <div>{{ $contact->note }}</div>
        @endif
    </div>
</div>

<!-- Faturalar Geçmişi -->
@if($contact->type == 'customer')
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-receipt me-1"></i>
            {{ __('Billed Sales Invoices') }}
        </h6>
        <span class="badge bg-secondary rounded-pill">{{ $contact->sales->count() }} {{ __('Invoices') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('Invoice No') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contact->sales as $sale)
                    <tr>
                        <td class="ps-4 fw-semibold font-monospace">{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-end fw-bold">{{ number_format($sale->total_amount, 2) }} ₺</td>
                        <td class="text-center">
                            <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-outline-primary">
                                {{ __('View Invoice') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted p-4">
                            {{ __('No sales invoices recorded for this contact.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-box-arrow-in-down me-1"></i>
            {{ __('Purchased Invoices') }}
        </h6>
        <span class="badge bg-secondary rounded-pill">{{ $contact->purchases->count() }} {{ __('Invoices') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('Invoice No') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contact->purchases as $purchase)
                    <tr>
                        <td class="ps-4 fw-semibold font-monospace">{{ $purchase->invoice_number }}</td>
                        <td>{{ $purchase->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-end fw-bold text-primary">{{ number_format($purchase->total_amount, 2) }} ₺</td>
                        <td class="text-center">
                            <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-sm btn-outline-primary">
                                {{ __('View Invoice') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted p-4">
                            {{ __('No purchase invoices recorded for this contact.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Kasa ve Ödeme Hareketleri -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-wallet2 me-1"></i>
            {{ __('Related Cash Transactions') }}
        </h6>
        <span class="badge bg-secondary rounded-pill">{{ $contact->cashTransactions->count() }} {{ __('Transactions') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('Date') }}</th>
                        <th>{{ __('Transaction Type') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end pe-4">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contact->cashTransactions as $tx)
                    <tr>
                        <td class="ps-4">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($tx->type == 'in')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    {{ __('Collection (Cash In)') }}
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                    {{ __('Payment / Refund (Cash Out)') }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $tx->description }}</td>
                        <td class="text-end pe-4 fw-bold">{{ number_format($tx->amount, 2) }} ₺</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted p-4">
                            {{ __('No cash transactions recorded for this contact.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

