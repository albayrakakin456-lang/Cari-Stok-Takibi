@extends('layouts.app')

@section('title', __('Edit Campaign').' - Cari & Stok Takip')

@section('content')
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('campaigns.index') }}" class="btn btn-light border" title="{{ __('Back') }}">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ __('Edit Campaign') }}</h2>
            <p class="text-muted mb-0">{{ $campaign->name }}</p>
        </div>
    </div>

    @include('campaigns._form', [
        'action' => route('campaigns.update', $campaign),
        'method' => 'PUT',
        'submitLabel' => __('Save Changes'),
    ])
@endsection
