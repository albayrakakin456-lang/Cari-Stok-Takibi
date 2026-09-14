@extends('layouts.app')

@section('title', 'Alış Faturaları - Cari & Stok Takip')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">{{ __('Purchase Invoices (Goods Receipt)') }}</h2>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-truck"></i>
            <span>+ {{ __('New Purchase (Goods Receipt)') }}</span>
        </a>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Invoice No') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th class="text-end">{{ __('Total Amount') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td><strong class="text-dark">{{ $purchase->invoice_number }}</strong></td>
                            <td>
                                <span class="fw-semibold">{{ $purchase->contact->name }}</span>
                            </td>
                            <td class="text-end fw-bold text-primary fs-6">
                                ₺{{ number_format($purchase->total_amount, 2, ',', '.') }}
                            </td>
                            <td>
                                <span class="text-muted small">{{ $purchase->created_at->format('d.m.Y H:i') }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>{{ __('Invoice Details') }}</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-truck fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                {{ __('No purchase invoices recorded yet.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
