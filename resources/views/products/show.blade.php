@extends('layouts.app')

@section('title', __('Product Card & Stock Movements') . ' - ' . $product->name)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill font-monospace">{{ $product->code }}</span>
            <h3 class="fw-bold mb-0">{{ $product->name }}</h3>
        </div>
        <p class="text-muted small mb-0">{{ __('Product Card & Stock Movements') }}</p>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Products') }}</span>
    </a>
</div>

<!-- 4 Özet İstatistik Kartı -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">{{ __('Current Stock') }}</span>
                <div class="kpi-icon-badge {{ $product->stock <= $product->min_stock ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' }}">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
            <h2 class="fw-bold {{ $product->stock <= $product->min_stock ? 'text-danger' : 'text-dark' }} mb-1">
                {{ $product->stock }} <small class="fs-6 fw-normal text-muted">{{ __('Units') }}</small>
            </h2>
            <div class="small {{ $product->stock <= $product->min_stock ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $product->stock <= $product->min_stock ? __('Critical stock level!') : __('Normal stock level') }}
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">{{ __('Critical Stock Level') }}</span>
                <div class="kpi-icon-badge bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
            <h2 class="fw-bold text-dark mb-1">
                {{ $product->min_stock }} <small class="fs-6 fw-normal text-muted">{{ __('Units') }}</small>
            </h2>
            <div class="small text-muted">{{ __('Stock alert threshold') }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">{{ __('Total Inflow (Warehouse)') }}</span>
                <div class="kpi-icon-badge bg-info bg-opacity-10 text-info">
                    <i class="bi bi-box-arrow-in-down"></i>
                </div>
            </div>
            <h2 class="fw-bold text-dark mb-1">
                {{ $totalIn }} <small class="fs-6 fw-normal text-muted">{{ __('Units') }}</small>
            </h2>
            <div class="small text-muted">{{ __('Count + Purchases + Returns') }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">{{ __('Total Outflow (Sales)') }}</span>
                <div class="kpi-icon-badge bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-box-arrow-up-right"></i>
                </div>
            </div>
            <h2 class="fw-bold text-dark mb-1">
                {{ $totalOut }} <small class="fs-6 fw-normal text-muted">{{ __('Units') }}</small>
            </h2>
            <div class="small text-muted">{{ __('Sold with sales invoices') }}</div>
        </div>
    </div>
</div>

<!-- Fiyat ve Finansal Değer Bilgileri -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-cash-coin me-1"></i>
            {{ __('Pricing & Warehouse Valuation') }}
        </h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small mb-1">{{ __('Purchase Price') }}</div>
                <h5 class="fw-bold mb-0">{{ number_format($product->purchase_price, 2) }} ₺</h5>
            </div>
            <div class="col-md-3">
                <div class="text-muted small mb-1">{{ __('Sale Price') }}</div>
                <h5 class="fw-bold mb-0">{{ number_format($product->sale_price, 2) }} ₺</h5>
            </div>
            <div class="col-md-3">
                <div class="text-muted small mb-1">{{ __('Warehouse Cost Value:') }}</div>
                <h5 class="fw-bold text-danger mb-0">{{ number_format($stockCostValue, 2) }} ₺</h5>
                <small class="text-muted">{{ __('(Current Stock × Purchase Price)') }}</small>
            </div>
            <div class="col-md-3">
                <div class="text-muted small mb-1">{{ __('Warehouse Sales Value:') }}</div>
                <h5 class="fw-bold text-success mb-0">{{ number_format($stockSaleValue, 2) }} ₺</h5>
                <small class="text-muted">{{ __('(Current Stock × Sale Price)') }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Stok Hareketleri Tablosu -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-clock-history me-1"></i>
            {{ __('Stock Movement History (Logs)') }}
        </h6>
        <span class="badge bg-secondary rounded-pill">{{ $product->stockMovements->count() }} {{ __('Records') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('Date') }}</th>
                        <th>{{ __('Transaction Type') }}</th>
                        <th class="text-center">{{ __('Quantity') }}</th>
                        <th>{{ __('Description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->stockMovements as $movement)
                    <tr>
                        <td class="ps-4">{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($movement->type == 'in')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-arrow-down-left me-1"></i>{{ __('+ Stock Inflow') }}
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="bi bi-arrow-up-right me-1"></i>{{ __('- Stock Outflow') }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center fw-bold fs-6">
                            {{ $movement->type == 'in' ? '+' : '-' }}{{ $movement->quantity }} {{ __('Units') }}
                        </td>
                        <td>{{ $movement->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted p-4">
                            <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                            {{ __('No stock movements recorded yet for this product.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

