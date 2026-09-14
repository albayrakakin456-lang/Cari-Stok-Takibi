@extends('layouts.app')

@section('title', __('Sales Invoice') . ' - ' . $sale->invoice_number)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-secondary fs-6 px-3 py-2 rounded-pill font-monospace">{{ $sale->invoice_number }}</span>
            <h3 class="fw-bold mb-0">{{ __('Sales Invoice') }}</h3>
        </div>
        <p class="text-muted small mb-0">{{ $sale->created_at->format('d.m.Y H:i') }}</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('sales.pdf', $sale->id) }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-pdf"></i>
            <span>{{ __('Download PDF') }}</span>
        </a>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>{{ __('Back to Sales') }}</span>
        </a>
        
        <!-- Fatura İptal (Delete) Formu -->
        <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('ATTENTION: When you cancel this sales invoice, stocks will be restored and cash will be refunded. Are you sure?') }}');">
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

<!-- Müşteri ve Fatura Bilgileri Kartı -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-person-fill me-1"></i>
            {{ __('Customer Information') }}
        </h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Customer Name:') }}</div>
                <div class="fw-bold fs-6">{{ $sale->contact->name }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Phone') }}</div>
                <div>{{ $sale->contact->phone ?? __('Not Specified') }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Email') }}</div>
                <div>{{ $sale->contact->email ?? __('Not Specified') }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ __('Address') }}</div>
                <div>{{ $sale->contact->address ?? __('Not Specified') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Fatura Kalemleri Tablosu -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-basket me-1"></i>
            {{ __('Sold Items / Invoice Lines') }}
        </h6>
        <span class="badge bg-secondary rounded-pill">{{ $sale->items->count() }} {{ __('Records') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>{{ __('Stock Code') }}</th>
                        <th>{{ __('Product Name') }}</th>
                        <th class="text-center">{{ __('Quantity') }}</th>
                        <th class="text-end">{{ __('Unit Price') }}</th>
                        <th class="text-end pe-4">{{ __('Total Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $index => $item)
                    <tr>
                        <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                        <td class="font-monospace fw-semibold">{{ $item->product->code ?? '-' }}</td>
                        <td>{{ $item->product->name ?? __('Deleted Product') }}</td>
                        <td class="text-center fw-semibold">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 2) }} ₺</td>
                        <td class="text-end pe-4 fw-bold">{{ number_format($item->total, 2) }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end fs-5 py-3">{{ __('Grand Total:') }}</th>
                        <th class="text-end fs-5 text-success pe-4 py-3">{{ number_format($sale->total_amount, 2) }} ₺</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
