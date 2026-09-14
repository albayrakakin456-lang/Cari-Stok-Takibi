@extends('layouts.app')

@section('title', __('New Purchase (Goods Receipt)'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">{{ __('New Purchase (Goods Receipt)') }}</h3>
                <p class="text-muted small mb-0">{{ __('Record supplier invoice and add items to warehouse stock.') }}</p>
            </div>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to Purchases') }}</span>
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

        @if($contacts->isEmpty())
            <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
                <strong>{{ __('Notice:') }}</strong> {{ __('There are currently no contacts registered as Supplier in the system. Please add a supplier contact first.') }}
                <a href="{{ route('contacts.create') }}" class="alert-link fw-semibold ms-1">{{ __('Add New Contact') }}</a>
            </div>
        @endif

        <form action="{{ route('purchases.store') }}" method="POST">
            @csrf   

            <!-- 1. Tedarikçi Seçimi -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-truck me-1"></i>
                        {{ __('Supplier Information') }}
                    </h6>
                </div>
                <div class="card-body p-4">
                    <label class="form-label fw-semibold">{{ __('Select Supplier') }} <span class="text-danger">*</span></label>
                    <select name="contact_id" class="form-select" required>
                        <option value="">{{ __('Select...') }}</option>
                        @foreach($contacts as $supplier)
                            <option value="{{ $supplier->id }}">
                                {{ $supplier->name }} ({{ $supplier->phone ?? __('Without Phone') }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 2. Alınan Ürünler (Sepet) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-box-seam me-1"></i>
                        {{ __('Purchased Products (Warehouse Inflow)') }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="products-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">{{ __('Product Name') }}</th>
                                    <th style="width: 150px;">{{ __('Inflow Quantity') }}</th>
                                    <th style="width: 200px;">{{ __('Purchase Unit Price (₺)') }}</th>
                                    <th style="width: 80px;" class="text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <tr>
                                    <td class="ps-4">
                                        <select name="product_id[]" class="form-select" required>
                                            <option value="">{{ __('Select Product...') }}</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" @selected((int) old('product_id.0', $selectedProductId) === $product->id)>
                                                    {{ $product->name }} ({{ __('Current Stock') }}: {{ $product->stock }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="quantity[]" class="form-control" value="1" min="1" required>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">₺</span>
                                            <input type="number" step="0.01" name="unit_price[]" class="form-control" placeholder="0.00" required>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)" title="{{ __('Delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-light bg-opacity-50 border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addRow()">
                            <i class="bi bi-plus-lg me-1"></i>
                            {{ __('+ Add New Product Row') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Ödeme Seçeneği -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_paid" value="1" id="isPaidSwitch">
                        <label class="form-check-label fw-bold" for="isPaidSwitch">
                            {{ __('Paid in cash from register (if checked, recorded as Cash Outflow automatically)') }}
                        </label>
                    </div>
                    <div class="form-text small mt-1">
                        {{ __('If unchecked, balance is credited as open-account payable to supplier.') }}
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span>{{ __('Save Purchase & Receive to Stock') }}</span>
                </button>
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary px-3">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function addRow() {
        var row = document.querySelector('#cart-body tr').cloneNode(true);
        row.querySelectorAll('input').forEach(input => input.value = '');
        row.querySelector('input[name="quantity[]"]').value = '1';
        document.getElementById('cart-body').appendChild(row);
    }

    function removeRow(btn) {
        var tbody = document.getElementById('cart-body');
        if (tbody.querySelectorAll('tr').length > 1) {
            btn.closest('tr').remove();
        } else {
            alert("{{ __('Faturada en az bir ürün satırı bulunmalıdır.') }}");
        }
    }
</script>
@endsection
