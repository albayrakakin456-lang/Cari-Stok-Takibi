@extends('layouts.app')

@section('title', __('New Sale (Create Invoice)'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">{{ __('New Sale (Create Invoice)') }}</h3>
                <p class="text-muted small mb-0">{{ __('Issue a new sales invoice and update stock.') }}</p>
            </div>
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to Sales') }}</span>
            </a>
        </div>

        @if ($errors->has('stock_error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3 p-3">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
                <div>
                    <h6 class="fw-bold mb-1 text-danger">{{ __('Eksi Stok Uyarısı!') }}</h6>
                    <p class="mb-0 small text-danger">{{ $errors->first('stock_error') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any() && !$errors->has('stock_error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                <div class="fw-bold mb-1">{{ __('Please correct the errors below:') }}</div>
                <ul class="mb-0 small ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('sales.store') }}" method="POST" id="saleForm" onsubmit="return validateStockBeforeSubmit(event)">
            @csrf

            <!-- 1. Müşteri Seçimi -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-person-fill me-1"></i>
                        {{ __('Customer Information') }}
                    </h6>
                </div>
                <div class="card-body p-4">
                    <label class="form-label fw-semibold">{{ __('Select Customer') }} <span class="text-danger">*</span></label>
                    <select name="contact_id" class="form-select" required>
                        <option value="">{{ __('Select...') }}</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                {{ $contact->name }} ({{ $contact->phone ?? __('Without Phone') }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 2. Ürünler (Sepet) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-cart3 me-1"></i>
                        {{ __('Products to Sell') }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="products-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">{{ __('Product Name') }}</th>
                                    <th style="width: 170px;">{{ __('Quantity') }}</th>
                                    <th style="width: 200px;">{{ __('Unit Price (₺)') }}</th>
                                    <th style="width: 80px;" class="text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <tr>
                                    <td class="ps-4">
                                        <select name="product_id[]" class="form-select product-select" onchange="handleProductChange(this)" required>
                                            <option value="">{{ __('Select Product...') }}</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" 
                                                        data-stock="{{ $product->stock }}"
                                                        data-price="{{ $product->sale_price }}"
                                                        {{ $product->stock <= 0 ? 'class=text-danger' : '' }}>
                                                    {{ $product->name }} ({{ __('Current Stock') }}: {{ $product->stock }}{{ $product->stock <= 0 ? ' - TÜKENDİ' : '' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="stock-feedback small mt-1"></div>
                                    </td>
                                    <td>
                                        <input type="number" name="quantity[]" class="form-control qty-input" value="1" min="1" oninput="handleQuantityChange(this)" required>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">₺</span>
                                            <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price-input" placeholder="0.00" required>
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

            <!-- Eksi Stok Canlı Hata Uyarısı Kutusu -->
            <div id="liveStockWarning" class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 d-none">
                <i class="bi bi-x-circle-fill me-2"></i>
                <span id="liveStockWarningText"></span>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" id="submitBtn" class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span>{{ __('Complete and Save Sale') }}</span>
                </button>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-3">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function handleProductChange(select) {
        var tr = select.closest('tr');
        var selectedOpt = select.options[select.selectedIndex];
        var stock = parseInt(selectedOpt.getAttribute('data-stock')) || 0;
        var price = selectedOpt.getAttribute('data-price') || '';
        
        var priceInput = tr.querySelector('.unit-price-input');
        if (priceInput && !priceInput.value) {
            priceInput.value = price;
        }

        var qtyInput = tr.querySelector('.qty-input');
        var feedback = tr.querySelector('.stock-feedback');

        if (select.value && stock <= 0) {
            feedback.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-octagon"></i> Bu ürünün stoğu tükenmiştir (0 adet)! Satış yapılamaz.</span>';
            select.classList.add('is-invalid');
            qtyInput.classList.add('is-invalid');
        } else if (select.value) {
            feedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Mevcut Stok: <strong>' + stock + '</strong> adet</span>';
            select.classList.remove('is-invalid');
            qtyInput.classList.remove('is-invalid');
        } else {
            feedback.innerHTML = '';
            select.classList.remove('is-invalid');
            qtyInput.classList.remove('is-invalid');
        }

        handleQuantityChange(qtyInput);
    }

    function handleQuantityChange(input) {
        var tr = input.closest('tr');
        var select = tr.querySelector('.product-select');
        var selectedOpt = select ? select.options[select.selectedIndex] : null;
        var stock = selectedOpt ? parseInt(selectedOpt.getAttribute('data-stock')) : 0;
        var qty = parseInt(input.value) || 0;
        var feedback = tr.querySelector('.stock-feedback');

        if (select && select.value) {
            if (stock <= 0) {
                input.classList.add('is-invalid');
                feedback.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-octagon"></i> Bu ürünün stoğu tükenmiştir! Satış yapılamaz.</span>';
            } else if (qty > stock) {
                input.classList.add('is-invalid');
                feedback.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle"></i> Yetersiz stok! En fazla ' + stock + ' adet satabilirsiniz.</span>';
            } else {
                input.classList.remove('is-invalid');
                feedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Mevcut Stok: <strong>' + stock + '</strong> adet (Kalan: ' + (stock - qty) + ')</span>';
            }
        }
        checkAllStockStatus();
    }

    function checkAllStockStatus() {
        var hasError = false;
        var errorMsg = '';
        var rows = document.querySelectorAll('#cart-body tr');

        rows.forEach(function(row) {
            var select = row.querySelector('.product-select');
            var qtyInput = row.querySelector('.qty-input');
            if (select && select.value) {
                var stock = parseInt(select.options[select.selectedIndex].getAttribute('data-stock')) || 0;
                var qty = parseInt(qtyInput.value) || 0;
                if (stock <= 0) {
                    hasError = true;
                    errorMsg = 'Sepetteki bazı ürünlerin stoğu tükendiği için satış onaylanamaz.';
                } else if (qty > stock) {
                    hasError = true;
                    errorMsg = 'Talep edilen miktar mevcut depo stoğunu aşıyor! Lütfen miktarı düşürün.';
                }
            }
        });

        var warningBox = document.getElementById('liveStockWarning');
        var warningText = document.getElementById('liveStockWarningText');
        var submitBtn = document.getElementById('submitBtn');

        if (hasError) {
            warningBox.classList.remove('d-none');
            warningText.innerText = errorMsg;
            submitBtn.classList.add('disabled');
            submitBtn.setAttribute('disabled', 'disabled');
        } else {
            warningBox.classList.add('d-none');
            submitBtn.classList.remove('disabled');
            submitBtn.removeAttribute('disabled');
        }
    }

    function addRow() {
        var row = document.querySelector('#cart-body tr').cloneNode(true);
        row.querySelectorAll('input').forEach(input => input.value = '');
        row.querySelector('input[name="quantity[]"]').value = '1';
        row.querySelector('.qty-input').value = '1';
        row.querySelector('.stock-feedback').innerHTML = '';
        row.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.getElementById('cart-body').appendChild(row);
    }

    function removeRow(btn) {
        var tr = btn.closest('tr');
        var allRows = document.querySelectorAll('#cart-body tr');
        if (allRows.length > 1) {
            tr.remove();
            checkAllStockStatus();
        } else {
            alert('En az bir ürün kalemi bulunmalıdır.');
        }
    }

    function validateStockBeforeSubmit(event) {
        var hasError = false;
        var rows = document.querySelectorAll('#cart-body tr');

        rows.forEach(function(row) {
            var select = row.querySelector('.product-select');
            var qtyInput = row.querySelector('.qty-input');
            if (select && select.value) {
                var stock = parseInt(select.options[select.selectedIndex].getAttribute('data-stock')) || 0;
                var qty = parseInt(qtyInput.value) || 0;
                if (stock <= 0 || qty > stock) {
                    hasError = true;
                }
            }
        });

        if (hasError) {
            event.preventDefault();
            alert('Hata: Yetersiz veya tükenmiş stokla satış yapılamaz! Lütfen sepeti kontrol ediniz.');
            return false;
        }
        return true;
    }
</script>
@endsection