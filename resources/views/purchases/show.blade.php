@extends('layouts.app')

@section('title', __('Purchase Invoice (Goods Receipt)') . ' - ' . $purchase->invoice_number)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-secondary fs-6 px-3 py-2 rounded-pill font-monospace">{{ $purchase->invoice_number }}</span>
            <h3 class="fw-bold mb-0">{{ __('Purchase Invoice (Goods Receipt)') }}</h3>
        </div>
        <p class="text-muted small mb-0">{{ $purchase->created_at->format('d.m.Y H:i') }}</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('purchases.pdf', $purchase->id) }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-pdf"></i>
            <span>{{ __('Download PDF') }}</span>
        </a>
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>{{ __('Back to Purchases') }}</span>
        </a>
        
        <!-- Fatura İptal (Delete) Formu -->
        <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('ATTENTION: When you cancel this purchase invoice, received goods will be deducted from warehouse stock. Are you sure?') }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-x-circle"></i>
                <span>{{ __('Cancel Invoice') }}</span>
            </button>
        </form>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        {{ $errors->first() }}
    </div>
@endif

<!-- Tedarikçi Bilgileri Kartı -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-truck me-1"></i>
            {{ __('Supplier Information') }}
        </h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Company / Supplier Name:') }}</div>
                <div class="fw-bold fs-6">{{ $purchase->contact->name }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Phone') }}</div>
                <div>{{ $purchase->contact->phone ?? __('Not Specified') }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Email') }}</div>
                <div>{{ $purchase->contact->email ?? __('Not Specified') }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Address') }}</div>
                <div>{{ $purchase->contact->address ?? __('Not Specified') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Fatura Kalemleri Tablosu -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-box-seam me-1"></i>
            {{ __('Received Items / Purchase Lines') }}
        </h6>
        <span class="badge bg-secondary rounded-pill">{{ $purchase->items->count() }} {{ __('Records') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>{{ __('Stock Code') }}</th>
                        <th>{{ __('Product Name') }}</th>
                        <th class="text-center">{{ __('Inflow Quantity') }}</th>
                        <th class="text-end">{{ __('Unit Price') }}</th>
                        <th class="text-end pe-4">{{ __('Total Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $index => $item)
                    <tr>
                        <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                        <td class="font-monospace fw-semibold">{{ $item->product->code ?? '-' }}</td>
                        <td>{{ $item->product->name ?? __('Deleted Product') }}</td>
                        <td class="text-center fw-bold text-success">+{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 2) }} ₺</td>
                        <td class="text-end pe-4 fw-bold">{{ number_format($item->total, 2) }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end fs-5 py-3">{{ __('Invoice Total:') }}</th>
                        <th class="text-end fs-5 text-primary pe-4 py-3">{{ number_format($purchase->total_amount, 2) }} ₺</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
