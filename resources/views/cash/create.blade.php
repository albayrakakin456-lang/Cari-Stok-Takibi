@extends('layouts.app')

@section('title', __('New Cash Movement'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">{{ __('New Cash Movement') }}</h3>
                <p class="text-muted small mb-0">{{ __('Record income or expense transaction in ledger.') }}</p>
            </div>
            <a href="{{ route('cash.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to Cash Ledger') }}</span>
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
                <form action="{{ route('cash.store') }}" method="POST">
                    @csrf

                    <!-- 1. Hareket Türü -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2">{{ __('Transaction Type') }} <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check p-3 border rounded-3 flex-fill">
                                <input class="form-check-input ms-0 me-2" type="radio" name="type" id="type_out" value="out" {{ old('type', 'out') == 'out' ? 'checked' : '' }} required>
                                <label class="form-check-label text-danger fw-bold cursor-pointer" for="type_out">
                                    <i class="bi bi-arrow-up-right-circle-fill me-1"></i>
                                    {{ __('Expense / Outflow (Cash Out)') }}
                                </label>
                            </div>
                            <div class="form-check p-3 border rounded-3 flex-fill">
                                <input class="form-check-input ms-0 me-2" type="radio" name="type" id="type_in" value="in" {{ old('type') == 'in' ? 'checked' : '' }}>
                                <label class="form-check-label text-success fw-bold cursor-pointer" for="type_in">
                                    <i class="bi bi-arrow-down-left-circle-fill me-1"></i>
                                    {{ __('Income / Inflow (Cash In)') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Tutar -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Amount (₺)') }} <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text fw-bold">₺</span>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>
                    </div>

                    <!-- 3. İlişkili Cari -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Related Contact (Optional)') }}</label>
                        <select name="contact_id" class="form-select">
                            <option value="">{{ __('No Contact (General Expense / Utility / Rent)') }}</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                    {{ $contact->name }} ({{ $contact->type == 'customer' ? __('Customer') : __('Supplier') }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">{{ __('Leave blank for general overhead like electricity bills or rent.') }}</div>
                    </div>

                    <!-- 4. Açıklama -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ __('Description') }} <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="{{ __('e.g. Electric bill or cash collection from customer') }}" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg"></i>
                            <span>{{ __('Save Cash Movement') }}</span>
                        </button>
                        <a href="{{ route('cash.index') }}" class="btn btn-outline-secondary px-3">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

