@extends('layouts.app')

@section('title', 'Yönetici Paneli - Cari & Stok Takip')

@section('content')

    <!-- Başlık ve Hızlı İşlem Butonları -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <span>{{ __('Executive Dashboard') }}</span>
            </h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sales.create') }}" class="btn btn-success d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-receipt"></i>
                <span>+ {{ __('New Sale') }}</span>
            </a>
            <a href="{{ route('purchases.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-truck"></i>
                <span>+ {{ __('New Purchase') }}</span>
            </a>
            <a href="{{ route('cash.create') }}" class="btn btn-warning text-dark d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-cash-coin"></i>
                <span>+ {{ __('Cash Movement') }}</span>
            </a>
        </div>
    </div>

    <!-- 1. Satır: 4 Büyük KPI Gösterge Kartı -->
    <div class="row g-3 mb-4">
        <!-- Kasa Mevcut Nakit -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('Current Cash Balance') }}</span>
                    <div class="kpi-icon-badge bg-success bg-opacity-10 text-success">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-0">₺{{ number_format($cashBalance, 2, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Depo Toplam Stok Sermayesi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('Warehouse Stock Capital') }}</span>
                    <div class="kpi-icon-badge bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-boxes"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-0">₺{{ number_format($totalStockValue, 2, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Bugünkü Satış Cirosu -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('Today\'s Sales Revenue') }}</span>
                    <div class="kpi-icon-badge bg-info bg-opacity-10 text-info">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-0">₺{{ number_format($todaySales, 2, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Bugünkü Net Kasa Akışı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('Today\'s Net Cash') }}</span>
                    <div class="kpi-icon-badge {{ ($todayCashIn - $todayCashOut) >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                </div>
                <h3 class="fw-bold {{ ($todayCashIn - $todayCashOut) >= 0 ? 'text-success' : 'text-danger' }} mb-0">
                    {{ ($todayCashIn - $todayCashOut) >= 0 ? '+' : '' }}₺{{ number_format($todayCashIn - $todayCashOut, 2, ',', '.') }}
                </h3>
            </div>
        </div>
    </div>

    <!-- 2. Satır: İkincil İstatistik Sayaçları -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card p-3 shadow-sm border-0 bg-white d-flex flex-row align-items-center gap-3">
                <div class="rounded-3 bg-light p-3 text-secondary">
                    <i class="bi bi-person-lines-fill fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">{{ __('Total Contacts') }}</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $totalContacts }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 shadow-sm border-0 bg-white d-flex flex-row align-items-center gap-3">
                <div class="rounded-3 bg-light p-3 text-secondary">
                    <i class="bi bi-tags-fill fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">{{ __('Product Varieties') }}</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $totalProducts }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 shadow-sm border-0 bg-white d-flex flex-row align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success">
                    <i class="bi bi-arrow-down-circle-fill fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">{{ __('Today Collections') }}</span>
                    <h5 class="fw-bold mb-0 text-success">+₺{{ number_format($todayCashIn, 2, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 shadow-sm border-0 bg-white d-flex flex-row align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 p-3 text-danger">
                    <i class="bi bi-arrow-up-circle-fill fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">{{ __('Today Payments/Expenses') }}</span>
                    <h5 class="fw-bold mb-0 text-danger">-₺{{ number_format($todayCashOut, 2, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Satır: Kritik Stok Uyarısı (Acil Sipariş Tablosu) -->
    <div class="card mb-4 shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-danger text-white py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <span class="fw-bold">{{ __('Critical Stock Warning') }}</span>
            </div>
            <span class="badge bg-white text-danger fw-bold rounded-pill px-3 py-2 fs-6">
                {{ $criticalProducts->count() }}
            </span>
        </div>
        <div class="card-body p-0">
            @if($criticalProducts->isEmpty())
                <div class="p-4 text-center text-success bg-success bg-opacity-10">
                    <i class="bi bi-shield-fill-check fs-2 d-block mb-1"></i>
                    <strong class="fs-6">{{ __('All stock levels are safe.') }}</strong>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Stock Code') }}</th>
                                <th>{{ __('Product Name') }}</th>
                                <th class="text-center">{{ __('Critical Limit') }}</th>
                                <th class="text-center">{{ __('Remaining Stock') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criticalProducts as $prod)
                            <tr>
                                <td><span class="stock-code-badge">{{ $prod->code ?? '-' }}</span></td>
                                <td class="fw-bold text-dark">{{ $prod->name }}</td>
                                <td class="text-center text-muted">{{ $prod->min_stock }} {{ __('Units') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold">
                                        {{ $prod->stock }} {{ __('Units') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-cart-plus"></i>
                                        <span>{{ __('Order Stock') }}</span>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- 4. Satır: İki Sütunlu Son Hareketler -->
    <div class="row g-4">
        <!-- Sol Sütun: Son Satış Faturaları -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-receipt text-primary fs-5"></i>
                        <span class="fw-bold text-dark">{{ __('Recent Sales Invoices') }}</span>
                    </div>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('View All') }} &rarr;</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Invoice No') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th class="text-center">{{ __('Inspect') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                <tr>
                                    <td><strong class="text-dark">{{ $sale->invoice_number }}</strong></td>
                                    <td>{{ $sale->contact->name ?? '-' }}</td>
                                    <td class="text-end fw-bold text-success">₺{{ number_format($sale->total_amount, 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-light border" title="{{ __('Detail') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">{{ __('No sales invoices yet.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Sütun: Son Kasa Hareketleri -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cash-coin text-warning fs-5"></i>
                        <span class="fw-bold text-dark">{{ __('Recent Cash Transactions') }}</span>
                    </div>
                    <a href="{{ route('cash.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('View All') }} &rarr;</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Action') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $tx)
                                <tr>
                                    <td>
                                        @if($tx->type == 'in')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-plus-lg"></i> {{ __('Inflow') }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-dash-lg"></i> {{ __('Outflow') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td><span class="text-truncate d-inline-block" style="max-width: 220px;">{{ $tx->description }}</span></td>
                                    <td class="text-end fw-bold {{ $tx->type == 'in' ? 'text-success' : 'text-danger' }}">
                                        {{ $tx->type == 'in' ? '+' : '-' }}₺{{ number_format($tx->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">{{ __('No cash transactions yet.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
