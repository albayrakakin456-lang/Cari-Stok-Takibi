@extends('layouts.app')

@section('title', __('Add New Contact'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">{{ __('Add New Contact') }}</h3>
                <p class="text-muted small mb-0">{{ __('Register a new customer or supplier card.') }}</p>
            </div>
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to Contacts') }}</span>
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
                <form action="{{ route('contacts.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Contact Name / Title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('e.g. Ahmet Yilmaz or ABC Ltd.') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Contact Type') }} <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="">{{ __('Select...') }}</option>
                            <option value="customer" {{ old('type') == 'customer' ? 'selected' : '' }}>{{ __('Customer (Sales)') }}</option>
                            <option value="supplier" {{ old('type') == 'supplier' ? 'selected' : '' }}>{{ __('Supplier (Purchases)') }}</option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Phone') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="{{ __('05XXXXXXXXX') }}" pattern="[0-9+\s\-\(\)]+" oninput="this.value = this.value.replace(/[^0-9+\s\-\(\)]/g, '')" maxlength="20">
                            </div>
                            <div class="form-text small">{{ __('Only digits and phone characters (+, -, space) are accepted.') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Email') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="{{ __('info@company.com') }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Address') }}</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="{{ __('Invoice / Delivery address') }}">{{ old('address') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('Special notes regarding the contact...') }}">{{ old('note') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg"></i>
                            <span>{{ __('Save Contact') }}</span>
                        </button>
                        <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary px-3">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

