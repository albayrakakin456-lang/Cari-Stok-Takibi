@extends('layouts.app')

@section('title', 'Kasa Defteri - Cari & Stok Takip')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">{{ __('Cash Ledger & Financial Movements') }}</h2>
        </div>
        <a href="{{ route('cash.create') }}" class="btn btn-warning text-dark d-inline-flex align-items-center gap-2 shadow-sm fw-bold">
            <i class="bi bi-cash-coin"></i>
            <span>+ {{ __('New Cash Movement') }}</span>
        </a>
    </div>

    <!-- 4 Dönemsel Kasa Raporu Kartı -->
    <div class="row g-3 mb-4">
        <!-- 1. Toplam Mevcut Kasa Bakiyesi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('Current Cash Balance') }}</span>
                    <div class="kpi-icon-badge bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">₺{{ number_format($netBalance, 2, ',', '.') }}</h3>
                <span class="text-muted small">
                    <span class="text-success">+₺{{ number_format($totalIn, 2, ',', '.') }}</span> / 
                    <span class="text-danger">-₺{{ number_format($totalOut, 2, ',', '.') }}</span>
                </span>
            </div>
        </div>

        <!-- 2. Günlük Kasa -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('Today (Daily)') }}</span>
                    <div class="kpi-icon-badge {{ $todayNet >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                        <i class="bi bi-calendar-day"></i>
                    </div>
                </div>
                <h3 class="fw-bold {{ $todayNet >= 0 ? 'text-success' : 'text-danger' }} mb-1">
                    {{ $todayNet >= 0 ? '+' : '' }}₺{{ number_format($todayNet, 2, ',', '.') }}
                </h3>
                <span class="text-muted small">
                    <span class="text-success">+₺{{ number_format($todayIn, 2, ',', '.') }}</span> / 
                    <span class="text-danger">-₺{{ number_format($todayOut, 2, ',', '.') }}</span>
                </span>
            </div>
        </div>

        <!-- 3. Haftalık Kasa -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('This Week (Weekly)') }}</span>
                    <div class="kpi-icon-badge bg-info bg-opacity-10 text-info">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                </div>
                <h3 class="fw-bold {{ $weekNet >= 0 ? 'text-success' : 'text-danger' }} mb-1">
                    {{ $weekNet >= 0 ? '+' : '' }}₺{{ number_format($weekNet, 2, ',', '.') }}
                </h3>
                <span class="text-muted small">
                    <span class="text-success">+₺{{ number_format($weekIn, 2, ',', '.') }}</span> / 
                    <span class="text-danger">-₺{{ number_format($weekOut, 2, ',', '.') }}</span>
                </span>
            </div>
        </div>

        <!-- 4. Aylık Kasa -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card p-3 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('This Month (Monthly)') }}</span>
                    <div class="kpi-icon-badge bg-dark bg-opacity-10 text-dark">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                </div>
                <h3 class="fw-bold {{ $monthNet >= 0 ? 'text-success' : 'text-danger' }} mb-1">
                    {{ $monthNet >= 0 ? '+' : '' }}₺{{ number_format($monthNet, 2, ',', '.') }}
                </h3>
                <span class="text-muted small">
                    <span class="text-success">+₺{{ number_format($monthIn, 2, ',', '.') }}</span> / 
                    <span class="text-danger">-₺{{ number_format($monthOut, 2, ',', '.') }}</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Kasa Hareketleri Tablosu -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-list-columns-reverse text-warning fs-5"></i>
                <span>{{ __('Cash Movement History') }}</span>
            </span>
            <span class="badge bg-light text-dark border px-3 py-2">{{ $transactions->count() }} {{ __('Transactions') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Transaction Type') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Related Contact') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                        <tr>
                            <td><span class="text-muted small">{{ $tx->created_at->format('d.m.Y H:i') }}</span></td>
                            <td>
                                @if($tx->type == 'in')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-plus-lg"></i> {{ __('Collection / Income') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-dash-lg"></i> {{ __('Payment / Expense') }}
                                    </span>
                                @endif
                            </td>
                            <td class="fw-semibold text-dark">{{ $tx->description }}</td>
                            <td>
                                @if($tx->contact)
                                    <a href="{{ route('contacts.show', $tx->contact->id) }}" class="text-decoration-none fw-bold text-primary">
                                        👤 {{ $tx->contact->name }}
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted border">{{ __('General Expense / Income') }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold fs-6 {{ $tx->type == 'in' ? 'text-success' : 'text-danger' }}">
                                {{ $tx->type == 'in' ? '+' : '-' }}₺{{ number_format($tx->amount, 2, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-cash-stack fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                {{ __('No cash transactions recorded yet.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
