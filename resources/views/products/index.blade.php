@extends('layouts.app')

@section('title', 'Ürün ve Stok Listesi - Cari & Stok Takip')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">{{ __('Products & Stock') }}</h2>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-lg"></i>
            <span>{{ __('Add New Product') }}</span>
        </a>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Stock Code') }}</th>
                            <th>{{ __('Product Name') }}</th>
                            <th>{{ __('Purchase Price') }}</th>
                            <th>{{ __('Sale Price') }}</th>
                            <th class="text-center">{{ __('Current Stock') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                            <td><span class="stock-code-badge">{{ $product->code ?? '-' }}</span></td>
                            <td class="fw-bold text-dark">{{ $product->name }}</td>
                            <td>₺{{ number_format($product->purchase_price, 2, ',', '.') }}</td>
                            <td>₺{{ number_format($product->sale_price, 2, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $product->stock <= $product->min_stock ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} rounded-pill px-3 py-1 fw-bold">
                                    {{ $product->stock }} {{ __('Units') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-eye"></i>
                                    <span>{{ __('Stock Card') }}</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-box-seam fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                {{ __('No products found.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection