@extends('layouts.app')

@section('title', __('Add New Product'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">{{ __('Add New Product') }}</h3>
                <p class="text-muted small mb-0">{{ __('Define a new stock card and price details.') }}</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to Products') }}</span>
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                <div class="fw-bold mb-1">{{ __('Please correct the errors below:') }}</div>
                <ul class="mb-0 small ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="{{ route('products.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Product Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('e.g. 15.6 Laptop Backpack') }}" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Stock Code') }} <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-monospace" value="{{ old('code') }}" placeholder="{{ __('e.g. PRD-001') }}" minlength="2" maxlength="50" pattern="[A-Za-z0-9\-_.]+" required>
                            <div class="form-text small">{{ __('2-50 characters. Letters, digits, -, _, . allowed.') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Barcode') }}</label>
                            <input type="text" name="barcode" class="form-control font-monospace" value="{{ old('barcode') }}" placeholder="{{ __('e.g. 869000000000') }}" minlength="8" maxlength="30" pattern="[A-Za-z0-9\-]+">
                            <div class="form-text small">{{ __('Optional. 8-30 alphanumeric characters.') }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Purchase Price') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price') }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Sale Price') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="number" step="0.01" name="sale_price" class="form-control" value="{{ old('sale_price') }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('VAT Rate (%)') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">%</span>
                                <input type="number" name="tax_rate" class="form-control" value="{{ old('tax_rate', 20) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Critical Stock Level') }} <span class="text-danger">*</span></label>
                            <input type="number" name="min_stock" class="form-control" value="{{ old('min_stock', 10) }}" min="0" required>
                            <div class="form-text small">{{ __('System warns if stock falls below this value.') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Initial Stock') }} <span class="text-danger">*</span></label>
                            <input type="number" name="stock" class="form-control" value="{{ old('stock', 0) }}" min="0" required>
                            <div class="form-text small">{{ __('Opening stock quantity entering warehouse (min 0).') }}</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg"></i>
                            <span>{{ __('Save Product') }}</span>
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary px-3">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection